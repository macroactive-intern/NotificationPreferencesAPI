<?php

// Verify that every endpoint rejects unauthenticated requests.
// These tests catch accidental middleware removal or routing mistakes.

it('unauthenticated request to notification preferences index is rejected', function () {
    $this->getJson('/api/notification-preferences')->assertUnauthorized();
});

it('unauthenticated request to notification preferences update is rejected', function () {
    $this->putJson('/api/notification-preferences/email/newsletter', ['enabled' => true])
        ->assertUnauthorized();
});

it('unauthenticated request to profile show is rejected', function () {
    $this->getJson('/api/profile')->assertUnauthorized();
});

it('unauthenticated request to profile update is rejected', function () {
    $this->putJson('/api/profile', ['display_name' => 'Test'])->assertUnauthorized();
});

it('unauthenticated request to device store is rejected', function () {
    $this->postJson('/api/devices', ['device_token' => 'token', 'platform' => 'ios'])
        ->assertUnauthorized();
});

it('unauthenticated request to device destroy is rejected', function () {
    $this->deleteJson('/api/devices/1')->assertUnauthorized();
});
