<?php

namespace App\Support;

final class NetworkMap
{
    // Redbiller expects these exact labels in examples
    private const MAP = [
        'mtn'      => 'MTN',
        'airtel'   => 'Airtel',
        'glo'      => 'Glo',
        '9mobile'  => '9mobile',
        'etisalat' => '9mobile', // just in case
    ];

    public static function toProduct(string $network): string
    {
        $key = strtolower($network);
        return self::MAP[$key] ?? strtoupper($network);
    }
}
