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
            'disco'     => $input['disco'],
            'meter_no'  => $input['meter_no'],
            'type'      => $input['type'],
            'amount'    => (int) $input['amount'],
            'reference' => $ref,
        ];
        if (!empty($input['callback_url'])) {
            $payload['callback_url'] = $input['callback_url'];
        }

        $path = $this->client->path('electricity', 'purchase_create');
        $res  = $this->client->post($path, $payload);

        $body = $res['json'] ?? [];
        if (!is_array($body) || empty($body)) {
            $body = ['raw' => $res['body']];
        }

        $status = $res['ok']
            ? strtoupper($body['status'] ?? BillTransaction::S_PENDING)
            : BillTransaction::S_FAILED;

        $tx = BillTransaction::create([
            'reference'         => $ref,
            'service'           => 'electricity',
            'product'           => $input['disco'],
            'account'           => $input['meter_no'],
            'amount'            => (int) $input['amount'],
            'amount_paid'       => (int) ($body['amount_paid'] ?? 0),
            'fee'               => (int) ($body['fee'] ?? ($body['charges'] ?? 0)),
            'cost'              => (int) ($body['amount_debited'] ?? ($body['amount'] ?? 0)),
            'currency'          => strtoupper($body['currency'] ?? 'NGN'),
            'provider'          => 'redbiller',
            'provider_txn_id'   => $body['id'] ?? null,
            'callback_url'      => $input['callback_url'] ?? null,
            'customer_name'     => $body['customer_name'] ?? null,
            'status'            => $status,
            'request_payload'   => $payload,
            'provider_response' => $body,
            'meta'              => ['type' => $input['type']],
        ]);

        if ($status === BillTransaction::S_SUCCESS) {
            $tx->markSuccess($body);
        } elseif ($status === BillTransaction::S_FAILED) {
            $tx->markFailed($body);
        }

        // If response already includes tokens, stash them
        $tokens = $body['tokens'] ?? ($body['data']['tokens'] ?? null);
        if (is_array($tokens)) {
            foreach ($tokens as $tk) {
                $tokenValue = $tk['token'] ?? ($tk['pin'] ?? null);
                if (!$tokenValue) {
                    continue;
                }

                ElectricityToken::firstOrCreate([
                    'bill_transaction_id' => $tx->id,
                    'token'               => $tokenValue,
                ], [
                    'units'       => isset($tk['units']) ? (int) $tk['units'] : null,
                    'tariff_code' => $tk['tariff'] ?? null,
                    'raw'         => $tk,
                ]);
            }
        }

        $tx->load('electricityTokens');

        return [
            'reference'    => $ref,
            'transaction'  => [
                'status'        => $tx->status,
                'customer_name' => $tx->customer_name,
                'amount'        => $tx->amount,
                'amount_paid'   => $tx->amount_paid,
                'fee'           => $tx->fee,
                'cost'          => $tx->cost,
                'currency'      => $tx->currency,
                'tokens'        => $tx->electricityTokens->map(fn ($token) => [
                    'token'       => $token->token,
                    'units'       => $token->units,
                    'tariff_code' => $token->tariff_code,
                ])->all(),
            ],
            'response'     => $res,
        ];
    }

    /** Poll status; update transaction; capture tokens if they arrive late */
    public function status(string $reference): array
    {
        $path = $this->client->path('electricity', 'purchase_status');
        $res  = $this->client->post($path, ['reference' => $reference]);

        $tx = BillTransaction::where('reference', $reference)->first();
        if ($tx && $res['ok']) {
            $body = $res['json'] ?? [];
            $status = strtoupper($body['status'] ?? $tx->status);
            $tx->update([
                'status'            => $status,
                'customer_name'     => $body['customer_name'] ?? $tx->customer_name,
                'amount_paid'       => (int) ($body['amount_paid'] ?? $tx->amount_paid),
                'cost'              => (int) ($body['amount_debited'] ?? $tx->cost),
                'fee'               => (int) ($body['fee'] ?? $tx->fee),
                'currency'          => strtoupper($body['currency'] ?? $tx->currency),
                'provider_txn_id'   => $body['id'] ?? $tx->provider_txn_id,
                'provider_response' => $body ?: ['raw' => $res['body']],
            ]);

            // Late tokens?
            $tokens = $body['tokens'] ?? ($body['data']['tokens'] ?? null);
            if (is_array($tokens)) {
                foreach ($tokens as $tk) {
                    $tokenValue = $tk['token'] ?? ($tk['pin'] ?? null);
                    if (!$tokenValue) {
                        continue;
                    }

                    ElectricityToken::firstOrCreate([
                        'bill_transaction_id' => $tx->id,
                        'token'               => $tokenValue,
                    ], [
                        'units'       => isset($tk['units']) ? (int) $tk['units'] : null,
                        'tariff_code' => $tk['tariff'] ?? null,
                        'raw'         => $tk,
                    ]);
                }
            }

            if ($status === BillTransaction::S_SUCCESS && !$tx->paid_at) {
                $tx->markSuccess($body ?: []);
            }
            if ($status === BillTransaction::S_FAILED  && !$tx->failed_at) {
                $tx->markFailed($body ?: []);
            }
        }

        return $res;
    }
}
