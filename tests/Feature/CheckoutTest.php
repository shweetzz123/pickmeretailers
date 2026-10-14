<?php

use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

function validCheckoutPayload(): array
{
    return [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address' => '123 Main St',
        'address2' => 'Unit 4',
        'barangay' => 'San Antonio',
        'city' => 'Springfield',
        'region' => 'NCR',
        'postal' => '1234',
        'phone' => '09171234567',
        'email' => 'john@example.com',
        'notes' => '',
    ];
}

test('guests are redirected to login at checkout', function () {
    $this->get('/checkout')->assertRedirect('/login');
});

test('checkout with an empty cart redirects to the shop', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertRedirect('/shop')
        ->assertSessionHas('cartMessage');

    expect(Order::count())->toBe(0);
});

test('placing an order reserves stock, records history and redirects to the GCash payment page', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertRedirect(route('orders.pay', Order::first()->id));

    $order = Order::where('user_id', $user->id)->first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe('pending');
    expect($order->payment_status)->toBe('unpaid');
    expect($order->payment_method)->toBe('gcash');
    expect($product->fresh()->product_quantity)->toBe(3);
    expect(InventoryHistory::where('reference_id', $order->id)->where('type', 'sale')->count())->toBe(1);
});

test('placing an order collapses a duplicated street address', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $payload = validCheckoutPayload();
    $payload['address'] = '2461 P Villanueva St Pasay City 2461 P Villanueva St Pasay City';
    $payload['address2'] = '';
    $payload['barangay'] = '91';
    $payload['city'] = 'Pasay';
    $payload['region'] = 'Metro Manila';
    $payload['postal'] = '1300';

    $this->actingAs($user)->post('/checkout/place-order', $payload);

    $order = Order::where('user_id', $user->id)->first();

    expect($order)->not->toBeNull();
    expect($order->address)->toBe('2461 P Villanueva St, 91, Pasay, Metro Manila 1300');
});

test('resubmitting the same checkout form does not create a second order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $payload = validCheckoutPayload();
    $payload['idempotency_key'] = 'key-checkout-1';

    $order = null;

    $this->actingAs($user)->post('/checkout/place-order', $payload);
    $order = Order::first();

    $this->actingAs($user)
        ->post('/checkout/place-order', $payload)
        ->assertRedirect(route('orders.pay', $order->id));

    expect(Order::count())->toBe(1);
    expect($product->fresh()->product_quantity)->toBe(3);
});

test('a different idempotency key creates a second order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post('/checkout/place-order', [...validCheckoutPayload(), 'idempotency_key' => 'key-checkout-1']);

    $this->actingAs($user)
        ->post('/checkout/place-order', [...validCheckoutPayload(), 'idempotency_key' => 'key-checkout-2']);

    expect(Order::count())->toBe(2);
    expect($product->fresh()->product_quantity)->toBe(1);
});

test('resubmitting a form for an already paid order does not create a duplicate', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'delivered',
        'payment_status' => 'paid',
        'idempotency_key' => 'key-paid-1',
    ]);
    $product->decrement('product_quantity', 2);

    $this->actingAs($user)
        ->post('/checkout/place-order', [...validCheckoutPayload(), 'idempotency_key' => 'key-paid-1'])
        ->assertRedirect(route('orders.show', $order->id));

    expect(Order::count())->toBe(1);
    expect($product->fresh()->product_quantity)->toBe(3);
});

test('order items record the variant used at checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)->post('/checkout/place-order', validCheckoutPayload());

    $item = OrderItem::first();

    expect($item->variant_id)->toBe($variant->id);
    expect($variant->fresh()->quantity)->toBe(3);
});

test('cannot place an order exceeding available stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 2]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($user)
        ->from('/checkout')
        ->post('/checkout/place-order', validCheckoutPayload())
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0);
    expect($product->fresh()->product_quantity)->toBe(2);
});

test('checkout validates the order payload', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $payload = validCheckoutPayload();
    $payload['phone'] = 'not-a-phone';
    $payload['postal'] = 'abc';

    $this->actingAs($user)
        ->from('/checkout')
        ->post('/checkout/place-order', $payload)
        ->assertSessionHasErrors(['phone', 'postal']);

    expect(Order::count())->toBe(0);
});

