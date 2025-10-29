<?php

namespace Tests\Feature\Bills;

use App\Models\BillTransaction;
use App\Models\User;
use App\Services\Redbiller\BillsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class BettingControllerTest extends TestCase
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

    public function test_it_rejects_unsupported_provider(): void
    {
        $user = $this->userWithPin();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/betting', [
            'provider' => 'unknown',
            'account'  => 'ACC123',
            'amount'   => 3000,
            'pin'      => '1234',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'ok'      => false,
                'message' => 'Unsupported betting provider.',
            ]);
    }

    public function test_purchase_requires_correct_pin(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('bettingPurchaseCreate')->never();
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin('1234');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/betting', [
            'provider' => 'bet9ja',
            'account'  => 'ACC123',
            'amount'   => 3000,
            'pin'      => '0000',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['pin']);
        $this->assertDatabaseCount('bill_transactions', 0);
    }

    public function test_it_processes_betting_purchase(): void
    {
        $service = Mockery::mock(BillsService::class);
        $service->shouldReceive('bettingPurchaseCreate')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return $payload['product'] === 'BET9JA'
                    && $payload['account'] === 'ACC123'
                    && $payload['amount'] === 3000
                    && $payload['reference'] === 'BET-REF-1';
            }))
            ->andReturn([
                'response' => [
                    'ok'   => true,
                    'json' => [
                        'status'  => 'SUCCESS',
                        'id'      => 'BET-200',
                        'message' => 'Approved',
                    ],
                    'status' => 200,
                ],
            ]);
        $this->app->instance(BillsService::class, $service);

        $user = $this->userWithPin('5555');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/bills/betting', [
            'provider'  => 'bet9ja',
            'account'   => 'ACC123',
            'amount'    => 3000,
            'reference' => 'BET-REF-1',
            'pin'       => '5555',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'reference' => 'BET-REF-1',
                'status'    => 'SUCCESS',
            ]);

        $transaction = BillTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals('betting', $transaction->service);
        $this->assertEquals('BET9JA', $transaction->product);
        $this->assertEquals('BET-REF-1', $transaction->reference);
        $this->assertEquals(3000, $transaction->amount);
        $this->assertArrayHasKey('pin_hash', $transaction->meta);
        $this->assertTrue(Hash::check('5555', $transaction->meta['pin_hash']));
    }
}
