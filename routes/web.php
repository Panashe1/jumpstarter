<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationMemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    // Organization creation is reachable without a current organization —
    // it's where brand-new users land.
    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');

    Route::get('organizations/{organization}/settings', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::patch('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::put('organizations/{organization}/switch', [OrganizationController::class, 'switch'])->name('organizations.switch');

    Route::post('organizations/{organization}/members', [OrganizationMemberController::class, 'store'])->name('organizations.members.store');
    Route::patch('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'update'])->name('organizations.members.update');
    Route::delete('organizations/{organization}/members/{user}', [OrganizationMemberController::class, 'destroy'])->name('organizations.members.destroy');

    // Everything below requires a valid current organization.
    Route::middleware(['organization'])->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('dashboard');
        })->name('dashboard');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
