<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\BettingPurchaseRequest;
use App\Models\BillTransaction;
use App\Services\Redbiller\BillsService;
use App\Support\RedbillerMap;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BettingController extends Controller
{
    public function __construct(private BillsService $bills) {}

    public function purchase(BettingPurchaseRequest $r)
    {
        $data = $r->validated();
        $product = RedbillerMap::bettingProvider($data['provider']);
        if (!$product) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported betting provider.',
            ], 422);
        }

        $user = $r->user();
        if (!$user?->pin || !Hash::check($data['pin'], $user->pin)) {
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        $reference = $data['reference'] ?? Str::ulid()->toBase32();
        $amount = (int) $data['amount'];

        $requestPayload = [
            'product'      => $product,
            'account'      => $data['account'],
            'amount'       => $amount,
            'reference'    => $reference,
            'callback_url' => $data['callback_url'] ?? null,
        ];

        $tx = BillTransaction::create([
            'reference'         => $reference,
            'service'           => 'betting',
            'product'           => $product,
            'account'           => $data['account'],
            'amount'            => $amount,
            'provider'          => 'redbiller',
            'status'            => BillTransaction::S_PENDING,
            'request_payload'   => array_merge($requestPayload, [
                'payment_source' => 'fiat_balance',
            ]),
            'meta'              => [
                'payment_source' => 'fiat_balance',
                'pin_hash'       => Hash::make($data['pin']),
            ],
        ]);

        $res = $this->bills->bettingPurchaseCreate([
            'product'      => $product,
            'account'      => $data['account'],
            'amount'       => $amount,
            'reference'    => $reference,
            'callback_url' => $data['callback_url'] ?? null,
        ]);

        $response = $res['response'] ?? [];
        $ok = $response['ok'] ?? false;
        $body = $response['json'] ?? ['raw' => $response['body'] ?? null];

        $tx->update([
            'status'            => $ok ? strtoupper($body['status'] ?? 'PENDING') : BillTransaction::S_FAILED,
            'provider_txn_id'   => $body['id'] ?? $tx->provider_txn_id,
            'provider_response' => $body,
            'amount_paid'       => (int) ($body['amount_paid'] ?? $tx->amount_paid),
            'fee'               => (int) ($body['fee'] ?? $tx->fee),
            'cost'              => (int) ($body['amount_debited'] ?? $tx->cost),
        ]);

        return response()->json([
            'reference' => $reference,
            'status'    => $tx->status,
            'provider'  => [
                'ok'     => $ok,
                'status' => $response['status'] ?? null,
            ],
            'message'   => $body['message']
                ?? ($body['details']['message'] ?? null)
                ?? 'Betting wallet top-up request submitted successfully.',
        ], $ok ? 200 : 422);
    }
}
