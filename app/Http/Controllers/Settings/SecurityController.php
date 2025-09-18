<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SecurityController
{
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return back()->with('flash', ['success' => 'Password changed successfully.']);
    }

    public function resetPin(Request $request)
    {
        $request->validate([
            'current_pin' => ['required', 'digits_between:4,6'],
            'pin'         => ['required', 'digits_between:4,6', 'confirmed'],
        ]);

        $user = $request->user();

        // If you already store hashed pin, verify current:
        if ($user->pin && ! Hash::check($request->current_pin, $user->pin)) {
            return back()->withErrors(['current_pin' => 'Current PIN is incorrect.']);
        }

        $user->forceFill([
            'pin' => Hash::make($request->pin),
        ])->save();

        return back()->with('flash', ['success' => 'PIN reset successfully.']);
    }

    public function updateTwoFactor(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->update(['two_factor_enabled' => $data['enabled']]);

        return back()->with('flash', ['success' => '2FA setting updated.']);
    }

    public function updateTrustedDevice(Request $request)
    {
        $data = $request->validate([
            'trusted' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->update(['trusted_device' => $data['trusted']]);

        return back()->with('flash', ['success' => 'Trusted device updated.']);
    }
}
