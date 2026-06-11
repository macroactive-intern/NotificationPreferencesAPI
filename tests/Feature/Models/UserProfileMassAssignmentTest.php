<?php

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fill() cannot override owner_id set by trusted code', function () {
    $userA = User::factory()->create(); // legitimate owner — set by the controller
    $userB = User::factory()->create(); // attacker — tries to transfer the profile to themselves

    $profile = new UserProfile();

    // Trusted code sets the real owner directly
    $profile->owner_id = $userA->id;

    // Attacker's payload attempts to change owner_id to their own user
    $profile->fill([
        'display_name' => 'Attacker Display Name',
        'timezone'     => 'UTC',
        'locale'       => 'en',
        'owner_id'     => $userB->id, // malicious override attempt
    ]);

    $profile->save();

    // FAILS with $guarded = []:
    //   fill() accepts owner_id and overwrites $userA->id with $userB->id
    // PASSES with proper $fillable (owner_id absent):
    //   fill() ignores owner_id — the direct assignment to $userA->id survives
    expect($profile->fresh()->owner_id)->toBe($userA->id);
});
