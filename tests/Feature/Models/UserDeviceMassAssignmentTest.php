<?php

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fill() cannot override registered_by set by trusted code', function () {
    $userA = User::factory()->create(); // legitimate registrant — set by the controller
    $userB = User::factory()->create(); // attacker — tries to register the device under another user

    $device = new UserDevice();

    // Trusted code sets the real registrant directly
    $device->registered_by = $userA->id;

    // Attacker's payload attempts to attribute this device to a different user
    $device->fill([
        'device_token'  => 'token-abc-123',
        'platform'      => 'ios',
        'active'        => true,
        'registered_by' => $userB->id, // malicious override attempt
    ]);

    $device->save();

    // FAILS with $guarded = []:
    //   fill() accepts registered_by and overwrites $userA->id with $userB->id —
    //   push notifications would be dispatched to the wrong user's device
    // PASSES with proper $fillable (registered_by absent):
    //   fill() ignores registered_by — the direct assignment to $userA->id survives
    expect($device->fresh()->registered_by)->toBe($userA->id);
});
