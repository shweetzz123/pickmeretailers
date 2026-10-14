<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    $code = $user->generateVerificationCode();

    Event::fake();

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => $code])
        ->assertRedirect(route('index', absolute: false).'?verified=1');

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email is not verified with an invalid code', function () {
    $user = User::factory()->unverified()->create();

    $user->update([
        'verification_code' => '123456',
        'verification_code_expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->post(route('verification.verify'), ['code' => '999999'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
