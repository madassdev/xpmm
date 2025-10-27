<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AdminPageController extends Controller
{
    /**
     * Map route keys to inertia page components and metadata.
     *
     * @var array<string, array<string, string>>
     */
    protected array $pages = [
        'overview' => [
            'component' => 'Admin/Overview',
            'title' => 'Overview',
            'description' => 'High-level snapshot of platform performance and activity.',
        ],
        'transactions' => [
            'component' => 'Admin/Transactions',
            'title' => 'Transactions',
            'description' => 'Monitor and audit recent inflows, outflows, and settlements.',
        ],
        'cards' => [
            'component' => 'Admin/Cards',
            'title' => 'Cards',
            'description' => 'Manage card programs, spending controls, and issuer settings.',
        ],
        'bills-management' => [
            'component' => 'Admin/BillsManagement',
            'title' => 'Bills Management',
            'description' => 'Coordinate biller integrations, fulfilment states, and SLAs.',
        ],
        'virtual-cards' => [
            'component' => 'Admin/VirtualCards',
            'title' => 'Virtual Cards',
            'description' => 'Issue, suspend, or configure virtual cards across teams.',
        ],
        'crypto-wallets' => [
            'component' => 'Admin/CryptoWallets',
            'title' => 'Crypto Wallets',
            'description' => 'Review custody balances, wallet health, and liquidity.',
        ],
        'users' => [
            'component' => 'Admin/Users',
            'title' => 'Users',
            'description' => 'Inspect customer cohorts, permissions, and lifecycle events.',
        ],
        'kyc' => [
            'component' => 'Admin/Kyc',
            'title' => 'KYC',
            'description' => 'Oversee identity checks, escalation queues, and screening.',
        ],
        'settings' => [
            'component' => 'Admin/Settings',
            'title' => 'Settings',
            'description' => 'Adjust platform-wide preferences, alerts, and automation.',
        ],
        'giftcard-transactions' => [
            'component' => 'Admin/GiftcardTransactions/Index',
            'title' => 'Giftcard Transactions',
            'description' => 'Review and manage giftcard buy/sell orders.',
        ],
    ];

    public function __invoke(Request $request, string $section = 'overview')
    {
        $section = Str::of($section)->lower()->value();

        if (! Arr::has($this->pages, $section)) {
            abort(404);
        }

        $page = $this->pages[$section];

        return inertia()->render($page['component'], [
            'meta' => [
                'title' => "Admin {$page['title']}",
                'description' => $page['description'],
                'current' => $section,
            ],
        ]);
    }
}
