<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\ElectricityValidateRequest;
use App\Http\Requests\ElectricityPurchaseRequest;
use App\Models\BillTransaction;
use App\Models\ElectricityToken;
use App\Services\Redbiller\ElectricityService;
use App\Support\RedbillerMap;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ElectricityController extends Controller
{
    public function __construct(private ElectricityService $svc) {}

    public function validateCustomer(ElectricityValidateRequest $r)
    {
        $payload = $r->validated();
        $disco = RedbillerMap::electricityDisco($payload['disco']);
        if (!$disco) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported electricity provider.',
            ], 422);
        }

        $res = $this->svc->validateMeter([
            'disco'    => $disco,
            'meter_no' => $payload['meter_no'],
            'type'     => $payload['type'],
        ]);

        $ok = $res['ok'] ?? false;
        $body = $res['json'] ?? ['raw' => $res['body'] ?? null];

        return response()->json([
            'ok'       => $ok,
            'status'   => $res['status'] ?? null,
            'message'  => $body['message']
                ?? ($body['details']['message'] ?? null)
                ?? ($ok ? 'Meter validated successfully.' : 'Unable to validate meter.'),
            'customer' => [
                'name'    => $body['customer_name'] ?? null,
                'address' => $body['address'] ?? $body['customer_address'] ?? null,
            ],
        ], $ok ? 200 : 422);
    }

    public function purchase(ElectricityPurchaseRequest $r)
    {
        $data = $r->validated();
        $disco = RedbillerMap::electricityDisco($data['disco']);
        if (!$disco) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported electricity provider.',
            ], 422);
        }

        $user = $r->user();
        if (!$user?->pin || !Hash::check($data['pin'], $user->pin)) {
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        $reference = $data['reference'] ?? Str::ulid()->toBase32();

        $requestPayload = [
            'disco'     => $disco,
            'meter_no'  => $data['meter_no'],
            'type'      => $data['type'],
            'amount'    => (int) $data['amount'],
            'reference' => $reference,
        ];
        if (!empty($data['callback_url'])) {
            $requestPayload['callback_url'] = $data['callback_url'];
        }

        $tx = BillTransaction::create([
            'reference'       => $reference,
            'service'         => 'electricity',
            'product'         => $disco,
            'account'         => $data['meter_no'],
            'amount'          => (int) $data['amount'],
            'currency'        => 'NGN',
            'provider'        => 'redbiller',
            'status'          => BillTransaction::S_PENDING,
            'request_payload' => array_merge($requestPayload, [
                'payment_source' => 'fiat_balance',
            ]),
            'meta'            => [
                'payment_source' => 'fiat_balance',
                'pin_hash'       => Hash::make($data['pin']),
                'meter_type'     => $data['type'],
            ],
        ]);

        $res = $this->svc->purchase(array_merge($requestPayload, [
            'callback_url' => $requestPayload['callback_url'] ?? null,
        ]));

        $response = $res['response'] ?? [];
        $ok = $response['ok'] ?? false;
        $body = $response['json'] ?? ['raw' => $response['body'] ?? null];

        $tx->update([
            'status'            => $ok ? strtoupper($body['status'] ?? 'PENDING') : BillTransaction::S_FAILED,
            'provider_txn_id'   => $body['id'] ?? $tx->provider_txn_id,
            'provider_response' => $body,
            'customer_name'     => $body['customer_name'] ?? $tx->customer_name,
            'amount_paid'       => (int) ($body['amount_paid'] ?? $tx->amount_paid),
            'fee'               => (int) ($body['fee'] ?? $tx->fee),
            'cost'              => (int) ($body['amount_debited'] ?? $tx->cost),
        ]);

        $tokens = [];
        $tokenPayload = $body['tokens'] ?? $body['data']['tokens'] ?? null;
        if (is_array($tokenPayload)) {
            foreach ($tokenPayload as $tk) {
                $tokenValue = $tk['token'] ?? ($tk['pin'] ?? null);
                if (!$tokenValue) {
                    continue;
                }
                $record = ElectricityToken::firstOrCreate([
                    'bill_transaction_id' => $tx->id,
                    'token'               => $tokenValue,
                ], [
                    'units'       => isset($tk['units']) ? (int) $tk['units'] : null,
                    'tariff_code' => $tk['tariff'] ?? ($tk['tariff_code'] ?? null),
                    'raw'         => $tk,
                ]);
                $tokens[] = [
                    'token'  => $record->token,
                    'units'  => $record->units,
                    'tariff' => $record->tariff_code,
                ];
            }
        }

        return response()->json([
            'reference' => $reference,
            'status'    => $tx->status,
            'provider'  => [
                'ok'     => $ok,
                'status' => $response['status'] ?? null,
            ],
            'message'   => $body['message']
                ?? ($body['details']['message'] ?? null)
                ?? 'Electricity purchase request submitted successfully.',
            'customer'  => [
                'name'    => $tx->customer_name,
                'account' => $tx->account,
            ],
            'tokens'    => $tokens,
        ], $ok ? 200 : 422);
    }

    public function status(string $reference)
    {
        $res = $this->svc->status($reference);
        return response()->json($res, $res['ok'] ? 200 : 422);
    }
}
