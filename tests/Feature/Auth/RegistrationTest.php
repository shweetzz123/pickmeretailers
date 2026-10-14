<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $this->get('/register')->assertOk();
});

test('registration stores a plain user account', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'user@example.com',
        'user_type' => 'user',
    ]);
    $response->assertRedirect(route('verification.notice'));
});

test('a posted admin account type is ignored and a plain user is created', function () {
    $this->post('/register', [
        'name' => 'Bad Actor',
        'email' => 'bad@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'user_type' => 'admin',
    ]);

    expect(User::where('email', 'bad@example.com')->first()->user_type)->toBe('user');
});
