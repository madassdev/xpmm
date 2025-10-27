<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftcardTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GiftcardTransactionController extends Controller
{
    public function index(): Response
    {
        $transactions = GiftcardTransaction::with(['user', 'giftcard'])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(function (GiftcardTransaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'user_name' => $transaction->user->name ?? 'N/A',
                    'user_email' => $transaction->user->email ?? 'N/A',
                    'giftcard_name' => $transaction->giftcard->name ?? 'N/A',
                    'type' => $transaction->type,
                    'type_label' => ucfirst($transaction->type),
                    'currency' => $transaction->currency,
                    'card_type' => $transaction->card_type,
                    'amount' => $transaction->amount / 100,
                    'quantity' => $transaction->quantity,
                    'payment_method' => $transaction->payment_method,
                    'status' => $transaction->status,
                    'status_label' => ucfirst($transaction->status),
                    'images' => $transaction->images ?? [],
                    'amount_received' => $transaction->amount_received ? $transaction->amount_received / 100 : null,
                    'created_at' => $transaction->created_at?->toIso8601String(),
                    'completed_at' => $transaction->completed_at?->toIso8601String(),
                ];
            });

        $stats = [
            'total' => GiftcardTransaction::count(),
            'pending' => GiftcardTransaction::where('status', GiftcardTransaction::STATUS_PENDING)->count(),
            'completed' => GiftcardTransaction::where('status', GiftcardTransaction::STATUS_COMPLETED)->count(),
            'rejected' => GiftcardTransaction::where('status', GiftcardTransaction::STATUS_REJECTED)->count(),
            'total_buy' => GiftcardTransaction::where('type', GiftcardTransaction::TYPE_BUY)->count(),
            'total_sell' => GiftcardTransaction::where('type', GiftcardTransaction::TYPE_SELL)->count(),
        ];

        return Inertia::render('Admin/GiftcardTransactions/Index', [
            'transactions' => $transactions,
            'stats' => $stats,
        ]);
    }

    public function approve(Request $request, GiftcardTransaction $transaction): JsonResponse
    {
        $transaction->update([
            'status' => GiftcardTransaction::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Transaction approved successfully',
        ]);
    }

    public function reject(Request $request, GiftcardTransaction $transaction): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $transaction->update([
            'status' => GiftcardTransaction::STATUS_REJECTED,
            'notes' => $request->input('reason'),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Transaction rejected successfully',
        ]);
    }
}
