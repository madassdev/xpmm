<?php

namespace App\Http\Controllers;

use App\Models\GiftcardTransaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $giftcardTransactions = $user?->giftcardTransactions()
            ->with('giftcard')
            ->latest()
            ->take(5)
            ->get()
            ->map(function (GiftcardTransaction $transaction) {
                $status = match ($transaction->status) {
                    GiftcardTransaction::STATUS_COMPLETED => 'success',
                    GiftcardTransaction::STATUS_PENDING,
                    GiftcardTransaction::STATUS_PROCESSING => 'pending',
                    default => 'failed',
                };

                return [
                    'id' => $transaction->id,
                    'createdAt' => $transaction->created_at?->format('d M, Y'),
                    'type' => sprintf(
                        '%s %s',
                        ucfirst($transaction->type),
                        $transaction->giftcard->name ?? 'giftcard'
                    ),
                    'amount' => $transaction->amount / 100,
                    'status' => $status,
                    'method' => $transaction->payment_method ?? 'Giftcard',
                ];
            }) ?? collect();

        return inertia()->render('Dashboard/Overview', [
            'giftcardTransactions' => $giftcardTransactions,
        ]);
    }

    public function wallets()
    {
        return inertia()->render('Dashboard/Wallets');
    }

    public function cards()
    {
        return inertia()->render('Dashboard/Cards');
    }

    public function referrals()
    {
        return inertia()->render('Dashboard/Referrals');
    }

    public function transactions()
    {
        return inertia()->render('Dashboard/Transactions');
    }

    public function transfer()
    {
        return inertia()->render('Dashboard/Transfer');
    }

    public function betting()
    {
        return inertia()->render('Dashboard/Betting');
    }
}
