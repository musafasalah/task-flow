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
        $demoUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

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
