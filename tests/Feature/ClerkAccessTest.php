<?php

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;

test('clerks can access the shared admin screens', function () {
    $clerk = User::factory()->clerk()->create();

    $this->actingAs($clerk)->get('/dashboard')->assertOk();
    $this->actingAs($clerk)->get('/view_product')->assertOk();
    $this->actingAs($clerk)->get('/view_category')->assertOk();
    $this->actingAs($clerk)->get('/admin/inventory')->assertOk();
});

test('clerks cannot access sales, orders, payments, or settings', function () {
    $clerk = User::factory()->clerk()->create();

    $this->actingAs($clerk)->get('/view_orders')->assertForbidden();
    $this->actingAs($clerk)->get('/admin/payments')->assertForbidden();
    $this->actingAs($clerk)->get('/admin/transactions')->assertForbidden();
    $this->actingAs($clerk)->get('/admin/reports')->assertForbidden();
    $this->actingAs($clerk)->get('/admin/reports/export/csv')->assertForbidden();
    $this->actingAs($clerk)->get('/admin/settings')->assertForbidden();
    $this->actingAs($clerk)->get('/admin/users')->assertForbidden();
});

test('regular users still cannot access the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/');
    $this->actingAs($user)->get('/view_product')->assertForbidden();
});

test('the clerk dashboard hides sales figures', function () {
    $clerk = User::factory()->clerk()->create();

    $this->actingAs($clerk)->get('/dashboard')
        ->assertOk()
        ->assertSee('Inventory Alerts')
        ->assertDontSee('Paid Revenue')
        ->assertDontSee('7-Day Paid Sales')
        ->assertDontSee('Recent Orders');
});

test('the clerk sidebar hides owner-only sections', function () {
    $clerk = User::factory()->clerk()->create();

    $this->actingAs($clerk)->get('/view_product')
        ->assertOk()
        ->assertSee('Inventory')
        ->assertDontSee('Payment Verification')
        ->assertDontSee('Transaction Ledger')
        ->assertDontSee('Users &amp; Roles')
        ->assertDontSee('Store Settings')
        ->assertDontSee('Orders');
});

test('the owner sidebar still shows every section', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get('/view_product')
        ->assertOk()
        ->assertSee('Payment Verification')
        ->assertSee('Store Settings');
});

test('clerks can restock inventory', function () {
    $clerk = User::factory()->clerk()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);

    $this->actingAs($clerk)
        ->from('/admin/inventory')
        ->post('/admin/inventory/'.$product->id.'/adjust', [
            'type' => 'restock',
            'quantity' => 5,
        ])
        ->assertRedirect('/admin/inventory')
        ->assertSessionHas('success');

    expect($product->fresh()->product_quantity)->toBe(15);
});

test('clerks cannot apply a stock adjustment that could reduce quantity', function () {
    $clerk = User::factory()->clerk()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);

    $this->actingAs($clerk)
        ->from('/admin/inventory')
        ->post('/admin/inventory/'.$product->id.'/adjust', [
            'type' => 'adjustment',
            'quantity' => 2,
        ])
        ->assertRedirect('/admin/inventory')
        ->assertSessionHasErrors('type');

    expect($product->fresh()->product_quantity)->toBe(10);
});

test('owners can apply a stock adjustment', function () {
    $owner = User::factory()->owner()->create();
    $product = Product::factory()->create(['product_quantity' => 10]);

    $this->actingAs($owner)
        ->from('/admin/inventory')
        ->post('/admin/inventory/'.$product->id.'/adjust', [
            'type' => 'adjustment',
            'quantity' => 2,
        ])
        ->assertRedirect('/admin/inventory')
        ->assertSessionHas('success');

    expect($product->fresh()->product_quantity)->toBe(2);
});

test('clerks cannot reduce stock through the product edit form', function () {
    $clerk = User::factory()->clerk()->create();
    $product = Product::factory()->create([
        'product_quantity' => 10,
        'product_category' => 'Snacks',
    ]);

    $this->actingAs($clerk)
        ->from('/updateproduct/'.$product->id)
        ->post('/update_product/'.$product->id, [
            'product_title' => $product->product_title,
            'product_description' => $product->product_description,
            'product_category' => 'Snacks',
            'product_quantity' => 3,
            'product_price' => $product->product_price,
        ])
        ->assertRedirect('/updateproduct/'.$product->id)
        ->assertSessionHasErrors('product_quantity');

    expect($product->fresh()->product_quantity)->toBe(10);
});

test('clerks cannot delete anything', function () {
    $clerk = User::factory()->clerk()->create();
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $coupon = Coupon::factory()->create();

    $this->actingAs($clerk)->delete('/deleteproduct/'.$product->id)->assertForbidden();
    $this->actingAs($clerk)->delete('/delete_category/'.$category->id)->assertForbidden();
    $this->actingAs($clerk)->delete('/admin/coupons/'.$coupon->id)->assertForbidden();

    expect(Product::find($product->id))->not->toBeNull()
        ->and(Category::find($category->id))->not->toBeNull()
        ->and(Coupon::find($coupon->id))->not->toBeNull();
});

test('owners can delete things', function () {
    $owner = User::factory()->owner()->create();
    $product = Product::factory()->create();

    $this->actingAs($owner)
        ->from('/view_product')
        ->delete('/deleteproduct/'.$product->id)
        ->assertRedirect('/view_product')
        ->assertSessionHas('deleteproduct_message');

    expect(Product::find($product->id))->toBeNull();
});

test('the owner can promote a customer to clerk and demote them again', function () {
    $owner = User::factory()->owner()->create();
    $user = User::factory()->create();

    $this->actingAs($owner)
        ->from('/admin/users')
        ->patch('/admin/users/'.$user->id.'/role')
        ->assertRedirect('/admin/users')
        ->assertSessionHas('success');

    expect($user->fresh()->user_type)->toBe('clerk');

    $this->actingAs($owner)
        ->from('/admin/users')
        ->patch('/admin/users/'.$user->id.'/role')
        ->assertRedirect('/admin/users')
        ->assertSessionHas('success');

    expect($user->fresh()->user_type)->toBe('user');
});

test('clerks cannot change roles', function () {
    $clerk = User::factory()->clerk()->create();
    $user = User::factory()->create();

    $this->actingAs($clerk)
        ->patch('/admin/users/'.$user->id.'/role')
        ->assertForbidden();

    expect($user->fresh()->user_type)->toBe('user');
});

test('the owner account role can never be changed', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->from('/admin/users')
        ->patch('/admin/users/'.$owner->id.'/role')
        ->assertRedirect('/admin/users')
        ->assertSessionHasErrors('role');

    expect($owner->fresh()->user_type)->toBe('owner');
});

test('user_type cannot be escalated through mass assignment', function () {
    $user = User::factory()->create();

    $user->update(['user_type' => 'owner']);

    expect($user->fresh()->user_type)->toBe('user');
});
