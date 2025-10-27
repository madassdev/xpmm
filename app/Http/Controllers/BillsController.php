<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $fiatBalance = (float) ($user?->fiat_balance ?? 0);

        return inertia()->render('Bills/Index', [
            'fiatBalance' => $fiatBalance,
        ]);
    }
}
