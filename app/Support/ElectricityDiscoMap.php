<?php

namespace App\Support;

final class ElectricityDiscoMap
{
    /**
     * Map a variety of front-end identifiers to the provider disco codes expected by Redbiller.
     */
    private const MAP = [
        'abuja-electricity' => 'AEDC',
        'abuja'             => 'AEDC',
        'aedc'              => 'AEDC',
        'ikeja-electricity' => 'IKEDC',
        'ikeja'             => 'IKEDC',
        'ikedc'             => 'IKEDC',
        'eko-electricity'   => 'EKEDC',
        'eko'               => 'EKEDC',
        'ekedc'             => 'EKEDC',
        'ibadan-electricity'=> 'IBEDC',
        'ibadan'            => 'IBEDC',
        'ibedc'             => 'IBEDC',
        'benin-electricity' => 'BEDC',
        'benin'             => 'BEDC',
        'bedc'              => 'BEDC',
        'enugu-electricity' => 'EEDC',
        'enugu'             => 'EEDC',
        'eedc'              => 'EEDC',
        'jos-electricity'   => 'JEDC',
        'jos'               => 'JEDC',
        'jedc'              => 'JEDC',
        'kano-electricity'  => 'KEDCO',
        'kano'              => 'KEDCO',
        'kedco'             => 'KEDCO',
        'kaduna-electricity'=> 'KAEDCO',
        'kaduna'            => 'KAEDCO',
        'kaedco'            => 'KAEDCO',
        'port-harcourt-electricity' => 'PHED',
        'portharcourt-electricity'  => 'PHED',
        'port-harcourt'             => 'PHED',
        'portharcourt'              => 'PHED',
        'phed'                      => 'PHED',
    ];

    public static function normalize(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $trimmed;
        }

        $slug = strtolower(str_replace(['_', ' '], '-', $trimmed));
        // Accept ids that carry the meter type suffix, e.g. `abuja-electricity-prepaid`.
        $slug = preg_replace('/-(prepaid|postpaid)$/', '', $slug);
        $slug = $slug ?? '';

        return self::MAP[$slug] ?? strtoupper($trimmed);
    }
}
