<?php

namespace App\Http\Controllers\Settings;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class SettingsController
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Index', [
            'userMeta' => [
                'name'    => $user->name,
                'email'   => $user->email,
                'phone'   => $user->phone,
                'username'=> $user->username,
                'avatar'  => $user->avatar_path,
                'twoFA'   => (bool) $user->two_factor_enabled,
                'trusted' => (bool) $user->trusted_device,
                'balance' => 0, // fetch from your wallet service if available
            ],
        ]);
    }
}
