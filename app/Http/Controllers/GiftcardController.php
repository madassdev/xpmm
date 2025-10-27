<?php

namespace App\Http\Controllers;

use App\Models\Giftcard;
use App\Models\GiftcardTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GiftcardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $transactions = [];

        if ($user) {
            $transactions = GiftcardTransaction::with('giftcard')
                ->where('user_id', $user->id)
                ->latest()
                ->take(25)
                ->get()
                ->map(function (GiftcardTransaction $transaction) {
                    return [
                        'id' => $transaction->id,
                        'date' => $transaction->created_at?->format('d M, Y'),
                        'giftcard' => $transaction->giftcard->name ?? 'Giftcard',
                        'type' => ucfirst($transaction->type),
                        'type_raw' => $transaction->type,
                        'currency' => $transaction->currency,
                        'amount' => $transaction->amount / 100,
                        'quantity' => $transaction->quantity,
                        'status' => $transaction->status,
                        'status_label' => ucfirst($transaction->status),
                        'payment_method' => $transaction->payment_method,
                        'card_type' => $transaction->card_type,
                        'amount_received' => $transaction->amount_received ? $transaction->amount_received / 100 : null,
                        'images' => $transaction->images ?? [],
                        'notes' => $transaction->notes,
                        'created_at' => $transaction->created_at?->toIso8601String(),
                    ];
                })
                ->values();
        }

        return inertia()->render('Giftcards/Index', [
            'giftcards' => Giftcard::all(),
            'transactions' => $transactions,
        ]);
    }

    public function list()
    {
        $giftcards = Giftcard::select('id', 'name', 'rate', 'available_regions', 'available_values', 'image_path')
            ->get()
            ->map(function ($giftcard) {
                return [
                    'id' => $giftcard->id,
                    'name' => $giftcard->name,
                    'rate' => $giftcard->rate,
                    'available_regions' => $giftcard->available_regions ?? [],
                    'available_values' => $giftcard->available_values ?? [],
                    'logo' => $giftcard->image_url,
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $giftcards,
        ]);
    }

    public function sell(Request $request)
    {
        $validated = $request->validate([
            'giftcard_id' => 'required|exists:giftcards,id',
            'currency' => 'required|in:NGN,USD,EUR',
            'type' => 'required|in:e-card,physical',
            'amount' => 'required|numeric|min:1',
            'quantity' => 'required|integer|min:1|max:10',
            'method' => 'required|in:NGN,BTC,USDT',
            'images' => 'nullable|array|max:6',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $imagePaths = [];
        $images = $request->file('images', []);
        if (is_array($images)) {
            foreach ($images as $image) {
                if ($image && $image->isValid()) {
                    $path = $image->store('giftcards/transactions', 'public');
                    $imagePaths[] = $path;
                }
            }
        }

        $amountInCents = (int) ($validated['amount'] * 100);

        $transaction = GiftcardTransaction::create([
            'user_id' => Auth::id(),
            'giftcard_id' => $validated['giftcard_id'],
            'type' => GiftcardTransaction::TYPE_SELL,
            'currency' => $validated['currency'],
            'card_type' => $validated['type'],
            'amount' => $amountInCents,
            'quantity' => $validated['quantity'],
            'payment_method' => $validated['method'],
            'status' => GiftcardTransaction::STATUS_PENDING,
            'images' => $imagePaths,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Giftcard sell request submitted successfully',
            'transaction_id' => $transaction->id,
        ], 201);
    }

    public function buy(Request $request)
    {
        $validated = $request->validate([
            'giftcard_id' => 'required|exists:giftcards,id',
            'currency' => 'required|in:NGN,USD,EUR',
            'amount' => 'required|numeric|min:1',
            'quantity' => 'required|integer|min:1|max:10',
            'payment_method' => 'required|in:NGN,BTC,USDT',
        ]);

        $giftcard = Giftcard::findOrFail($validated['giftcard_id']);

        if ($giftcard->available_values && !in_array((int) $validated['amount'], $giftcard->available_values)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid amount. Please select from available values.',
            ], 422);
        }

        $amountInCents = (int) ($validated['amount'] * 100);
        $totalCost = $amountInCents * $validated['quantity'] * ($giftcard->rate / 100);

        $transaction = GiftcardTransaction::create([
            'user_id' => Auth::id(),
            'giftcard_id' => $validated['giftcard_id'],
            'type' => GiftcardTransaction::TYPE_BUY,
            'currency' => $validated['currency'],
            'amount' => $amountInCents,
            'quantity' => $validated['quantity'],
            'payment_method' => $validated['payment_method'],
            'status' => GiftcardTransaction::STATUS_PENDING,
            'amount_received' => (int) $totalCost,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Giftcard purchase initiated successfully',
            'transaction_id' => $transaction->id,
            'total_cost' => $totalCost / 100,
        ], 201);
    }
}
