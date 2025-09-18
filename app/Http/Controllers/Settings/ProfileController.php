<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users','email')->ignore($user->id)],
            'phone'    => ['nullable', 'string', 'max:30'],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users','username')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('flash', ['success' => 'Profile updated.']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');

        // delete old avatar if you want
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $path]);

        return back()->with('flash', ['success' => 'Avatar updated.']);
    }
}
