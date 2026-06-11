# SECURITY-CHECKLIST.md

## Mass assignment rules

Mass assignment is what happens when you pass an array of user-supplied data directly
into `Model::create()` or `$model->fill()`. Eloquent copies every key in that array
straight onto the model's attributes. If a malicious caller includes a key you didn't
intend — like `user_id` or `is_admin` — and the model doesn't explicitly block it,
that value lands in the database.

The two properties that control which fields survive this process are `$fillable` and
`$guarded`. **Every model in this project must declare one of them.** A model with
neither risks having every attribute silently accepted from user input.

Rules:
- Every model declares a `$fillable` array listing only the fields the user is
  permitted to supply.
- `$guarded = []` is **banned** in this project (see the dedicated section below).
- Ownership fields (`user_id`, `owner_id`, `registered_by`) must **never** appear in
  any `$fillable` array anywhere in the codebase.
- These three fields are assigned in application code only — never from request input.

---

## Fields that must never be mass-assignable

The following fields establish who owns a record. Allowing them through mass assignment
lets any caller lie about ownership by injecting a different ID into the request body.

| Field | Appears on | Why it is dangerous if mass-assignable |
|---|---|---|
| `user_id` | `UserNotificationPreference` | Caller could claim records belong to a different user |
| `owner_id` | `UserProfile` | Caller could transfer profile ownership to any account |
| `registered_by` | `UserDevice` | Caller could falsely attribute device registration to another user |

None of these fields may appear in a `$fillable` array. Grep enforcement rule:

```
# This pattern must produce zero results across the entire codebase:
grep -rn "user_id\|owner_id\|registered_by" app/Models --include="*.php" \
  | grep "\$fillable"
```

---

## Ownership assignment rules

Ownership fields are set by the application after it has verified who the authenticated
user is. The only safe source of truth is `$request->user()->id` (or the equivalent
authenticated identity), never anything from the request body.

**The pattern to follow in every controller / action:**

```php
// CORRECT — instantiate the model, set ownership directly, then fill safe fields
$preference = new UserNotificationPreference($request->validated());
$preference->user_id = $request->user()->id;  // set after fill, from the session
$preference->save();

// WRONG — never pass ownership fields through create() or fill()
$preference = UserNotificationPreference::create($request->all());
// or
$preference = UserNotificationPreference::create([
    ...$request->validated(),
    'user_id' => $request->input('user_id'),  // user-supplied — banned
]);
```

> **Why `create([..., 'user_id' => $request->user()->id])` does NOT work:**
> `create()` calls `fill()` internally. Since `user_id` is absent from `$fillable`, Eloquent
> silently discards it — the record would be saved with a NULL `user_id` and hit the NOT NULL
> constraint. Always set ownership fields via direct property assignment **after** `fill()`.

Rules:
- `user_id` is always taken from `Auth::id()` or `$request->user()->id`.
- `owner_id` is always taken from `Auth::id()` at the time of record creation.
- `registered_by` is always taken from `Auth::id()` at the time of device registration.
- None of these values is read from `$request->input()`, `$request->all()`, or
  `$request->validated()`.

---

## Testing rules

Tests must prove that ownership fields cannot be overridden by a request payload,
regardless of what the caller sends. Each model needs at least the following two test
shapes:

**1 — Ownership is taken from the authenticated user, not the request body**

```php
it('ignores user_id supplied in the request body', function () {
    $realUser   = User::factory()->create();
    $otherUser  = User::factory()->create();

    $response = actingAs($realUser)
        ->postJson('/api/preferences', [
            'channel'  => 'email',
            'enabled'  => true,
            'user_id'  => $otherUser->id,   // attacker tries to claim another user's ID
        ]);

    $response->assertCreated();

    expect(UserNotificationPreference::first()->user_id)
        ->toBe($realUser->id);          // must be the authenticated user
});
```

**2 — A user cannot read or mutate another user's records**

