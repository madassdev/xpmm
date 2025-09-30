<?php

namespace Tests\Feature\Services\Redbiller;

use App\Models\BillTransaction;
use App\Services\Redbiller\ElectricityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ElectricityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_persists_transaction_and_tokens()
    {
        Http::fake([
            '*' => Http::response([
                'status'         => 'SUCCESS',
                'id'             => 'RB123456',
                'customer_name'  => 'Jane Doe',
                'amount_debited' => 5200,
                'amount_paid'    => 5000,
                'fee'            => 200,
                'currency'       => 'NGN',
                'tokens'         => [
                    [
                        'token'  => '123456789012',
                        'units'  => 43,
                        'tariff' => 'A1',
                    ],
                ],
            ], 200),
        ]);

        $service = app(ElectricityService::class);

        $result = $service->purchase([
            'disco'    => 'IKEDC',
            'meter_no' => '12345678901',
            'type'     => 'prepaid',
            'amount'   => 5000,
        ]);

        $this->assertTrue($result['response']['ok']);
        $this->assertEquals('SUCCESS', $result['transaction']['status']);
        $this->assertSame('Jane Doe', $result['transaction']['customer_name']);
        $this->assertSame('NGN', $result['transaction']['currency']);
        $this->assertCount(1, $result['transaction']['tokens']);

        $tx = BillTransaction::where('reference', $result['reference'])->first();
        $this->assertNotNull($tx);
        $this->assertEquals(BillTransaction::S_SUCCESS, $tx->status);
        $this->assertEquals('electricity', $tx->service);
        $this->assertEquals('IKEDC', $tx->product);
        $this->assertEquals('12345678901', $tx->account);
        $this->assertEquals(5000, $tx->amount);
        $this->assertEquals(5200, $tx->cost);
        $this->assertEquals(200, $tx->fee);
        $this->assertEquals('Jane Doe', $tx->customer_name);
        $this->assertNotNull($tx->paid_at);
        $this->assertCount(1, $tx->electricityTokens);
        $this->assertEquals('123456789012', $tx->electricityTokens->first()->token);
    }
}
