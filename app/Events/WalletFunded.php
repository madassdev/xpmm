<?php

namespace App\Events;

use App\Models\WalletTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletFunded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public WalletTransaction $transaction)
    {
    }
}
