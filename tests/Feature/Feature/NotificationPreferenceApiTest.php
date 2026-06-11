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
        ->assertJsonCount(2, 'data');
});

it('user cannot see another user\'s preferences', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    makePreference($userA->id);

    $this->actingAs($userB, 'sanctum')
        ->getJson('/api/notification-preferences')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ── update ────────────────────────────────────────────────────────────────────

it('user can create a preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/email/newsletter', ['enabled' => false])
        ->assertCreated();

    expect(
        UserNotificationPreference::where('user_id', $user->id)->first()->enabled
    )->toBeFalse();
});

it('user can update an existing preference', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/email/newsletter', ['enabled' => true])
        ->assertCreated();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/email/newsletter', ['enabled' => false])
        ->assertOk();

    expect(
        UserNotificationPreference::where('user_id', $user->id)->first()->enabled
    )->toBeFalse();
});

it('quiet hours are stored and returned', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/push/alert', [
            'enabled'           => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end'   => '07:00',
        ])
        ->assertCreated()
        ->assertJsonFragment([
            'quiet_hours_start' => '22:00',
            'quiet_hours_end'   => '07:00',
        ]);
});

it('invalid quiet hours format is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/push/alert', [
            'quiet_hours_start' => '10pm',
        ])
        ->assertUnprocessable();
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

it('invalid event type is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/notification-preferences/email/bad event!', ['enabled' => true])
        ->assertUnprocessable();
});
