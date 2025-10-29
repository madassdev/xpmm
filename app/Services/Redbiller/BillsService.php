<?php

namespace App\Services\Redbiller;

use App\Models\Bill;
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
         // Check database first - plans synced within last 24 hours are considered fresh
         $existingPlans = Bill::service('data')
             ->provider($product)
             ->where('synced_at', '>=', now()->subDay())
             ->get();
 
         if ($existingPlans->isNotEmpty()) {
             return $existingPlans->map(function ($plan) {
                 return [
                     'id' => $plan->code,
                     'price' => $plan->price,
                     'name' => $plan->name,
                     'meta' => $plan->meta,
                 ];
             })->toArray();
         }
 
         // Fetch from API if not in DB or stale
         $res = $this->dataPlans($product);
         if (!($res['ok'] ?? false)) {
             $status = $res['status'];
             throw new Exception("Failed to fetch data plans from provider: [{$status}]");
         }
 
         $json = $res['json'] ?? [];
         $categories = $json['categories'] ?? $json['details']['categories'] ?? [];
         $now = now();
 
         // Save to database
         foreach ($categories as $cat) {
             $code = $cat['code'] ?? ($cat['plan_code'] ?? '');
             $price = (int) ($cat['amount'] ?? 0);
             $name = $cat['label'] ?? ($cat['name'] ?? ($cat['code'] ?? ''));
 
             Bill::updateOrCreate(
                 [
                     'service' => 'data',
                     'provider' => $product,
                     'code' => $code,
                 ],
                 [
                     'name' => $name,
                     'price' => $price,
                     'meta' => $cat,
                     'synced_at' => $now,
                 ]
             );
         }
 
         // Return from database to ensure consistency
         return Bill::service('data')
             ->provider($product)
             ->get()
             ->map(function ($plan) {
                 return [
                     'id' => $plan->code,
                     'price' => $plan->price,
                     'name' => $plan->name,
                     'meta' => $plan->meta,
                 ];
             })->toArray();
     }
 
     public function getDataPlanByCode($product, $code)
     {
         // Try database first
         $plan = Bill::service('data')
             ->provider($product)
             ->code($code)
             ->first();
 
         if ($plan) {
             return [
                 'id' => $plan->code,
                 'price' => $plan->price,
                 'name' => $plan->name,
                 'meta' => $plan->meta,
             ];
         }
 
         // If not in DB, fetch plans list (which will sync to DB)
         $plans = $this->getDataPlansList($product);
         return collect($plans)->firstWhere('id', $code);
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

    public function cableValidate(string $product, string $smartcard): array
    {
        $path = $this->client->path('cable', 'validate');
        return $this->client->post($path, [
            'product'       => $product,
            'smart_card_no' => $smartcard,
        ]);
    }
    

    public function getCablePlansList($provider)
    {
        // Check database first - plans synced within last 24 hours are considered fresh
        $existingPlans = Bill::service('cable')
            ->provider($provider)
            ->where('synced_at', '>=', now()->subDay())
            ->get();

        if ($existingPlans->isNotEmpty()) {
            return $existingPlans->map(function ($plan) use ($provider) {
                return [
                    'id' => $plan->code,
                    'price' => $plan->price,
                    'name' => $plan->name,
                    'plan' => "{$provider} - {$plan->name}",
                    'meta' => $plan->meta,
                ];
            })->toArray();
        }

        // Fetch from API if not in DB or stale
        $res = $this->cablePlans($provider);
        if (!($res['ok'] ?? false)) {
            $status = $res['status'];
            throw new Exception("Failed to fetch cable plans from provider: [{$status}]");
        }

        $json = $res['json'] ?? [];
        $categories = $json['categories'] ?? $json['details']['categories'] ?? [];
        $now = now();

        // Save to database
        foreach ($categories as $cat) {
            $code = $cat['code'] ?? ($cat['plan_code'] ?? '');
            $price = (int) ($cat['amount'] ?? 0);
            $name = $cat['label'] ?? ($cat['name'] ?? ($cat['code'] ?? ''));

            Bill::updateOrCreate(
                [
                    'service' => 'cable',
                    'provider' => $provider,
                    'code' => $code,
                ],
                [
                    'name' => $name,
                    'price' => $price,
                    'meta' => $cat,
                    'synced_at' => $now,
                ]
            );
        }

        // Return from database to ensure consistency
        return Bill::service('cable')
            ->provider($provider)
            ->get()
            ->map(function ($plan) use ($provider) {
                return [
                    'id' => $plan->code,
                    'price' => $plan->price,
                    'name' => $plan->name,
                    'plan' => "{$provider} - {$plan->name}",
                    'meta' => $plan->meta,
                ];
            })->toArray();
    }

    public function getCablePlanByCode($product, $code)
    {
        // Try database first
        $plan = Bill::service('cable')
            ->provider($product)
            ->code($code)
            ->first();

        if ($plan) {
            return [
                'id' => $plan->code,
                'price' => $plan->price,
                'name' => $plan->name,
                'plan' => "{$product} - {$plan->name}",
                'meta' => $plan->meta,
            ];
        }

        // If not in DB, fetch plans list (which will sync to DB)
        $plans = $this->getCablePlansList($product);
        return collect($plans)->firstWhere('id', $code);
    }

    public function cablePurchaseCreate(array $input): array
    {
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

        return ['reference' => $ref, 'response' => $res];
    }

    /* =========================
     * Internet
     * ========================= */

    public function internetPlans(string $product): array
    {
        $path = $this->client->path('internet', 'plans_list');
        return $this->client->post($path, ['product' => $product]);
    }

    public function getInternetPlansList($provider)
    {
        $existingPlans = Bill::service('internet')
            ->provider($provider)
            ->where('synced_at', '>=', now()->subDay())
            ->get();

        if ($existingPlans->isNotEmpty()) {
            return $existingPlans->map(function ($plan) {
                return [
                    'id'    => $plan->code,
                    'price' => $plan->price,
                    'name'  => $plan->name,
                    'meta'  => $plan->meta,
                ];
            })->toArray();
        }

        $res = $this->internetPlans($provider);
        if (!($res['ok'] ?? false)) {
            $status = $res['status'];
            throw new Exception("Failed to fetch internet plans from provider: [{$status}]");
        }

        $json = $res['json'] ?? [];
        $categories = $json['categories'] ?? $json['details']['categories'] ?? [];
        $now = now();

        foreach ($categories as $cat) {
            $code = $cat['code'] ?? ($cat['plan_code'] ?? '');
            $price = (int) ($cat['amount'] ?? 0);
            $name = $cat['label'] ?? ($cat['name'] ?? ($cat['code'] ?? ''));

            Bill::updateOrCreate(
                [
                    'service'  => 'internet',
                    'provider' => $provider,
                    'code'     => $code,
                ],
                [
                    'name'     => $name,
                    'price'    => $price,
                    'meta'     => $cat,
                    'synced_at'=> $now,
                ]
            );
        }

        return Bill::service('internet')
            ->provider($provider)
            ->get()
            ->map(function ($plan) {
                return [
                    'id'    => $plan->code,
                    'price' => $plan->price,
                    'name'  => $plan->name,
                    'meta'  => $plan->meta,
                ];
            })->toArray();
    }

    public function getInternetPlanByCode($provider, $code)
    {
        $plan = Bill::service('internet')
            ->provider($provider)
            ->code($code)
            ->first();

        if ($plan) {
            return [
                'id'    => $plan->code,
                'price' => $plan->price,
                'name'  => $plan->name,
                'meta'  => $plan->meta,
            ];
        }

        $plans = $this->getInternetPlansList($provider);
        return collect($plans)->firstWhere('id', $code);
    }

    public function internetPurchaseCreate(array $input): array
    {
        $ref = $input['reference'] ?? Str::ulid()->toBase32();

        $account = $input['account_id']
            ?? $input['customer_id']
            ?? $input['customer_no']
            ?? $input['account']
            ?? $input['phone_no']
            ?? '';

        $payload = [
            'product'        => $input['product'] ?? '',
            'code'           => $input['plan'] ?? $input['code'] ?? '',
            'reference'      => $ref,
            'amount'         => (int) ($input['amount'] ?? 0),
            'customer_id'    => $account,
            'customer_no'    => $account,
            'account_id'     => $account,
        ];

        if (!empty($input['callback_url'])) {
            $payload['callback_url'] = $input['callback_url'];
        }

        $path = $this->client->path('internet', 'purchase_create');
        $res  = $this->client->post($path, $payload);

        return ['reference' => $ref, 'response' => $res];
    }

    /* =========================
     * Betting
     * ========================= */

    public function bettingPurchaseCreate(array $input): array
    {
        $ref = $input['reference'] ?? Str::ulid()->toBase32();

        $account = $input['account_id']
            ?? $input['customer_id']
            ?? $input['customer_no']
            ?? $input['account']
            ?? '';

        $payload = [
            'product'     => $input['product'] ?? '',
            'amount'      => (int) ($input['amount'] ?? 0),
            'reference'   => $ref,
            'customer_id' => $account,
            'customer_no' => $account,
            'account_id'  => $account,
        ];

        if (!empty($input['callback_url'])) {
            $payload['callback_url'] = $input['callback_url'];
        }

        $path = $this->client->path('betting', 'purchase_create');
        $res  = $this->client->post($path, $payload);

        return ['reference' => $ref, 'response' => $res];
    }
}