```php
it('returns 403 when accessing another user\'s preference', function () {
    $owner    = User::factory()->create();
    $attacker = User::factory()->create();

    $preference = UserNotificationPreference::factory()
        ->for($owner)
        ->create();

    actingAs($attacker)
        ->getJson("/api/preferences/{$preference->id}")
        ->assertForbidden();
});
```

Rules:
- Every ownership-bearing model must have a test for each shape above.
- `UserDevice` must have an additional test confirming `registered_by` is set from the
  authenticated session and cannot be overridden via the request body.
- All tests run against the in-memory SQLite database (`DB_DATABASE=:memory:`).
- Tests must not share state — use `RefreshDatabase` or Pest's `uses(RefreshDatabase::class)`.

---

## Fillable vs guarded

### `$fillable`

`$fillable` is an explicit **allowlist**. You write down every column that is safe to
accept from user-supplied input:

```php
protected $fillable = ['channel', 'enabled', 'frequency'];
```

When `create()` or `fill()` is called with an array, Eloquent silently drops any key
that is not on this list. Fields like `user_id` simply never make it through —
even if the caller includes them — because they are absent from `$fillable`.

This is the correct approach for this project because:
- The list of safe fields is small and well-understood.
- Adding a new field requires a conscious, deliberate edit to `$fillable`.
- There is no way to accidentally expose a column that was added to the database later.

### `$guarded`

`$guarded` is an explicit **denylist**. You write down every column that must be
blocked, and everything else passes through:

```php
protected $guarded = ['user_id', 'owner_id'];
```

This inverts the safety model: fields are accepted by default, blocked by exception.
The problem is that "everything else" includes any column you add to the table in the
future. A developer who adds a new migration column — say `is_verified` or `role` —
and forgets to update `$guarded` has silently opened a mass-assignment vector.

`$guarded` is acceptable only when you need a quick admin-only scaffold and you
understand the tradeoff. It is not acceptable for user-facing models in this API.

---

## Why guarded empty is dangerous

`$guarded = []` means: **block nothing**. Every column in the table is mass-assignable,
including `user_id`, `owner_id`, `registered_by`, and any column added by a future
migration.

```php
// With $guarded = [], this is silently accepted:
UserDevice::create($request->all());
// A crafted request body of {"token":"abc","registered_by":1} sets registered_by = 1
// regardless of who is actually authenticated.
```

The danger compounds over time. Every new migration column is immediately exposed
without any action required from the attacker. A developer who adds `verified_at` to
`user_devices` has made it mass-assignable the moment the migration runs, even if no
one intended that.

`$guarded = []` is explicitly banned in this project. The linter / code-review checklist
must reject any PR that introduces it on a user-facing model.

### Why `UserDevice` must not use `$guarded = []`

`UserDevice` stores push tokens and device metadata tied to a specific user via
`registered_by` and `user_id`. These two fields are the only thing preventing one
user's notification from being delivered to another user's device.

With `$guarded = []`:
- A caller can POST `{"token":"victim_token","registered_by":99}` to register a device
  under a different user's identity.
- A caller can PATCH an existing device record and silently reassign `user_id` to any
  other account.
- Notification dispatch that queries `UserDevice` by `user_id` would then send pushes
  to the wrong device.

The fix is a narrow `$fillable` that lists only `token`, `platform`, `name`, and
similar descriptive fields. `registered_by` and `user_id` are set in the controller
from `Auth::id()` and never accepted from the request body.

---

## Pre-model sign-off

Before creating `UserNotificationPreference`, `UserProfile`, or `UserDevice`:

- [ ] Every developer on the team has read this document
- [ ] `$fillable` vs `$guarded` decision is understood and agreed
- [ ] Ownership fields (`user_id`, `owner_id`, `registered_by`) confirmed absent from all `$fillable` arrays
- [ ] Controller pattern for safe ownership assignment reviewed
- [ ] Both test shapes (ownership-from-session, 403-on-wrong-user) are written for each model before any feature code
