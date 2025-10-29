<?php

namespace Tests\Feature\Bills;

use App\Models\BillTransaction;
use App\Models\User;
use App\Services\Redbiller\BillsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class CableControllerTest extends TestCase
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

    public function test_it_lists_plans_for_supported_provider(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('getCablePlansList')
            ->once()
            ->with('DSTV')
            ->andReturn([
                ['id' => 'dstv-padi', 'name' => 'DSTV Padi', 'price' => 2500],
            ]);
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/bills/tv/plans?provider=dstv');

        $response
            ->assertOk()
            ->assertJson([
                'ok'      => true,
                'product' => 'DSTV',
            ])
            ->assertJsonFragment(['id' => 'dstv-padi']);
    }

    public function test_it_validates_smartcard(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('cableValidate')
            ->once()
            ->with('DSTV', '1234567890')
            ->andReturn([
                'ok'   => true,
                'json' => [
                    'customer_name' => 'John Doe',
                    'message'       => 'Valid',
                ],
                'status' => 200,
            ]);
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/tv/validate', [
            'provider'  => 'dstv',
            'smartcard' => '1234567890',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'ok'       => true,
                'customer' => [
                    'name' => 'John Doe',
                ],
            ]);
    }

    public function test_purchase_requires_pin(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('getCablePlanByCode')->never();
        $service->shouldReceive('cablePurchaseCreate')->never();
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin('2468');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/tv', [
            'provider'  => 'dstv',
            'planId'    => 'dstv-padi',
            'smartcard' => '1234567890',
            'pin'       => '0000',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['pin']);
        $this->assertDatabaseCount('bill_transactions', 0);
    }

    public function test_it_processes_purchase_successfully(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('getCablePlanByCode')
            ->once()
            ->with('DSTV', 'dstv-padi')
            ->andReturn([
                'id'    => 'dstv-padi',
                'name'  => 'DSTV Padi',
                'price' => 2500,
            ]);
        $service->shouldReceive('cablePurchaseCreate')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return $payload['product'] === 'DSTV'
                    && $payload['smart_card'] === '1234567890'
                    && $payload['plan'] === 'dstv-padi'
                    && $payload['reference'] === 'REF-TV-1';
            }))
            ->andReturn([
                'response' => [
                    'ok'   => true,
                    'json' => [
                        'status'        => 'SUCCESS',
                        'id'            => 'RB-TV-100',
                        'message'       => 'Approved',
                        'customer_name' => 'John Doe',
                    ],
                    'status' => 200,
                ],
            ]);
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin('2468');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/tv', [
            'provider'  => 'dstv',
            'planId'    => 'dstv-padi',
            'smartcard' => '1234567890',
            'phone'     => '08012345678',
            'reference' => 'REF-TV-1',
            'pin'       => '2468',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'reference' => 'REF-TV-1',
                'status'    => 'SUCCESS',
                'customer'  => [
                    'name'    => 'John Doe',
                    'account' => '1234567890',
                ],
            ]);

        $transaction = BillTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals('tv', $transaction->service);
        $this->assertEquals('DSTV', $transaction->product);
        $this->assertEquals('REF-TV-1', $transaction->reference);
        $this->assertEquals(2500, $transaction->amount);
        $this->assertArrayHasKey('pin_hash', $transaction->meta);
        $this->assertTrue(Hash::check('2468', $transaction->meta['pin_hash']));
    }
}
