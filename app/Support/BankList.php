<?php

namespace App\Support;

use Illuminate\Support\Collection;

class BankList
{
    /**
     * @return array<int, array{code: string, name: string}>
     */
    public static function all(): array
    {
        return [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '023', 'name' => 'Citibank Nigeria'],
            ['code' => '050', 'name' => 'Ecobank Nigeria'],
            ['code' => '011', 'name' => 'First Bank of Nigeria'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank'],
            ['code' => '063', 'name' => 'Access Bank (Diamond)'],
            ['code' => '214', 'name' => 'First City Monument Bank'],
            ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
            ['code' => '304', 'name' => 'Stanbic Mobile'],
            ['code' => '305', 'name' => 'Paycom'],
            ['code' => '070', 'name' => 'Fidelity Bank'],
            ['code' => '232', 'name' => 'Sterling Bank'],
            ['code' => '033', 'name' => 'United Bank for Africa'],
            ['code' => '032', 'name' => 'Union Bank of Nigeria'],
            ['code' => '035', 'name' => 'Wema Bank'],
            ['code' => '057', 'name' => 'Zenith Bank'],
        ];
    }

    public static function collect(): Collection
    {
        return collect(self::all());
    }

    public static function codes(): array
    {
        return self::collect()->pluck('code')->all();
    }

    /**
     * @return array{code: string, name: string}|null
     */
    public static function find(string $code): ?array
    {
        return self::collect()->firstWhere('code', $code);
    }
}
