<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\AirtimePurchaseRequest;
use App\Services\Redbiller\BillsService;
use App\Support\NetworkMap;
use App\Models\BillTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AirtimeController extends Controller
{
    public function __construct(private BillsService $bills) {}

    public function purchase(AirtimePurchaseRequest $r)
    {
        // 1) Validated FE payload
        $data = $r->validated();
        $user = $r->user();
        if (! $user->pin || ! Hash::check($data['pin'], $user->pin)) {
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        // 2) Transform to Redbiller structure (DO NOT include payment source/pin)
        $product = NetworkMap::toProduct($data['network']);

        $reference = $data['reference'] ?? Str::ulid()->toBase32();

        $redbillerPayload = [
            'product'   => $product,
            'phone_no'  => $data['phone'],
            'amount'    => (int) $data['amount'],
            'reference' => $reference,
        ];
        if (array_key_exists('ported', $data)) {
            // Redbiller samples often show string "true"/"false"
            $redbillerPayload['ported'] = $data['ported'] ? 'true' : 'false';
        }
        if (!empty($data['callback_url'])) {
            $redbillerPayload['callback_url'] = $data['callback_url'];
        }

        // 3) Persist local intent first (for audit & idempotency)
        //    Store payment source & a hashed pin in meta. Never store raw PIN.
        $tx = BillTransaction::create([
            'reference'         => $reference,
            'service'           => 'airtime',
            'product'           => $product,
            'network'           => $data['network'],
            'phone'             => $data['phone'],
            'amount'            => (int) $data['amount'],
            'provider'          => 'redbiller',
            'status'            => BillTransaction::S_PENDING,
            'request_payload'   => $redbillerPayload, // what we’ll send out
            'meta'              => [
                'payment_source' => 'fiat_balance',
                'pin_hash' => Hash::make($data['pin']), // never log or return
            ],
        ]);

        // 4) Call provider via BillsService (uses the redbiller payload)
        $res = $this->bills->airtimePurchaseCreate([
            // support both variants inside service; we pass canonical keys
            'product'      => $redbillerPayload['product'],
            'phone_no'     => $redbillerPayload['phone_no'],
            'amount'       => $redbillerPayload['amount'],
            'reference'    => $reference,
            'ported'       => $redbillerPayload['ported'] ?? null,
            'callback_url' => $redbillerPayload['callback_url'] ?? null,
            // nothing else; payment source and pin stay internal
        ]);

        // 5) Update local txn from provider response
        $ok = $res['response']['ok'] ?? false;
        $body = $res['response']['json'] ?? ['raw' => $res['response']['body'] ?? null];

        $tx->update([
            'status'            => $ok ? strtoupper($body['status'] ?? 'PENDING') : BillTransaction::S_FAILED,
            'provider_txn_id'   => $body['id'] ?? $tx->provider_txn_id,
            'provider_response' => $body,
        ]);

        // 6) Return safe response (never return pin or pin_hash)
        return response()->json([
            'reference' => $reference,
            'status'    => $tx->status,
            'provider'  => [
                'ok'     => $ok,
                'status' => $res['response']['status'] ?? null,
            ],
        ], $ok ? 200 : 422);
    }
}