test('checkout rejects a numeric city', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $payload = validCheckoutPayload();
    $payload['city'] = '137605';

    $this->actingAs($user)
        ->from('/checkout')
        ->post('/checkout/place-order', $payload)
        ->assertSessionHasErrors('city');

    expect(Order::count())->toBe(0);
});

test('applying a valid percentage coupon returns the discount', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 100, 'product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Coupon::factory()->create(['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20]);

    $this->actingAs($user)
        ->postJson('/checkout/coupon', ['code' => 'save20'])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'couponCode' => 'SAVE20',
            'discount' => 40,
            'freeShipping' => false,
        ]);

    expect(session('coupon_code'))->toBe('SAVE20');
});

test('applying an unknown coupon returns an error', function () {
    $user = User::factory()->create();
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => Product::factory()->create(['product_price' => 100, 'product_quantity' => 5])->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->postJson('/checkout/coupon', ['code' => 'NOPE'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('applying a coupon below its minimum order returns an error', function () {
    $user = User::factory()->create();
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => Product::factory()->create(['product_price' => 100, 'product_quantity' => 5])->id,
        'quantity' => 1,
    ]);

    Coupon::factory()->create(['code' => 'MIN500', 'type' => 'fixed', 'value' => 50, 'min_order' => 500]);

    $this->actingAs($user)
        ->postJson('/checkout/coupon', ['code' => 'MIN500'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect(session('coupon_code'))->toBeNull();
});

test('removing an applied coupon clears it from the session', function () {
    $user = User::factory()->create();
    session(['coupon_code' => 'SAVE20']);

    $this->actingAs($user)
        ->deleteJson('/checkout/coupon')
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(session('coupon_code'))->toBeNull();
});

test('placing an order with a coupon stores the discount and records usage', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 100, 'product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $coupon = Coupon::factory()->create(['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20]);

    session(['coupon_code' => 'SAVE20']);

    $this->actingAs($user)->post('/checkout/place-order', validCheckoutPayload());

    $order = Order::where('user_id', $user->id)->first();

    expect((float) $order->discount)->toBe(40.0);
    expect($order->coupon_code)->toBe('SAVE20');
    expect(CouponUsage::where('order_id', $order->id)->where('coupon_id', $coupon->id)->exists())->toBeTrue();
    expect((float) CouponUsage::where('order_id', $order->id)->first()->discount_amount)->toBe(40.0);
    expect($coupon->fresh()->used_count)->toBe(1);
    expect(session('coupon_code'))->toBeNull();
});

test('free shipping coupon keeps shipping free at zero cost', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_price' => 300, 'product_quantity' => 5]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Coupon::factory()->create(['code' => 'FREESHIP', 'type' => 'free_shipping', 'value' => 0, 'min_order' => 500]);

    session(['coupon_code' => 'FREESHIP']);

    $this->actingAs($user)->post('/checkout/place-order', validCheckoutPayload());

    $order = Order::where('user_id', $user->id)->first();

    expect((float) $order->shipping_fee)->toBe(0.0);
    expect((float) $order->discount)->toBe(0.0);
    expect($order->coupon_code)->toBe('FREESHIP');
    expect(CouponUsage::where('order_id', $order->id)->exists())->toBeTrue();
});

test('checkout place-order is throttled to prevent stock hoarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    for ($i = 0; $i < 10; $i++) {
        $this->post('/checkout/place-order', [])->assertRedirect();
    }

    $this->post('/checkout/place-order', [])
        ->assertTooManyRequests();
});

/*
|--------------------------------------------------------------------------
| GCash payment flow
|--------------------------------------------------------------------------
*/

test('the payment page shows the configured GCash details', function () {
    Setting::put('gcash_number', '09171234567');
    Setting::put('gcash_name', 'Pick Me Retailers');

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($user)
        ->get(route('orders.pay', $order->id))
        ->assertOk()
        ->assertSee('09171234567')
        ->assertSee('Pick Me Retailers');
});

test('a customer can submit a GCash reference with a screenshot', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($user)
        ->post(route('orders.pay.submit', $order->id), [
            'reference' => '1234567890123',
            'proof' => UploadedFile::fake()->image('proof.jpg', 100, 100),
            'confirm_address' => '1',
        ])
        ->assertRedirect(route('orders.show', $order->id));

    $order->refresh();

    expect($order->payment_status)->toBe('awaiting_verification');
    expect($order->payment_reference)->toBe('1234567890123');
    expect($order->payment_submitted_at)->not->toBeNull();
    expect($order->payment_proof_path)->not->toBeNull();

    $proofPath = public_path('payment-proofs/'.$order->payment_proof_path);
    expect(file_exists($proofPath))->toBeTrue();
    @unlink($proofPath);
});

