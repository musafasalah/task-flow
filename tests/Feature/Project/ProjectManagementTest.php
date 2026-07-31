<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('lists only the authenticated user\'s projects', function () {
    Project::factory(3)->for($this->user)->create();
    Project::factory(2)->create(); // other users' projects

    $this->getJson('/api/projects')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'status']], 'meta', 'links']);
});

it('creates a project for the authenticated user', function () {
    $response = $this->postJson('/api/projects', [
        'name' => 'My New Project',
        'description' => 'A description',
        'status' => 'active',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My New Project')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('projects', [
        'name' => 'My New Project',
        'user_id' => $this->user->id,
    ]);
});

it('defaults new projects to active status', function () {
    $this->postJson('/api/projects', ['name' => 'No Status Project'])
        ->assertCreated()
        ->assertJsonPath('data.status', ProjectStatus::Active->value);
});

it('validates required fields when creating a project', function () {
    $this->postJson('/api/projects', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('rejects an invalid status value', function () {
    $this->postJson('/api/projects', ['name' => 'X', 'status' => 'invalid'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('shows a project the user owns', function () {
    $project = Project::factory()->for($this->user)->create();

    $this->getJson("/api/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id);
});

it('updates a project the user owns', function () {
    $project = Project::factory()->for($this->user)->create();

    $this->putJson("/api/projects/{$project->id}", ['name' => 'Renamed', 'status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Renamed']);
});

it('soft deletes a project the user owns', function () {
    $project = Project::factory()->for($this->user)->create();

    $this->deleteJson("/api/projects/{$project->id}")->assertNoContent();

    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

it('forbids viewing another user\'s project', function () {
    $project = Project::factory()->create(); // different owner

    $this->getJson("/api/projects/{$project->id}")->assertForbidden();
});

it('forbids updating another user\'s project', function () {
    $project = Project::factory()->create();

    $this->putJson("/api/projects/{$project->id}", ['name' => 'Hacked'])->assertForbidden();
});

it('forbids deleting another user\'s project', function () {
    $project = Project::factory()->create();

    $this->deleteJson("/api/projects/{$project->id}")->assertForbidden();
});

it('requires authentication to access projects', function () {
    app()['auth']->forgetGuards();

    $this->getJson('/api/projects')->assertUnauthorized();
});
