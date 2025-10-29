<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataPurchaseRequest;
use App\Services\Redbiller\BillsService;
use App\Support\NetworkMap;
use App\Models\BillTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileDataController extends Controller
{
    public function __construct(private BillsService $bills) {}

    public function getPlans(Request $r)
    {
        $r->validate([
            'network' => 'required|string|in:mtn,airtel,glo,9mobile',
        ]);

        $network = strtolower($r->query('network'));
        $product = NetworkMap::toProduct($network); // e.g. "MTN"
        try {
            $plans = $this->bills->getDataPlansList($product);
        } catch (Exception $err) {
            return response()->json([
                'ok' => false,
                'message' => $err->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok'        => true,
            'network'   => $network,
            'product'   => $product,
            'plans'     => $plans,
            'raw'       => ['count' => count($plans)], // small hint; not leaking the whole payload
        ]);
    }
    public function purchase(DataPurchaseRequest $r)
    {
        // 1) Validated FE payload
        $data = $r->validated();
        $code = $data['planId'];
        $provider = NetworkMap::toProduct($data['provider']);

        $user = $r->user();
        if (!$user?->pin || !Hash::check($data['pin'], $user->pin)) {
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        $reference = $data['reference'] ?? Str::ulid()->toBase32();

        $redbillerPayload = [
            'product'   => $provider,
            'phone_no'  => $data['phone'],
            'code'      => $code,
            'reference' => $reference,
        ];

        if (array_key_exists('ported', $data)) {
            // Redbiller samples often show string "true"/"false"
            $redbillerPayload['ported'] = $data['ported'] ? 'true' : 'false';
        }
        if (!empty($data['callback_url'])) {
            $redbillerPayload['callback_url'] = $data['callback_url'];
        }

        $plan = $this->bills->getDataPlanByCode($provider, $code);
        
        if (!$plan) {
            return response()->json([
                'ok' => false,
                'message' => 'Plan not found',
            ], 404);
        }
        
        $bundle = $plan['name'];
        $amount = (int) $plan['price'];
        $product = "{$provider} - $bundle";
        $redbillerPayload['amount'] = $amount;
        // Store payment source in request payload for reference
        $requestPayload = array_merge($redbillerPayload, [
            'payment_source' => 'fiat_balance',
        ]);

        $tx = BillTransaction::create([
            'reference'         => $reference,
            'service'           => 'data',
            'product'           => $product,
            'network'           => $data['provider'],
            'phone'             => $data['phone'],
            'plan_id'           => $code,
            'amount'            => (int) $amount,
            'provider'          => 'redbiller',
            'status'            => BillTransaction::S_PENDING,
            'request_payload'   => $requestPayload,
            'meta'              => [
                'payment_source' => 'fiat_balance',
                'pin_hash'       => Hash::make($data['pin']),
                'plan_name'      => $bundle,
            ],
        ]);

        // 4) Call provider via BillsService (uses the redbiller payload)
        $res = $this->bills->dataPurchaseCreate([
            // support both variants inside service; we pass canonical keys
            'product'      => $redbillerPayload['product'],
            'phone_no'     => $redbillerPayload['phone_no'],
            'amount'       => $redbillerPayload['amount'],
            'reference'    => $reference,
            'ported'       => $redbillerPayload['ported'] ?? null,
            'callback_url' => $redbillerPayload['callback_url'] ?? null,
            'plan'         => $code,
            // nothing else; payment source and any sensitive data stay internal
        ]);

        // 5) Update local txn from provider response
        $ok = $res['response']['ok'] ?? false;
        $body = $res['response']['json'] ?? ['raw' => $res['response']['body'] ?? null];

        $tx->update([
            'status'            => $ok ? strtoupper($body['status'] ?? 'PENDING') : BillTransaction::S_FAILED,
            'provider_txn_id'   => $body['id'] ?? $tx->provider_txn_id,
            'provider_response' => $body,
            'customer_name'     => $body['customer_name'] ?? $tx->customer_name,
            'amount_paid'       => (int) ($body['amount_paid'] ?? $tx->amount_paid),
            'fee'               => (int) ($body['fee'] ?? $tx->fee),
            'cost'              => (int) ($body['amount_debited'] ?? $tx->cost),
        ]);

        // 6) Return safe response (never return pin or pin_hash)
        return response()->json([
            'reference' => $reference,
            'status'    => $tx->status,
            'provider'  => [
                'ok'     => $ok,
                'status' => $res['response']['status'] ?? null,
            ],
            'message'   => $body['message']
                ?? ($body['details']['message'] ?? null)
                ?? 'Data purchase request submitted successfully.',
        ], $ok ? 200 : 422);
    }
}
