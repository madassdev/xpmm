<?php

namespace App\Services\Redbiller;

use App\Models\BillTransaction;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BillsService
{
    public function __construct(private Client $client) {}

    /* =========================
     * Airtime
     * ========================= */

    /**
     * Create an airtime purchase.
     * Input expects FE-style keys; we map to Redbiller spec.
     * Required: product (MTN|Airtel|Glo|9mobile), phone, amount
     * Optional: ported (bool), callback_url (string), reference (string)
     */
    public function airtimePurchaseCreate(array $input): array
    {
        // Map FE → Redbiller
        $payload = [
            'product'  => $input['product'] ?? strtoupper($input['network'] ?? ''), // support "network" alias
            'phone_no' => $input['phone']   ?? $input['phone_no'] ?? '',
            'amount'   => (int) ($input['amount'] ?? 0),
        ];

        if (!empty($input['ported'])) {
            // Docs show string "true"/"false" in some examples; play it safe.
            $payload['ported'] = $input['ported'] === true ? 'true' : (string) $input['ported'];
        }

        if (!empty($input['callback_url'])) {
            $payload['callback_url'] = $input['callback_url'];
        }

        $ref = $input['reference'] ?? Str::ulid()->toBase32();
        $payload['reference'] = $ref;

        $path = $this->client->path('airtime', 'purchase_create');
        $res  = $this->client->post($path, $payload);

        // // Persist locally (non-fatal if DB is unavailable—feel free to wrap in try/catch)
        // BillTransaction::updateOrCreate([
        //     'reference' => $ref
        // ], [
        //     'reference'         => $ref,
        //     'service'           => 'airtime',
        //     'product'           => $payload['product'] ?? null,
        //     'network'           => $input['network'] ?? null,
        //     'phone'             => $payload['phone_no'],
        //     'ported'            => !empty($input['ported']),
        //     'amount'            => (int) ($input['amount'] ?? 0),
        //     'callback_url'      => $payload['callback_url'] ?? null,
        //     'provider'          => 'redbiller',
        //     'status'            => $res['ok'] ? (strtoupper($res['json']['status'] ?? 'PENDING')) : 'FAILED',
        //     'provider_txn_id'   => $res['json']['id'] ?? null,
        //     'request_payload'   => $payload,
        //     'provider_response' => $res['json'] ?? ['raw' => $res['body']],
        // ]);

        return ['reference' => $ref, 'response' => $res];
    }

    public function airtimePurchaseStatus(string $reference): array
    {
        $path = $this->client->path('airtime', 'purchase_status');
        $res  = $this->client->post($path, ['reference' => $reference]);

        if ($tx = BillTransaction::where('reference', $reference)->first()) {
            if ($res['ok'] && !empty($res['json']['status'])) {
                $status = strtoupper($res['json']['status']);
                $tx->update([
                    'status'            => $status,
                    'amount_paid'       => (int) ($res['json']['amount_paid'] ?? $tx->amount_paid),
                    'amount_discount'   => (int) ($res['json']['discount_amount'] ?? $tx->amount_discount),
                    'provider_txn_id'   => $res['json']['id'] ?? $tx->provider_txn_id,
                    'provider_response' => $res['json'],
                ]);
                if ($status === BillTransaction::S_SUCCESS && !$tx->paid_at) $tx->markSuccess($res['json']);
                if ($status === BillTransaction::S_FAILED && !$tx->failed_at) $tx->markFailed($res['json']);
            }
        }

        return $res;
    }

    public function airtimePurchaseList(array $filters = []): array
    {
        $path = $this->client->path('airtime', 'purchase_list');

        // pass-through only known filters to avoid accidental noise
        $payload = array_filter([
            'reference' => $filters['reference'] ?? null,
            'status'    => $filters['status']    ?? null,
            'product'   => $filters['product']   ?? null,
            'phone_no'  => $filters['phone']     ?? $filters['phone_no'] ?? null,
            'page'      => $filters['page']      ?? null,
            'from'      => $filters['from']      ?? null, // YYYY-MM-DD
            'to'        => $filters['to']        ?? null, // YYYY-MM-DD
        ], fn($v) => !is_null($v));

        return $this->client->post($path, $payload);
    }

    public function airtimePurchaseRetry(string $reference): array
    {
        $path = $this->client->path('airtime', 'purchase_retry');
        return $this->client->post($path, ['reference' => $reference]);
    }

    public function airtimeGetRetriedTrail(string $reference): array
    {
        $path = $this->client->path('airtime', 'retried_trail');
        // Docs show GET with ?reference=... in some sections; but POST variants also exist.
        // We’ll pass as POST to keep header/body consistent. If you prefer GET, swap to $this->client->get($path, ['reference'=>$reference]).
        return $this->client->post($path, ['reference' => $reference]);
    }

    /* =========================
     * Data
     * ========================= */

     public function dataPlans(string $product): array
     {
         $path = $this->client->path('data', 'plans_list');
         return $this->client->post($path, ['product' => $product]);
     }
     
 
     public function getDataPlansList($product)
     {
         $env = config('redbiller.env');
         $cacheKey = "redbiller:data:plans:{$product}:{$env}";
         $res = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($product) {
             return $this->dataPlans($product);
         });
         if (!($res['ok'] ?? false)) {
             $status = $res['status'];
             throw new Exception("Failed to fetch data plans from provider: [{$status}]");
         }
         $json = $res['json'] ?? [];
         $categories = $json['categories'] ?? $json['details']['categories'] ?? [];
         $plans = [];
         foreach ($categories as $cat) {
             // e.g., $cat = [ 'code' => '1GB-7days', 'amount' => 350, 'label' => '1GB (7 days)' ... ]
             $plans[] = [
                 'id'   => $cat['code']   ?? ($cat['plan_code'] ?? ''),
                 'price' => (int) ($cat['amount'] ?? 0),
                 'name'  => $cat['label']  ?? ($cat['name'] ?? ($cat['code'] ?? '')),
                 'meta'   => $cat, // keep the raw in case FE wants more fields (validity, bandwidth, etc.)
             ];
         }
 
         return $plans;
     }
 
     public function getDataPlanByCode($product, $code)
     {
         $plans = $this->getDataPlansList($product);
         $plan = collect($plans)->filter(function($plan) use ($code){
             return $plan['id'] == $code;
         })->first();
         return $plan;
     }

    public function dataPurchaseCreate(array $input): array
    {
        // Map FE → Redbiller
        $payload = [
            'product'  => $input['product'] ?? strtoupper($input['network'] ?? ''), // support "network" alias
            'phone_no' => $input['phone']   ?? $input['phone_no'] ?? '',
            'code'   => $input['plan'] ?? 0,
        ];

        $plans = $this->getDataPlansList($input['product']);

        if (!empty($input['ported'])) {
            // Docs show string "true"/"false" in some examples; play it safe.
            $payload['ported'] = $input['ported'] === true ? 'true' : (string) $input['ported'];
        }

        if (!empty($input['callback_url'])) {
            $payload['callback_url'] = $input['callback_url'];
        }

        $ref = $input['reference'] ?? Str::ulid()->toBase32();
        $payload['reference'] = $ref;

        $path = $this->client->path('data', 'purchase_create');
        $res  = $this->client->post($path, $payload);
        // dd($res);

        // // Persist locally (non-fatal if DB is unavailable—feel free to wrap in try/catch)
        // BillTransaction::updateOrCreate([
        //     'reference' => $ref
        // ], [
        //     'reference'         => $ref,
        //     'service'           => 'data',
        //     'product'           => $payload['product'] ?? null,
        //     'network'           => $input['network'] ?? null,
        //     'phone'             => $payload['phone_no'],
        //     'ported'            => !empty($input['ported']),
        //     'amount'            => (int) ($input['amount'] ?? 0),
        //     'callback_url'      => $payload['callback_url'] ?? null,
        //     'provider'          => 'redbiller', 
        //     'status'            => $res['ok'] ? (strtoupper($res['json']['status'] ?? 'PENDING')) : 'FAILED',
        //     'provider_txn_id'   => $res['json']['id'] ?? null,
        //     'request_payload'   => $payload,
        //     'provider_response' => $res['json'] ?? ['raw' => $res['body']],
        // ]);

        return ['reference' => $ref, 'response' => $res];
    }

    /**
     * NOTE: Data purchase-create path is left null in config to avoid guessing.
     * When you confirm it (likely '/{v}/bills/data/plans/purchase/create'),
     * add it in config and implement similar to airtimePurchaseCreate().
     */

    public function dataPurchaseStatus(string $reference): array
    {
        $path = $this->client->path('data', 'purchase_status');
        return $this->client->post($path, ['reference' => $reference]);
    }

    public function dataPurchaseList(array $filters = []): array
    {
        $path = $this->client->path('data', 'purchase_list');
        $payload = array_filter([
            'reference' => $filters['reference'] ?? null,
            'status'    => $filters['status']    ?? null,
            'product'   => $filters['product']   ?? null,
            'phone_no'  => $filters['phone']     ?? $filters['phone_no'] ?? null,
            'page'      => $filters['page']      ?? null,
            'from'      => $filters['from']      ?? null,
            'to'        => $filters['to']        ?? null,
        ], fn($v) => !is_null($v));

        return $this->client->post($path, $payload);
    }


    public function cablePlans(string $product): array
    {
        $path = $this->client->path('cable', 'plans_list');
        return $this->client->post($path, ['product' => $product]);
    }
    

    public function getCablePlansList($provider)
    {
        $env = config('redbiller.env');
        $cacheKey = "redbiller:cable:plans:{$provider}:{$env}";
        $res = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($provider) {
            return $this->cablePlans($provider);
        });

        if (!($res['ok'] ?? false)) {
            $status = $res['status'];
            throw new Exception("Failed to fetch data plans from provider: [{$status}]");
        }
        $json = $res['json'] ?? [];
        $categories = $json['categories'] ?? $json['details']['categories'] ?? [];
        $plans = [];
        foreach ($categories as $cat) {
            // e.g., $cat = [ 'code' => '1GB-7days', 'amount' => 350, 'label' => '1GB (7 days)' ... ]
            $bundle = $cat['name'];
            $plan = "{$provider} - $bundle";
            $plans[] = [
                'id'   => $cat['code']   ?? ($cat['plan_code'] ?? ''),
                'price' => (int) ($cat['amount'] ?? 0),
                'name'  => $cat['label']  ?? ($cat['name'] ?? ($cat['code'] ?? '')),
                'plan' => $plan,
                'meta'   => $cat, // keep the raw in case FE wants more fields (validity, bandwidth, etc.)
            ];
        }

        return $plans;
    }

    public function getCablePlanByCode($product, $code)
    {
        $plans = $this->getDataPlansList($product);
        $plan = collect($plans)->filter(function($plan) use ($code){
            return $plan['id'] == $code;
        })->first();
        return $plan;
    }

    public function cablePurchaseCreate(array $input): array
    {
        // $data = [
        //     "product" => "DStv",
        //     "code" => "3800",
        //     "smart_card_no" => "0000000000",
        //     "customer_name" => "JOHN DOE",
        //     "phone_no" => "08144698943",
        //     "callback_url" => "https://domain.com",
        //     "reference" => "TRalsGTyew01i"
        // ];

        // Map FE → Redbiller
        $payload = [
            'product'  => $input['product'] ?? strtoupper($input['provider'] ?? ''), // support "network" alias
            'smart_card_no' => $input['smart_card']   ?? $input['smart_card_no'] ?? '',
            'code'   => $input['plan'] ?? $input['code'] ?? 0,
            'phone_no' => $input['phone_no']   ?? $input['phone'] ?? '',
        ];

        if (!empty($input['ported'])) {
            // Docs show string "true"/"false" in some examples; play it safe.
            $payload['ported'] = $input['ported'] === true ? 'true' : (string) $input['ported'];
        }

        if (!empty($input['callback_url'])) {
            $payload['callback_url'] = $input['callback_url'];
        }

        $ref = $input['reference'] ?? Str::ulid()->toBase32();
        $payload['reference'] = $ref;

        $path = $this->client->path('cable', 'purchase_create');
        $res  = $this->client->post($path, $payload);
        // dd($res);

        // // Persist locally (non-fatal if DB is unavailable—feel free to wrap in try/catch)
        // BillTransaction::updateOrCreate([
        //     'reference' => $ref
        // ], [
        //     'reference'         => $ref,
        //     'service'           => 'data',
        //     'product'           => $payload['product'] ?? null,
        //     'network'           => $input['network'] ?? null,
        //     'phone'             => $payload['phone_no'],
        //     'ported'            => !empty($input['ported']),
        //     'amount'            => (int) ($input['amount'] ?? 0),
        //     'callback_url'      => $payload['callback_url'] ?? null,
        //     'provider'          => 'redbiller', 
        //     'status'            => $res['ok'] ? (strtoupper($res['json']['status'] ?? 'PENDING')) : 'FAILED',
        //     'provider_txn_id'   => $res['json']['id'] ?? null,
        //     'request_payload'   => $payload,
        //     'provider_response' => $res['json'] ?? ['raw' => $res['body']],
        // ]);

        return ['reference' => $ref, 'response' => $res];
    }
}
