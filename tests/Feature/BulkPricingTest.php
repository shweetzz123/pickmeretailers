<?php

use App\Models\Cart;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

function bulkPricingCheckoutPayload(): array
{
    return [
        'first_name' => 'Bella',
        'last_name' => 'Buyer',
        'address' => '1 Bulk Way',
        'address2' => '',
        'barangay' => 'Poblacion',
        'city' => 'Pasay',
        'region' => 'Metro Manila',
        'postal' => '1300',
        'phone' => '09171234567',
        'email' => 'bella@example.com',
        'notes' => '',
    ];
}

test('a product charges the bulk price only at or above the threshold', function () {
    $product = Product::factory()->create(['product_price' => 100, 'wholesale_price' => 80]);

    expect($product->bulkPriceThreshold())->toBe(10);
    expect($product->priceForQuantity(1))->toBe(100.0);
    expect($product->priceForQuantity(9))->toBe(100.0);
    expect($product->priceForQuantity(10))->toBe(80.0);
    expect($product->priceForQuantity(25))->toBe(80.0);
});

test('a product without a bulk price always charges the retail price', function () {
    $product = Product::factory()->create(['product_price' => 100, 'wholesale_price' => null]);

    expect($product->hasWholesalePrice())->toBeFalse();
    expect($product->priceForQuantity(50))->toBe(100.0);
});

test('the product page advertises the bulk threshold price', function () {
    $product = Product::factory()->create([
        'product_price' => 100,
        'wholesale_price' => 80,
        'product_quantity' => 20,
    ]);

    $this->get(route('product_details', $product->id))
        ->assertOk()
        ->assertSee('Buy 10 or more')
        ->assertSee('80.00');
});

test('a cart line unit price switches to the bulk price at the threshold', function () {
    $product = Product::factory()->create([
        'product_price' => 100,
        'wholesale_price' => 80,
        'product_quantity' => 50,
    ]);

    $cart = Cart::factory()->create([
        'product_id' => $product->id,
        'quantity' => 9,
    ]);

    expect($cart->unitPrice())->toBe(100.0);

    $cart->quantity = 10;

    expect($cart->unitPrice())->toBe(80.0);
});

test('a bulk order records the resale unit price at checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'product_price' => 100,
        'wholesale_price' => 80,
        'product_quantity' => 20,
    ]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $this->actingAs($user)->post('/checkout/place-order', bulkPricingCheckoutPayload());

    $item = OrderItem::first();

    expect($item)->not->toBeNull();
    expect((float) $item->price)->toBe(80.0);
});

test('a small order records the retail unit price at checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'product_price' => 100,
        'wholesale_price' => 80,
        'product_quantity' => 20,
    ]);
    Cart::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 9,
    ]);

    $this->actingAs($user)->post('/checkout/place-order', bulkPricingCheckoutPayload());

    $item = OrderItem::first();

    expect($item)->not->toBeNull();
    expect((float) $item->price)->toBe(100.0);
});
