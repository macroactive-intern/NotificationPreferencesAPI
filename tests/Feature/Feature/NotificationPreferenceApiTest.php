<?php

use App\Models\User;
use App\Models\UserNotificationPreference;

// ── helpers ──────────────────────────────────────────────────────────────────

function makePreference(int $userId, string $channel = 'email', string $event = 'newsletter'): UserNotificationPreference
{
    $pref = new UserNotificationPreference(['channel' => $channel, 'event_type' => $event, 'enabled' => true]);
    $pref->user_id = $userId;
    $pref->save();

    return $pref;
}

// ── index ─────────────────────────────────────────────────────────────────────

it('authenticated user can list only their own preferences', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    makePreference($userA->id, 'email', 'newsletter');
    makePreference($userA->id, 'push',  'alert');
    makePreference($userB->id, 'sms',   'promo');

    $this->actingAs($userA, 'sanctum')
        ->getJson('/api/notification-preferences')
        ->assertOk()
        ->assertJsonCount(2);
});

it('user cannot see another user\'s preferences', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    makePreference($userA->id);

    $this->actingAs($userB, 'sanctum')
        ->getJson('/api/notification-preferences')
        ->assertOk()
        ->assertJsonCount(0);
});

// ── update ────────────────────────────────────────────────────────────────────

it('user can update their own preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/email/newsletter', ['enabled' => false])
        ->assertCreated();

    expect(
        UserNotificationPreference::where('user_id', $user->id)->first()->enabled
    )->toBeFalse();
});

it('request cannot override user_id', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA, 'sanctum')
        ->putJson('/api/notification-preferences/email/newsletter', [
            'enabled' => true,
            'user_id' => $userB->id,   // attacker injects a different user
        ])
        ->assertSuccessful();

    expect(UserNotificationPreference::first()->user_id)->toBe($userA->id);
});

it('invalid channel is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/fax/newsletter', ['enabled' => true])
        ->assertUnprocessable();
});
