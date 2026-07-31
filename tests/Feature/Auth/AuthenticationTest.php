<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('registers a new user and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
});

it('requires a unique email to register', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('requires a matching password confirmation to register', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('logs in a user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
        ->assertJsonPath('user.id', $user->id);
});

it('rejects a login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('logs out an authenticated user', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/logout')
        ->assertOk()
        ->assertJson(['message' => 'Logged out successfully.']);
});

it('forbids guests from accessing protected routes', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
    $this->getJson('/api/user')->assertUnauthorized();
});

it('returns the authenticated user profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});
