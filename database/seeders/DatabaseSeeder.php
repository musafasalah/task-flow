<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );

        // Only generate sample data on a fresh database so the seeder is
        // idempotent and safe to re-run (e.g. on every container start).
        if (Project::query()->exists()) {
            return;
        }

        $this->seedProjectsWithTasks($demoUser);

        User::factory(4)->create()->each(function (User $user): void {
            $this->seedProjectsWithTasks($user);
        });
    }

    /**
     * Seed a handful of projects and tasks for the given user.
     */
    protected function seedProjectsWithTasks(User $user): void
    {
        Project::factory(3)
            ->for($user)
            ->create()
            ->each(function (Project $project): void {
                Task::factory(5)->for($project)->create();
                Task::factory(2)->for($project)->overdue()->create();
            });
    }
}
