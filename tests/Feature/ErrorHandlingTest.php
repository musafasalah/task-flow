<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns a JSON 404 for an unknown api route', function () {
    $this->getJson('/api/does-not-exist')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Resource not found.']);
});

it('returns a JSON 404 for a missing model', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/projects/999999')
        ->assertNotFound()
        ->assertJson(['message' => 'Resource not found.']);
});

it('returns a JSON 401 for unauthenticated requests', function () {
    $this->getJson('/api/dashboard')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('returns a JSON 422 with an errors object for validation failures', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/projects', [])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['name']]);
});
