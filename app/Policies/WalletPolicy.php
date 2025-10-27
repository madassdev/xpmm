<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine whether the user can manage manual wallet funding.
     */
    public function fund(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermissionTo('wallets.fund');
    }
}
