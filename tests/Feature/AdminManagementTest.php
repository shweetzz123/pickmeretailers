<?php

use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;

test('an administrator can view the payment verification queue', function () {
    $admin = User::factory()->owner()->create();

    Order::factory()->create(['payment_status' => 'awaiting_verification']);

    $this->actingAs($admin)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertSee('GCash Payment Verification');
});

test('a non-administrator cannot view the payment queue', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.payments'))->assertForbidden();
});

test('an administrator can view the transaction ledger and export it as CSV', function () {
    $admin = User::factory()->owner()->create();
    $order = Order::factory()->paid()->create(['total' => 250.00]);

    Transaction::create([
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'type' => 'payment',
        'method' => 'gcash',
        'status' => 'completed',
        'amount' => 250.00,
        'reference' => 'REF250',
        'recorded_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.transactions'))
        ->assertOk()
        ->assertSee('REF250');

    $this->actingAs($admin)
        ->get(route('admin.transactions.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('an administrator can view the users list', function () {
    $admin = User::factory()->owner()->create();
    User::factory()->create(['name' => 'Regular Shopper']);

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee('Regular Shopper');
});

test('an administrator can update the store settings', function () {
    $admin = User::factory()->owner()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Store Settings');

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'gcash_number' => '09171234567',
            'gcash_name' => 'Pick Me Retailers',
            'gcash_instructions' => 'Scan and pay.',
            'shipping_fee' => '50',
        ])
        ->assertRedirect();

    expect(Setting::get('gcash_number'))->toBe('09171234567');
    expect(Setting::get('gcash_name'))->toBe('Pick Me Retailers');
    expect(Setting::get('gcash_instructions'))->toBe('Scan and pay.');
    expect(Setting::get('shipping_fee'))->toBe('50.00');
});
