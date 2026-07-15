<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    private function organizationWithMember(User $user, string $role = 'member'): Organization
    {
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => $role]);

        return $organization;
    }

    public function test_queries_are_scoped_to_the_current_organization(): void
    {
        $user = User::factory()->create();
        $orgA = $this->organizationWithMember($user, OrganizationRole::Owner->value);
        $orgB = $this->organizationWithMember($user, OrganizationRole::Owner->value);

        $projectA = Project::factory()->create(['organization_id' => $orgA->id, 'created_by' => $user->id]);
        $projectB = Project::factory()->create(['organization_id' => $orgB->id, 'created_by' => $user->id]);

        $user->switchOrganization($orgA);
        $this->actingAs($user->fresh());

        $this->assertSame([$projectA->id], Project::pluck('id')->all());

        $user->switchOrganization($orgB);
        $this->actingAs($user->fresh());

        $this->assertSame([$projectB->id], Project::pluck('id')->all());
    }

    public function test_created_models_are_stamped_with_the_current_organization(): void
    {
        $user = User::factory()->create();
        $organization = $this->organizationWithMember($user, OrganizationRole::Owner->value);
        $user->switchOrganization($organization);

        $this->actingAs($user->fresh());

        $project = Project::create([
            'name' => 'Scoped project',
            'created_by' => $user->id,
        ]);

        $this->assertSame($organization->id, $project->organization_id);
    }

    public function test_users_can_switch_to_an_organization_they_belong_to(): void
    {
        $user = User::factory()->create();
        $orgA = $this->organizationWithMember($user);
        $orgB = $this->organizationWithMember($user);
        $user->switchOrganization($orgA);

        $this->actingAs($user)
            ->put(route('organizations.switch', $orgB))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($orgB->id, $user->fresh()->current_organization_id);
    }

    public function test_users_cannot_switch_to_an_organization_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();
        $this->organizationWithMember($user);
        $other = Organization::factory()->create();

        $this->actingAs($user)
            ->put(route('organizations.switch', $other))
            ->assertForbidden();
    }

    public function test_users_without_an_organization_are_redirected_to_the_create_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('organizations.create'));
    }

    public function test_creating_an_organization_makes_the_creator_its_owner_and_switches_to_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('organizations.store'), ['name' => 'New Workspace'])
            ->assertRedirect(route('dashboard'));

        $organization = Organization::where('name', 'New Workspace')->sole();

        $this->assertSame($user->id, $organization->owner_id);
        $this->assertSame($organization->id, $user->fresh()->current_organization_id);
        $this->assertSame(OrganizationRole::Owner, $user->roleIn($organization));
    }
}
