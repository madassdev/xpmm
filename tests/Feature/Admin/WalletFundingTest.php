<?php

use App\Events\WalletFunded;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createAdminUser(): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $role = Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('allows an admin to fund a wallet', function (): void {
    $admin = createAdminUser();
    $customer = User::factory()->create();

    Event::fake();

    $response = $this->actingAs($admin)->post(route('admin.wallets.fund.store'), [
        'user_id' => $customer->id,
        'amount' => '1500.00',
        'reference' => 'MANUAL-REF-001',
        'description' => 'Manual top up',
    ]);

    $response->assertRedirect(route('admin.wallets.fund'));
    $response->assertSessionHas('status');

    $wallet = Wallet::where('user_id', $customer->id)->first();
    expect($wallet)->not->toBeNull();
    expect($wallet->balance)->toEqual('1500.00');

    $this->assertDatabaseHas('wallet_transactions', [
        'user_id' => $customer->id,
        'reference' => 'MANUAL-REF-001',
        'type' => WalletTransaction::TYPE_CREDIT,
    ]);

    Event::assertDispatched(WalletFunded::class);
});

it('validates manual funding inputs', function (): void {
    $admin = createAdminUser();
    $customer = User::factory()->create();

    $response = $this->from(route('admin.wallets.fund'))->actingAs($admin)->post(route('admin.wallets.fund.store'), [
        'user_id' => $customer->id,
        'amount' => '-10',
        'reference' => '',
    ]);

    $response->assertRedirect(route('admin.wallets.fund'));
    $response->assertSessionHasErrors(['amount', 'reference']);

    expect(Wallet::where('user_id', $customer->id)->exists())->toBeFalse();
});

it('prevents non-admin users from accessing manual funding', function (): void {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)->get(route('admin.wallets.fund'))->assertForbidden();

    $this->actingAs($user)->post(route('admin.wallets.fund.store'), [
        'user_id' => $target->id,
        'amount' => '100',
        'reference' => 'MANUAL-REF-002',
    ])->assertForbidden();
});
