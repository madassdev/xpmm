<?php

namespace App\Services\Redbiller;

use App\Models\BillTransaction;
use App\Models\ElectricityToken;
use Illuminate\Support\Str;

class ElectricityService
{
    public function __construct(private Client $client) {}

    /** Validate meter and get customer name */
    public function validateMeter(array $input): array
    {
        $payload = [
            'disco'    => $input['disco'],
            'meter_no' => $input['meter_no'],
            'type'     => $input['type'], // prepaid|postpaid
        ];

        $path = $this->client->path('electricity', 'validate');
        return $this->client->post($path, $payload);
    }

    /** Create purchase; persist BillTransaction */
    public function purchase(array $input): array
    {
        $ref = $input['reference'] ?? Str::ulid()->toBase32();

        $payload = [
            'disco'        => $input['disco'],
            'meter_no'     => $input['meter_no'],
            'type'         => $input['type'],
            'amount'       => (int) $input['amount'],
            'reference'    => $ref,
        ];
        if (!empty($input['callback_url'])) $payload['callback_url'] = $input['callback_url'];

        $path = $this->client->path('electricity', 'purchase_create');
        $res  = $this->client->post($path, $payload);

        $tx = BillTransaction::create([
            'reference'         => $ref,
            'service'           => 'electricity',
            'product'           => $input['disco'],
            'account'           => $input['meter_no'],
            'amount'            => (int) $input['amount'],
            'currency'          => 'NGN',
            'provider'          => 'redbiller',
            'status'            => $res['ok'] ? strtoupper($res['json']['status'] ?? 'PENDING') : 'FAILED',
            'request_payload'   => $payload,
            'provider_response' => $res['json'] ?? ['raw' => $res['body']],
            'meta'              => ['type' => $input['type']],
        ]);

        // If response already includes tokens, stash them
        $tokens = $res['json']['tokens'] ?? $res['json']['data']['tokens'] ?? null;
        if (is_array($tokens)) {
            foreach ($tokens as $tk) {
                ElectricityToken::create([
                    'bill_transaction_id' => $tx->id,
                    'token'     => $tk['token'] ?? ($tk['pin'] ?? ''),
                    'units'     => isset($tk['units']) ? (int)$tk['units'] : null,
                    'tariff_code' => $tk['tariff'] ?? null,
                    'raw'       => $tk,
                ]);
            }
        }

        return ['reference' => $ref, 'response' => $res];
    }

    /** Poll status; update transaction; capture tokens if they arrive late */
    public function status(string $reference): array
    {
        $path = $this->client->path('electricity', 'purchase_status');
        $res  = $this->client->post($path, ['reference' => $reference]);

        $tx = BillTransaction::where('reference', $reference)->first();
        if ($tx && $res['ok']) {
            $status = strtoupper($res['json']['status'] ?? $tx->status);
            $tx->update([
                'status'            => $status,
                'customer_name'     => $res['json']['customer_name'] ?? $tx->customer_name,
                'amount_paid'       => (int) ($res['json']['amount_paid'] ?? $tx->amount_paid),
                'cost'              => (int) ($res['json']['amount_debited'] ?? $tx->cost),
                'provider_txn_id'   => $res['json']['id'] ?? $tx->provider_txn_id,
                'provider_response' => $res['json'],
            ]);

            // Late tokens?
            $tokens = $res['json']['tokens'] ?? $res['json']['data']['tokens'] ?? null;
            if (is_array($tokens)) {
                foreach ($tokens as $tk) {
                    $exists = ElectricityToken::where('bill_transaction_id', $tx->id)
                        ->where('token', $tk['token'] ?? ($tk['pin'] ?? ''))
                        ->exists();
                    if (!$exists) {
                        ElectricityToken::create([
                            'bill_transaction_id' => $tx->id,
                            'token'     => $tk['token'] ?? ($tk['pin'] ?? ''),
                            'units'     => isset($tk['units']) ? (int)$tk['units'] : null,
                            'tariff_code' => $tk['tariff'] ?? null,
                            'raw'       => $tk,
                        ]);
                    }
                }
            }

            if ($status === 'SUCCESS' && !$tx->paid_at) $tx->markSuccess($res['json']);
            if ($status === 'FAILED'  && !$tx->failed_at) $tx->markFailed($res['json']);
        }

        return $res;
    }
}
