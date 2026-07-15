<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('organizations/create', [
            'hasOrganizations' => request()->user()->organizations()->exists(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = Organization::create([
            'name' => $request->validated('name'),
            'owner_id' => $request->user()->id,
        ]);

        $organization->users()->attach($request->user(), [
            'role' => OrganizationRole::Owner->value,
        ]);

        $request->user()->switchOrganization($organization);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Organization created.');
    }

    public function edit(Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        return Inertia::render('organizations/edit', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'members' => $organization->users()
                ->orderBy('name')
                ->get()
                ->map(fn ($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->pivot->role,
                ]),
            'can' => [
                'update' => Gate::allows('update', $organization),
                'delete' => Gate::allows('delete', $organization),
                'manageMembers' => Gate::allows('manageMembers', $organization),
            ],
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->validated());

        return back()->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        $organization->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Organization deleted.');
    }

    public function switch(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->switchOrganization($organization), 403);

        return redirect()->route('dashboard');
    }
}
