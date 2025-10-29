<?php

namespace Tests\Feature\Bills;

use App\Models\BillTransaction;
use App\Models\ElectricityToken;
use App\Models\User;
use App\Services\Redbiller\ElectricityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class ElectricityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function userWithPin(string $pin = '1234'): User
    {
        return User::factory()->create([
            'pin' => Hash::make($pin),
        ]);
    }

    public function test_it_validates_meter_successfully(): void
    {
        $service = Mockery::mock(ElectricityService::class);
        $service->shouldReceive('validateMeter')
            ->once()
            ->with([
                'disco'    => 'AEDC',
                'meter_no' => '0123456789',
                'type'     => 'prepaid',
            ])
            ->andReturn([
                'ok'   => true,
                'json' => [
                    'customer_name' => 'Jane Roe',
                    'address'       => 'Abuja',
                    'message'       => 'Validated',
                ],
                'status' => 200,
            ]);
        $this->app->instance(ElectricityService::class, $service);

        $user = $this->userWithPin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/electricity/validate', [
            'disco'    => 'aedc',
            'meter_no' => '0123456789',
            'type'     => 'prepaid',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'ok'       => true,
                'customer' => [
                    'name'    => 'Jane Roe',
                    'address' => 'Abuja',
                ],
            ]);
    }

    public function test_it_rejects_unsupported_disco_on_validation(): void
    {
        $user = $this->userWithPin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/electricity/validate', [
            'disco'    => 'unknown-disco',
            'meter_no' => '000111',
            'type'     => 'prepaid',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'ok'      => false,
                'message' => 'Unsupported electricity provider.',
            ]);
    }

    public function test_purchase_requires_valid_pin(): void
    {
        $service = Mockery::mock(ElectricityService::class);
        $service->shouldReceive('purchase')->never();
        $this->app->instance(ElectricityService::class, $service);

        $user = $this->userWithPin('1234');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/electricity', [
            'disco'    => 'aedc',
            'meter_no' => '0123456789',
            'type'     => 'prepaid',
            'amount'   => 5000,
            'pin'      => '9999',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['pin']);
        $this->assertDatabaseCount('bill_transactions', 0);
    }

    public function test_it_processes_purchase_and_persists_tokens(): void
    {
        $service = Mockery::mock(ElectricityService::class);
        $service->shouldReceive('purchase')
            ->once()
            ->andReturn([
                'response' => [
                    'ok'   => true,
                    'json' => [
                        'status'         => 'SUCCESS',
                        'id'             => 'RB123',
                        'message'        => 'Approved',
                        'customer_name'  => 'Jane Roe',
                        'amount_paid'    => 5200,
                        'fee'            => 50,
                        'amount_debited' => 5150,
                        'tokens'         => [
                            ['token' => '123456789012', 'units' => 100],
                        ],
                    ],
                    'status' => 200,
                ],
            ]);
        $this->app->instance(ElectricityService::class, $service);

        $user = $this->userWithPin('1234');

        $payload = [
            'disco'     => 'aedc',
            'meter_no'  => '0123456789',
            'type'      => 'prepaid',
            'amount'    => 5200,
            'pin'       => '1234',
            'reference' => 'REF-123',
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/electricity', $payload);

        $response
            ->assertOk()
            ->assertJson([
                'reference' => 'REF-123',
                'status'    => 'SUCCESS',
                'customer'  => [
                    'name'    => 'Jane Roe',
                    'account' => '0123456789',
                ],
            ])
            ->assertJsonFragment([
                'token' => '123456789012',
                'units' => 100,
            ]);

        $transaction = BillTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals('REF-123', $transaction->reference);
        $this->assertEquals('AEDC', $transaction->product);
        $this->assertEquals('SUCCESS', $transaction->status);
        $this->assertEquals('electricity', $transaction->service);
        $this->assertEquals(5200, $transaction->amount);
        $this->assertArrayHasKey('pin_hash', $transaction->meta);
        $this->assertTrue(Hash::check('1234', $transaction->meta['pin_hash']));

        $this->assertEquals(1, ElectricityToken::count());
        $token = ElectricityToken::first();
        $this->assertEquals('123456789012', $token->token);
        $this->assertEquals(100, $token->units);
    }
}
