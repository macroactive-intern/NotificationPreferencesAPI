<?php

use App\Models\User;
use App\Models\UserProfile;

// ── show ──────────────────────────────────────────────────────────────────────

it('authenticated user can get their profile', function () {
    $user = User::factory()->create();

    $profile = new UserProfile(['display_name' => 'Test User']);
    $profile->owner_id = $user->id;
    $profile->save();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonFragment(['owner_id' => $user->id]);
});

it('returns 404 when profile does not exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/profile')
        ->assertNotFound();
});

// ── update ────────────────────────────────────────────────────────────────────

it('user can update their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/profile', ['display_name' => 'Updated Name'])
        ->assertSuccessful()
        ->assertJsonFragment(['display_name' => 'Updated Name']);
});

it('request cannot override owner_id', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA, 'sanctum')
        ->putJson('/api/profile', [
            'display_name' => 'Legit Name',
            'owner_id'     => $userB->id,  // attacker injects a different owner
        ])
        ->assertSuccessful();

    expect(UserProfile::first()->owner_id)->toBe($userA->id);
});

it('user cannot update another user\'s profile', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // Create userA's profile with a known display_name
    $profileA = new UserProfile(['display_name' => 'Original Name']);
    $profileA->owner_id = $userA->id;
    $profileA->save();

    // userB calls PUT /api/profile — this creates or updates userB's OWN profile,
    // it must not touch userA's record
    $this->actingAs($userB, 'sanctum')
        ->putJson('/api/profile', ['display_name' => 'Hacked Name'])
        ->assertSuccessful();

    expect($profileA->fresh()->display_name)->toBe('Original Name');
});
