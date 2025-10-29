<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternetPurchaseRequest;
use App\Models\BillTransaction;
use App\Services\Redbiller\BillsService;
use App\Support\RedbillerMap;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InternetController extends Controller
{
    public function __construct(private BillsService $bills) {}

    public function plans(Request $r)
    {
        $data = $r->validate([
            'provider' => 'required|string|max:50',
        ]);

        $product = RedbillerMap::internetProduct($data['provider']);
        if (!$product) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported internet provider.',
            ], 422);
        }

        try {
            $plans = $this->bills->getInternetPlansList($product);
        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok'       => true,
            'provider' => $data['provider'],
            'product'  => $product,
            'plans'    => $plans,
        ]);
    }

    public function purchase(InternetPurchaseRequest $r)
    {
        $data = $r->validated();
        $product = RedbillerMap::internetProduct($data['provider']);
        if (!$product) {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported internet provider.',
            ], 422);
        }

        $user = $r->user();
        if (!$user?->pin || !Hash::check($data['pin'], $user->pin)) {
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        $plan = $this->bills->getInternetPlanByCode($product, $data['planId']);
        if (!$plan) {
            return response()->json([
                'ok' => false,
                'message' => 'Plan not found for provider.',
            ], 404);
        }

        $reference = $data['reference'] ?? Str::ulid()->toBase32();
        $amount = (int) ($plan['price'] ?? 0);

        $requestPayload = [
            'product'   => $product,
            'plan'      => $data['planId'],
            'amount'    => $amount,
            'account'   => $data['account'],
            'reference' => $reference,
            'callback_url' => $data['callback_url'] ?? null,
        ];

        $tx = BillTransaction::create([
            'reference'         => $reference,
            'service'           => 'internet',
            'product'           => $product,
            'account'           => $data['account'],
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

        $res = $this->bills->internetPurchaseCreate($requestPayload);
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
                ?? 'Internet purchase request submitted successfully.',
            'plan'      => [
                'name'  => $plan['name'] ?? null,
                'price' => $amount,
            ],
        ], $ok ? 200 : 422);
    }
}
