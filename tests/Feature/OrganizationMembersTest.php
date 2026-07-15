<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMembersTest extends TestCase
{
    use RefreshDatabase;

    private function orgWithOwner(User $owner): Organization
    {
        $organization = Organization::factory()->create(['owner_id' => $owner->id]);
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner->value]);
        $owner->switchOrganization($organization);

        return $organization;
    }

    private function addMember(Organization $organization, string $role = 'member'): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => $role]);

        return $user;
    }

    public function test_admins_can_add_members_by_email(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);
        $invitee = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('organizations.members.store', $organization), [
                'email' => $invitee->email,
                'role' => 'member',
            ])
            ->assertRedirect();

        $this->assertSame(OrganizationRole::Member, $invitee->roleIn($organization));
    }

    public function test_members_cannot_add_members(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);
        $member = $this->addMember($organization);
        $invitee = User::factory()->create();

        $this->actingAs($member)
            ->post(route('organizations.members.store', $organization), [
                'email' => $invitee->email,
                'role' => 'member',
            ])
            ->assertForbidden();
    }

    public function test_adding_an_unknown_email_fails_validation(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);

        $this->actingAs($owner)
            ->from(route('organizations.edit', $organization))
            ->post(route('organizations.members.store', $organization), [
                'email' => 'nobody@example.com',
                'role' => 'member',
            ])
            ->assertRedirect(route('organizations.edit', $organization))
            ->assertSessionHasErrors('email');
    }

    public function test_admins_can_change_a_member_role_but_not_the_owner_role(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);
        $admin = $this->addMember($organization, OrganizationRole::Admin->value);
        $member = $this->addMember($organization);

        $this->actingAs($admin)
            ->patch(route('organizations.members.update', [$organization, $member]), ['role' => 'admin'])
            ->assertRedirect();

        $this->assertSame(OrganizationRole::Admin, $member->roleIn($organization));

        $this->actingAs($admin)
            ->patch(route('organizations.members.update', [$organization, $owner]), ['role' => 'member'])
            ->assertForbidden();
    }

    public function test_admins_can_remove_members_but_not_the_owner(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);
        $admin = $this->addMember($organization, OrganizationRole::Admin->value);
        $member = $this->addMember($organization);

        $this->actingAs($admin)
            ->delete(route('organizations.members.destroy', [$organization, $member]))
            ->assertRedirect();

        $this->assertFalse($member->fresh()->belongsToOrganization($organization));

        $this->actingAs($admin)
            ->delete(route('organizations.members.destroy', [$organization, $owner]))
            ->assertForbidden();
    }

    public function test_members_can_leave_an_organization(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);
        $member = $this->addMember($organization);
        $member->switchOrganization($organization);

        $this->actingAs($member)
            ->delete(route('organizations.members.destroy', [$organization, $member]))
            ->assertRedirect(route('dashboard'));

        $this->assertFalse($member->fresh()->belongsToOrganization($organization));
        $this->assertNull($member->fresh()->current_organization_id);
    }

    public function test_only_the_owner_can_delete_the_organization(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);
        $admin = $this->addMember($organization, OrganizationRole::Admin->value);

        $this->actingAs($admin)
            ->delete(route('organizations.destroy', $organization))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('organizations.destroy', $organization))
            ->assertRedirect(route('dashboard'));

        $this->assertNull(Organization::find($organization->id));
    }

    public function test_only_admins_can_rename_the_organization(): void
    {
        $owner = User::factory()->create();
        $organization = $this->orgWithOwner($owner);
        $member = $this->addMember($organization);

        $this->actingAs($member)
            ->patch(route('organizations.update', $organization), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('organizations.update', $organization), ['name' => 'Renamed Inc'])
            ->assertRedirect();

        $this->assertSame('Renamed Inc', $organization->fresh()->name);
    }
}
