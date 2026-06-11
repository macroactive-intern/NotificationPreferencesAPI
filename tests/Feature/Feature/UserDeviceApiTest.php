<?php

use App\Models\User;
use App\Models\UserDevice;

// ── store ─────────────────────────────────────────────────────────────────────

it('authenticated user can register a device', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/devices', [
            'device_token' => 'token-abc-123',
            'platform'     => 'ios',
        ])
        ->assertCreated();

    $device = UserDevice::first();
    expect($device->registered_by)->toBe($user->id);
    expect($device->platform)->toBe('ios');
});

it('request cannot override registered_by', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA, 'sanctum')
        ->postJson('/api/devices', [
            'device_token'  => 'token-xyz-456',
            'platform'      => 'android',
            'registered_by' => $userB->id,  // attacker injects a different registrant
        ])
        ->assertCreated();

    expect(UserDevice::first()->registered_by)->toBe($userA->id);
});

// ── destroy ───────────────────────────────────────────────────────────────────

it('user can delete their own device', function () {
    $user = User::factory()->create();

    $device = new UserDevice(['device_token' => 'token-delete-me', 'platform' => 'web']);
    $device->registered_by = $user->id;
    $device->save();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/devices/{$device->id}")
        ->assertNoContent();

    expect(UserDevice::find($device->id))->toBeNull();
});

it('user cannot delete another user\'s device', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $device = new UserDevice(['device_token' => 'token-owned-by-a', 'platform' => 'ios']);
    $device->registered_by = $userA->id;
    $device->save();

    $this->actingAs($userB, 'sanctum')
        ->deleteJson("/api/devices/{$device->id}")
        ->assertForbidden();

    // Device must still exist
    expect(UserDevice::find($device->id))->not->toBeNull();
});

it('invalid platform is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/devices', [
            'device_token' => 'token-bad-platform',
            'platform'     => 'windows',
        ])
        ->assertUnprocessable();
});
