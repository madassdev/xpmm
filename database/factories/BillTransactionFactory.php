<?php

namespace Database\Factories;

use App\Models\BillTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BillTransactionFactory extends Factory
{
    protected $model = BillTransaction::class;

    public function definition(): array
    {
        $ref = Str::ulid()->toBase32();

        return [
            'reference'        => $ref,
            'service'          => 'airtime',
            'product'          => $this->faker->randomElement(['MTN','Airtel','Glo','9mobile']),
            'network'          => null,
            'phone'            => '080'.mt_rand(10000000, 99999999),
            'ported'           => false,
            'amount'           => 50000, // ₦500 in kobo
            'provider'         => 'redbiller',
            'status'           => BillTransaction::S_PENDING,
            'request_payload'  => ['reference' => $ref],
        ];
    }

    public function success(): self
    {
        return $this->state(fn() => [
            'status'  => BillTransaction::S_SUCCESS,
            'paid_at' => now(),
        ]);
    }
}
