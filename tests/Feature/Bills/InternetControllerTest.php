<?php

namespace Tests\Feature\Bills;

use App\Models\BillTransaction;
use App\Models\User;
use App\Services\Redbiller\BillsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class InternetControllerTest extends TestCase
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
        $service->shouldReceive('getInternetPlansList')
            ->once()
            ->with('SPECTRANET')
            ->andReturn([
                ['id' => 'plan-1', 'name' => 'Spectranet Mini', 'price' => 1000],
            ]);
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/bills/internet/plans?provider=spectranet');

        $response
            ->assertOk()
            ->assertJson([
                'ok'      => true,
                'product' => 'SPECTRANET',
            ])
            ->assertJsonFragment(['id' => 'plan-1']);
    }

    public function test_it_rejects_unsupported_provider_on_plans(): void
    {
        $user = $this->userWithPin();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/bills/internet/plans?provider=unknown');

        $response
            ->assertStatus(422)
            ->assertJson([
                'ok'      => false,
                'message' => 'Unsupported internet provider.',
            ]);
    }

    public function test_purchase_requires_correct_pin(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('getInternetPlanByCode')->never();
        $service->shouldReceive('internetPurchaseCreate')->never();
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin('1234');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/internet', [
            'provider' => 'spectranet',
            'planId'   => 'plan-1',
            'account'  => 'ACC12345',
            'pin'      => '0000',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['pin']);
        $this->assertDatabaseCount('bill_transactions', 0);
    }

    public function test_it_processes_purchase_successfully(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('getInternetPlanByCode')
            ->once()
            ->with('SPECTRANET', 'plan-1')
            ->andReturn([
                'id'    => 'plan-1',
                'name'  => 'Spectranet Mini',
                'price' => 2500,
            ]);
        $service->shouldReceive('internetPurchaseCreate')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return $payload['product'] === 'SPECTRANET'
                    && $payload['plan'] === 'plan-1'
                    && $payload['amount'] === 2500
                    && $payload['account'] === 'ACC12345'
                    && $payload['reference'] === 'REF-567';
            }))
            ->andReturn([
                'response' => [
                    'ok'   => true,
                    'json' => [
                        'status'  => 'SUCCESS',
                        'id'      => 'RB456',
                        'message' => 'Approved',
                        'amount_paid' => 2500,
                    ],
                    'status' => 200,
                ],
            ]);
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin('9999');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/internet', [
            'provider'   => 'spectranet',
            'planId'     => 'plan-1',
            'account'    => 'ACC12345',
            'reference'  => 'REF-567',
            'pin'        => '9999',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'reference' => 'REF-567',
                'status'    => 'SUCCESS',
                'plan'      => [
                    'name'  => 'Spectranet Mini',
                    'price' => 2500,
                ],
            ]);

        $transaction = BillTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals('internet', $transaction->service);
        $this->assertEquals('SPECTRANET', $transaction->product);
        $this->assertEquals('REF-567', $transaction->reference);
        $this->assertEquals(2500, $transaction->amount);
        $this->assertArrayHasKey('pin_hash', $transaction->meta);
        $this->assertTrue(Hash::check('9999', $transaction->meta['pin_hash']));
    }
}
