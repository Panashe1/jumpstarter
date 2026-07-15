<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Creates a demo login (test@example.com / password) that owns one
     * organization and is a plain member of another, so tenant isolation
     * and role checks can be demonstrated immediately.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $teammates = User::factory(4)->create();
        $outsider = User::factory()->create([
            'name' => 'Other Owner',
            'email' => 'other@example.com',
        ]);

        // Organization the demo user owns.
        $acme = Organization::factory()->create([
            'name' => 'Acme Inc',
            'slug' => 'acme-inc',
            'owner_id' => $owner->id,
        ]);
        $acme->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);
        $acme->users()->attach($teammates[0], ['role' => OrganizationRole::Admin->value]);
        $acme->users()->attach($teammates[1], ['role' => OrganizationRole::Member->value]);
        $acme->users()->attach($teammates[2], ['role' => OrganizationRole::Member->value]);

        // A second organization where the demo user is only a member —
        // useful for showing that data does not leak across tenants.
        $globex = Organization::factory()->create([
            'name' => 'Globex Corp',
            'slug' => 'globex-corp',
            'owner_id' => $outsider->id,
        ]);
        $globex->users()->attach($outsider, ['role' => OrganizationRole::Owner->value]);
        $globex->users()->attach($owner, ['role' => OrganizationRole::Member->value]);
        $globex->users()->attach($teammates[3], ['role' => OrganizationRole::Member->value]);

        $owner->forceFill(['current_organization_id' => $acme->id])->save();
        $outsider->forceFill(['current_organization_id' => $globex->id])->save();

        $this->seedProjects($acme, [$owner, $teammates[0], $teammates[1], $teammates[2]]);
        $this->seedProjects($globex, [$outsider, $owner, $teammates[3]]);
    }

    /**
     * @param  array<int, User>  $members
     */
    private function seedProjects(Organization $organization, array $members): void
    {
        $projects = Project::factory(3)->active()->create([
            'organization_id' => $organization->id,
            'created_by' => $members[0]->id,
        ]);

        foreach ($projects as $project) {
            foreach (TaskStatus::cases() as $status) {
                $count = fake()->numberBetween(2, 5);

                for ($position = 0; $position < $count; $position++) {
                    $task = Task::factory()->create([
                        'project_id' => $project->id,
                        'created_by' => fake()->randomElement($members)->id,
                        'assignee_id' => fake()->optional(0.7)->randomElement($members)?->id,
                        'status' => $status,
                        'position' => $position,
                    ]);

                    Comment::factory(fake()->numberBetween(0, 3))->create([
                        'task_id' => $task->id,
                        'user_id' => fake()->randomElement($members)->id,
                    ]);
                }
            }
        }
    }
}
