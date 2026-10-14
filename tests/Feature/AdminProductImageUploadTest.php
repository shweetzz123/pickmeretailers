<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

test('an administrator can upload a product image larger than two megabytes', function () {
    $admin = User::factory()->admin()->create();
    $image = UploadedFile::fake()->image('product.png')->size(3 * 1024);

    $response = $this->actingAs($admin)->post(route('admin.postaddproduct'), [
        'product_title' => 'Large product image',
        'product_description' => 'Product with a larger image.',
        'product_quantity' => 1,
        'product_price' => 10,
        'product_category' => 'General',
        'product_image' => $image,
    ]);

    $response->assertRedirect();
    $product = Product::where('product_title', 'Large product image')->firstOrFail();
    expect($product->reorder_level)->toBeNull();
    $imagePath = public_path('products/'.$product->product_image);

    try {
        expect(File::exists($imagePath))->toBeTrue();
    } finally {
        File::delete($imagePath);
    }
});

test('an administrator can upload multiple product images at once', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.postaddproduct'), [
        'product_title' => 'Multi image product',
        'product_description' => 'Product with several images.',
        'product_quantity' => 5,
        'product_price' => 25,
        'product_category' => 'General',
        'product_images' => [
            UploadedFile::fake()->image('main.png'),
            UploadedFile::fake()->image('second.png'),
            UploadedFile::fake()->image('third.png'),
        ],
    ]);

    $response->assertRedirect();

    $product = Product::where('product_title', 'Multi image product')->firstOrFail();

    expect($product->product_image)->not->toBeNull();
    expect($product->product_images)->toHaveCount(2);
    expect($product->galleryImages())->toHaveCount(3);

    $paths = array_map(fn ($image) => public_path('products/'.$image), $product->galleryImages());

    try {
        foreach ($paths as $path) {
            expect(File::exists($path))->toBeTrue();
        }
    } finally {
        File::delete($paths);
    }
});

test('a product requires at least one image when it is created', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->from(route('admin.addproduct'))
        ->post(route('admin.postaddproduct'), [
            'product_title' => 'No image product',
            'product_description' => 'Product without an image.',
            'product_quantity' => 1,
            'product_price' => 10,
            'product_category' => 'General',
        ]);

    $response->assertSessionHasErrors('product_images');
    expect(Product::where('product_title', 'No image product')->exists())->toBeFalse();
});
