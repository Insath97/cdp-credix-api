<?php

use Illuminate\Support\Facades\Hash;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('accepts login parameter for authentication and fails with invalid credentials if user does not exist', function () {
    $response = $this->postJson('/api/v1/login', [
        'login' => 'dev@localhost.com',
        'password' => 'password',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Invalid credentials',
    ]);
});

it('accepts email parameter as an alternative to login and fails with invalid credentials if user does not exist', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'dev@localhost.com',
        'password' => 'password',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Invalid credentials',
    ]);
});

it('fails validation when both email and login are missing', function () {
    $response = $this->postJson('/api/v1/login', [
        'password' => 'password',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Validation failed',
        'errors' => [
            'login' => ['The login field is required.'],
        ],
    ]);
});

it('fails validation when password is missing', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'dev@localhost.com',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Validation failed',
        'errors' => [
            'password' => ['The password field is required.'],
        ],
    ]);
});
