<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Http\Requests\StoreOrganizationMemberRequest;
use App\Http\Requests\UpdateOrganizationMemberRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationMemberController extends Controller
{
    public function store(StoreOrganizationMemberRequest $request, Organization $organization): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        if ($user->belongsToOrganization($organization)) {
            return back()->withErrors([
                'email' => 'That user is already a member of this organization.',
            ]);
        }

        $organization->users()->attach($user, [
            'role' => $request->validated('role'),
        ]);

        return back()->with('success', "{$user->name} added to {$organization->name}.");
    }

    public function update(UpdateOrganizationMemberRequest $request, Organization $organization, User $user): RedirectResponse
    {
        abort_if($user->id === $organization->owner_id, 403, 'The owner\'s role cannot be changed.');
        abort_unless($user->belongsToOrganization($organization), 404);

        $organization->users()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Organization $organization, User $user): RedirectResponse
    {
        // Members may remove themselves (leave); anything else needs manage rights.
        if ($request->user()->id !== $user->id) {
            Gate::authorize('manageMembers', $organization);
        }

        abort_if($user->id === $organization->owner_id, 403, 'The owner cannot be removed.');
        abort_unless($user->belongsToOrganization($organization), 404);

        $organization->users()->detach($user);

        if ($user->current_organization_id === $organization->id) {
            $user->forceFill(['current_organization_id' => null])->save();
        }

        if ($request->user()->id === $user->id) {
            return redirect()->route('dashboard');
        }

        return back()->with('success', "{$user->name} removed.");
    }
}
