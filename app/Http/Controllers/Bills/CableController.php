<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\CablePurchaseRequest;
use App\Http\Requests\CableValidateRequest;
use App\Models\BillTransaction;
use App\Services\Redbiller\BillsService;
use App\Support\RedbillerMap;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CableController extends Controller
{
    public function __construct(private BillsService $bills) {}

    public function plans(Request $r)
    {
        $data = $r->validate([
            'provider' => 'required|string|max:50',
        ]);

        $providerKey = strtolower(trim($data['provider']));
        $product = RedbillerMap::cableProduct($providerKey);
        if (!$product) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported TV provider.',
            ], 422);
        }

        try {
            $plans = $this->bills->getCablePlansList($product);
        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok'       => true,
            'provider' => $providerKey,
            'product'  => $product,
            'plans'    => $plans,
        ]);
    }

    public function validateCustomer(CableValidateRequest $r)
    {
        $data = $r->validated();
        $product = RedbillerMap::cableProduct($data['provider']);
        if (!$product) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported TV provider.',
            ], 422);
        }

        $res = $this->bills->cableValidate($product, $data['smartcard']);
        $ok = $res['ok'] ?? false;
        $body = $res['json'] ?? ['raw' => $res['body'] ?? null];

        return response()->json([
            'ok'       => $ok,
            'status'   => $res['status'] ?? null,
            'message'  => $body['message']
                ?? ($body['details']['message'] ?? null)
                ?? ($ok ? 'Smartcard validated successfully.' : 'Unable to validate smartcard.'),
            'customer' => [
                'name'    => $body['customer_name'] ?? null,
                'address' => $body['address'] ?? $body['customer_address'] ?? null,
            ],
        ], $ok ? 200 : 422);
    }

    public function purchase(CablePurchaseRequest $r)
    {
        $data = $r->validated();
        $product = RedbillerMap::cableProduct($data['provider']);
        if (!$product) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported TV provider.',
            ], 422);
        }

        $user = $r->user();
        if (!$user?->pin || !Hash::check($data['pin'], $user->pin)) {
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        $plan = $this->bills->getCablePlanByCode($product, $data['planId']);
        if (!$plan) {
            return response()->json([
                'ok' => false,
                'message' => 'Bundle not found for provider.',
            ], 404);
        }

        $reference = $data['reference'] ?? Str::ulid()->toBase32();
        $amount = (int) ($plan['price'] ?? 0);

        $requestPayload = [
            'product'      => $product,
            'smart_card'   => $data['smartcard'],
            'plan'         => $data['planId'],
            'phone'        => $data['phone'] ?? null,
            'amount'       => $amount,
            'reference'    => $reference,
            'callback_url' => $data['callback_url'] ?? null,
        ];

        $tx = BillTransaction::create([
            'reference'         => $reference,
            'service'           => 'tv',
            'product'           => $product,
            'account'           => $data['smartcard'],
            'plan_id'           => $data['planId'],
            'amount'            => $amount,
            'provider'          => 'redbiller',
            'status'            => BillTransaction::S_PENDING,
            'request_payload'   => array_merge($requestPayload, [
                'payment_source' => 'fiat_balance',
            ]),
            'meta'              => [
                'payment_source' => 'fiat_balance',
                'pin_hash'       => Hash::make($data['pin']),
                'plan_name'      => $plan['name'] ?? null,
            ],
        ]);

        $res = $this->bills->cablePurchaseCreate([
            'product'       => $requestPayload['product'],
            'smart_card'    => $requestPayload['smart_card'],
            'plan'          => $requestPayload['plan'],
            'phone_no'      => $requestPayload['phone'],
            'reference'     => $reference,
            'callback_url'  => $requestPayload['callback_url'] ?? null,
        ]);

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

        return response()->json([
            'reference' => $reference,
            'status'    => $tx->status,
            'provider'  => [
                'ok'     => $ok,
                'status' => $response['status'] ?? null,
            ],
            'message'   => $body['message']
                ?? ($body['details']['message'] ?? null)
                ?? 'TV subscription request submitted successfully.',
            'customer'  => [
                'name'    => $tx->customer_name,
                'account' => $tx->account,
            ],
        ], $ok ? 200 : 422);
    }
}
