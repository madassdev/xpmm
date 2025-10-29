<?php

namespace App\Support;

final class RedbillerMap
{
    private const ELECTRICITY = [
        'aedc'        => 'AEDC',
        'abuja'       => 'AEDC',
        'ikedc'       => 'IKEDC',
        'ikeja'       => 'IKEDC',
        'ekedc'       => 'EKEDC',
        'eko'         => 'EKEDC',
        'ibedc'       => 'IBEDC',
        'ibadan'      => 'IBEDC',
        'bedc'        => 'BEDC',
        'benin'       => 'BEDC',
        'eedc'        => 'EEDC',
        'enugu'       => 'EEDC',
        'jed'         => 'JED',
        'jedc'        => 'JED',
        'jos'         => 'JED',
        'kedco'       => 'KEDCO',
        'kano'        => 'KEDCO',
        'kaedco'      => 'KAEDCO',
        'kaduna'      => 'KAEDCO',
        'phed'            => 'PHED',
        'portharcourt'    => 'PHED',
        'port-harcourt'   => 'PHED',
    ];

    private const CABLE = [
        'dstv'      => 'DSTV',
        'gotv'      => 'GOTV',
        'startimes' => 'STARTIMES',
    ];

    private const INTERNET = [
        'spectranet' => 'SPECTRANET',
        'smile'      => 'SMILE',
        'swift'      => 'SWIFT',
        'ipnx'       => 'IPNX',
        'fiberone'   => 'FIBERONE',
    ];

    private const BETTING = [
        'bet9ja'   => 'BET9JA',
        'sporty'   => 'SPORTYBET',
        '1xbet'    => '1XBET',
        'betking'  => 'BETKING',
        'nairabet' => 'NAIRABET',
        'msport'   => 'MSPORT',
    ];

    public static function electricityDisco(string $key): ?string
    {
        $key = strtolower(trim($key));
        return self::ELECTRICITY[$key] ?? null;
    }

    public static function cableProduct(string $key): ?string
    {
        $key = strtolower(trim($key));
        return self::CABLE[$key] ?? null;
    }

    public static function internetProduct(string $key): ?string
    {
        $key = strtolower(trim($key));
        return self::INTERNET[$key] ?? null;
    }

    public static function bettingProvider(string $key): ?string
    {
        $key = strtolower(trim($key));
        return self::BETTING[$key] ?? null;
    }
}
