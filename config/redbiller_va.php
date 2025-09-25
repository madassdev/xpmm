<?php

return [
    'env'         => env('REDBILLER_ENV', 'test'),
    'private_key' => env('REDBILLER_PRIVATE_KEY', ''),

    'hosts' => [
        'live' => 'https://api.live.redbiller.com',
        'test' => 'https://api.test.redbiller.com',
    ],

    // Version the wallet API separately from bills
    'version' => env('REDBILLER_WALLETS_VERSION', '1.0'),

    // ENDPOINTS — edit these strings to match the exact PDF paths
    'endpoints' => [
        'create_va' => '/{v}/wallet/virtual-account/create',
        'get_va'    => '/{v}/wallet/virtual-account/get',
        'list_txn'  => '/{v}/wallet/transactions/list',     // for reconciliation (optional)
    ],

    // REQUEST PAYLOAD MAPPING — if doc uses different keys, fix here not in code
    'payload' => [
        'create_va' => [
            // left flexible: fill what your PDF requires
            // e.g. 'reference', 'customer' => ['name','email','phone']
        ],
    ],

    // RESPONSE MAPPING — define the JSON paths we extract from provider response
    'response' => [
        'va' => [
            'account_number' => 'data.account_number', // dot-notation path
            'bank_name'      => 'data.bank_name',
        ],
    ],

    'http' => [
        'timeout' => (int) env('REDBILLER_TIMEOUT', 25),
        'retries' => (int) env('REDBILLER_RETRIES', 2),
    ],

    // Webhook signature verification (toggle once you confirm headers from doc)
    'webhook' => [
        'verify'   => env('REDBILLER_WALLETS_VERIFY_SIG', false),
        'sig_hdr'  => env('REDBILLER_WALLETS_SIG_HEADER', 'X-Signature'),
        'ts_hdr'   => env('REDBILLER_WALLETS_TS_HEADER', 'X-Signature-Timestamp'),
        'algo'     => env('REDBILLER_WALLETS_SIG_ALGO', 'sha256'),
        'secret'   => env('REDBILLER_WALLETS_WEBHOOK_SECRET', ''),
        'skew_sec' => (int) env('REDBILLER_WALLETS_TS_SKEW', 300),
    ],
];
