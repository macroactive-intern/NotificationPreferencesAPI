<?php

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fill() cannot override user_id set by trusted code', function () {
    $userA = User::factory()->create(); // legitimate owner — set by the controller
    $userB = User::factory()->create(); // attacker — tries to claim ownership via request body

    $preference = new UserNotificationPreference();

    // Trusted code (the controller) sets the real owner directly before accepting any input
    $preference->user_id = $userA->id;

    // Attacker's payload arrives via fill() and tries to override the ownership field
    $preference->fill([
        'channel'    => 'email',
        'event_type' => 'newsletter',
        'enabled'    => true,
        'user_id'    => $userB->id, // malicious override attempt
    ]);

    $preference->save();

    // FAILS with $guarded = []:
    //   fill() accepts user_id and overwrites $userA->id with $userB->id
    // PASSES with proper $fillable (user_id absent):
    //   fill() ignores user_id — the direct assignment to $userA->id survives
    expect($preference->fresh()->user_id)->toBe($userA->id);
});