test('the GCash reference is required to submit a payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'unpaid']);

    $this->actingAs($user)
        ->post(route('orders.pay.submit', $order->id), ['reference' => '', 'confirm_address' => '1'])
        ->assertSessionHasErrors('reference');

    expect($order->fresh()->payment_status)->toBe('unpaid');
});

test('the GCash reference must be exactly 13 digits', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'unpaid']);

    foreach (['123456789012', '12345678901234', '12345678901a'] as $invalid) {
        $this->actingAs($user)
            ->post(route('orders.pay.submit', $order->id), [
                'reference' => $invalid,
                'confirm_address' => '1',
            ])
            ->assertSessionHasErrors('reference');

        expect($order->fresh()->payment_status)->toBe('unpaid');
    }
});

test('the delivery address must be confirmed before submitting a payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'unpaid']);

    $this->actingAs($user)
        ->post(route('orders.pay.submit', $order->id), ['reference' => '1234567890123'])
        ->assertSessionHasErrors('confirm_address');

    expect($order->fresh()->payment_status)->toBe('unpaid');
});

test('the payment page shows the order delivery address', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'payment_status' => 'unpaid',
        'address' => '2461 P Villanueva St, Pasay, Metro Manila 1300',
    ]);

    $this->actingAs($user)
        ->get(route('orders.pay', $order->id))
        ->assertOk()
        ->assertSee('Delivering to')
        ->assertSee('2461 P Villanueva St, Pasay, Metro Manila 1300');
});

test('a customer cannot pay another customer\'s order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id, 'payment_status' => 'unpaid']);

    $this->actingAs($other)->get(route('orders.pay', $order->id))->assertForbidden();

    $this->actingAs($other)
        ->post(route('orders.pay.submit', $order->id), ['reference' => '123'])
        ->assertForbidden();
});

test('an administrator verifies a payment and the order is marked paid with an invoice and ledger entry', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'awaiting_verification',
        'payment_reference' => 'REF123',
        'total' => 350.00,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 175.00,
    ]);
    Cart::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $order->id))
        ->assertRedirect();

    $order->refresh();

    expect($order->payment_status)->toBe('paid');
    expect($order->paid_at)->not->toBeNull();
    expect($order->payment_verified_by)->toBe($admin->id);
    expect($order->invoice)->not->toBeNull();
    expect($order->invoice->invoice_number)->toContain('INV-');
    expect((float) $order->invoice->total)->toBe(350.0);
    expect(Transaction::where('order_id', $order->id)->where('type', 'payment')->where('status', 'completed')->exists())->toBeTrue();
    expect(Cart::where('user_id', $user->id)->count())->toBe(0);

    Mail::assertQueued(OrderConfirmationMail::class);
});

test('an administrator rejects a payment with a reason', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
        'payment_status' => 'awaiting_verification',
        'payment_reference' => 'REF123',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.payments.reject', $order->id), ['reason' => 'Reference not found in GCash.'])
        ->assertRedirect();

    $order->refresh();

    expect($order->payment_status)->toBe('rejected');
    expect($order->payment_notes)->toBe('Reference not found in GCash.');
    expect($order->invoice)->toBeNull();
});

test('non-administrators cannot verify payments', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'payment_status' => 'awaiting_verification',
    ]);

    $this->actingAs($user)
        ->post(route('admin.payments.verify', $order->id))
        ->assertForbidden();
});

test('the invoice is available after payment and missing before it', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = Product::factory()->create(['product_quantity' => 5]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'payment_status' => 'awaiting_verification',
        'payment_reference' => 'REF123',
        'total' => 200.00,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 200.00,
    ]);

    $this->actingAs($user)
        ->get(route('orders.invoice', $order->id))
        ->assertNotFound();

    $this->actingAs($admin)->post(route('admin.payments.verify', $order->id));

    $invoiceNumber = $order->fresh()->invoice->invoice_number;

    $this->actingAs($user)
        ->get(route('orders.invoice', $order->id))
        ->assertOk()
        ->assertSee($invoiceNumber);
});
