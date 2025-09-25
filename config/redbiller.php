<?php

return [
    /*
     |------------------------------------------------------------
     | Environment & Auth
     |------------------------------------------------------------
     | REDBILLER_ENV: test | live
     */
    'env' => env('REDBILLER_ENV', 'test'),
    'private_key' => env('REDBILLER_PRIVATE_KEY', ''),

    'hosts' => [
        'live' => 'https://api.live.redbiller.com',
        'test' => 'https://api.test.redbiller.com',
    ],

    'http' => [
        'timeout' => (int) env('REDBILLER_TIMEOUT', 25),
        'retries' => (int) env('REDBILLER_RETRIES', 2),
    ],

    /*
     |------------------------------------------------------------
     | Versions per product area (from the PDF)
     |------------------------------------------------------------
     */
    'versions' => [
        'airtime'    => '1.0', // purchase/create, status, list, retry, retried-trail
        'airtime_pin' => '1.3', // if you use pins later
        'data'       => '1.0', // plans/list, purchase/status, purchase/list
        'electricity' => '1.0',
    ],

    /*
     |------------------------------------------------------------
     | Endpoints
     |------------------------------------------------------------
     | We build full paths dynamically with chosen version.
     */
    'endpoints' => [
        'airtime' => [
            'purchase_create'  => '/{v}/bills/airtime/purchase/create',
            'purchase_status'  => '/{v}/bills/airtime/purchase/status',
            'purchase_list'    => '/{v}/bills/airtime/purchase/list',
            'purchase_retry'   => '/{v}/bills/airtime/purchase/retry',
            'retried_trail'    => '/{v}/bills/airtime/purchase/get-retried-trail',
        ],
        'data' => [
            'plans_list'       => '/{v}/bills/data/plans/list',
            'purchase_status'  => '/{v}/bills/data/plans/purchase/status',
            'purchase_list'    => '/{v}/bills/data/plans/purchase/list',
            // NOTE: create endpoint exists in docs but was on another page;
            // wire it later when you confirm exact path (likely /{v}/bills/data/plans/purchase/create)
            'purchase_create'  => '/{v}/bills/data/plans/purchase/create',
        ],
        'electricity' => [
            'validate'        => '/{v}/bills/electricity/validate',
            'purchase_create' => '/{v}/bills/electricity/purchase/create',
            'purchase_status' => '/{v}/bills/electricity/purchase/status',
            'purchase_list'   => '/{v}/bills/electricity/purchase/list',
        ],
        'cable' => [
            'validate'        => '/{v}/bills/cable/validate',
            'plans_list'       => '/{v}/bills/cable/plans/list',
            'purchase_create' => '/{v}/bills/cable/plans/purchase/create',
            'purchase_status' => '/{v}/bills/cable/purchase/status',
            'purchase_list'   => '/{v}/bills/cable/purchase/list',
        ],
    ],
];
