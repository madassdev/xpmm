<?php

namespace App\Services\Redbiller;

use App\Models\BillTransaction;
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

        // Persist locally (non-fatal if DB is unavailable—feel free to wrap in try/catch)
        BillTransaction::updateOrCreate([
            'reference' => $ref
        ], [
            'reference'         => $ref,
            'service'           => 'airtime',
            'product'           => $payload['product'] ?? null,
            'network'           => $input['network'] ?? null,
            'phone'             => $payload['phone_no'],
            'ported'            => !empty($input['ported']),
            'amount'            => (int) ($input['amount'] ?? 0),
            'callback_url'      => $payload['callback_url'] ?? null,
            'provider'          => 'redbiller',
            'status'            => $res['ok'] ? (strtoupper($res['json']['status'] ?? 'PENDING')) : 'FAILED',
            'provider_txn_id'   => $res['json']['id'] ?? null,
            'request_payload'   => $payload,
            'provider_response' => $res['json'] ?? ['raw' => $res['body']],
        ]);

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

    public function dataPlansList(string $product): array
    {
        $path = $this->client->path('data', 'plans_list');
        return $this->client->post($path, ['product' => $product]);
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
}
