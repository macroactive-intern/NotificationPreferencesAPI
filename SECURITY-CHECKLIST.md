# Security Checklist — L18 Notification Preferences API

## Authentication & Authorization
- [ ] All endpoints require authenticated user (Sanctum or Passport token)
- [ ] Users can only read/write their own notification preferences, profiles, and devices
- [ ] No mass-assignment vulnerabilities — `$fillable` or `$guarded` defined on every model
- [ ] Policy classes enforce ownership before any database mutation

## Input Validation
- [ ] All request inputs validated with Form Request classes
- [ ] Enum values validated against a strict allowlist (notification channels, preference types)
- [ ] Device token / push token length and format validated
- [ ] No raw user input passed to queries — use Eloquent or parameterised query builder only

## Data Exposure
- [ ] API resources used on all responses — never return raw model `toArray()`
- [ ] Sensitive fields (e.g. `remember_token`, `password`) excluded from resource output
- [ ] No stack traces or debug output in production (`APP_DEBUG=false`)

## Database
- [ ] Every migration column has appropriate constraints (`not null`, `unique`, `foreign key`)
- [ ] Foreign key constraints defined so orphaned rows are prevented at the DB level
- [ ] Soft-deletes considered for user data (GDPR / right-to-erasure)

## Secrets & Configuration
- [ ] `.env` and `.env.testing` are in `.gitignore` — never committed
- [ ] `APP_KEY` rotated before first production deployment
- [ ] No credentials hard-coded anywhere in source

## Rate Limiting
- [ ] Throttle middleware applied to all public/auth routes
- [ ] Higher throttle limits for authenticated write operations reviewed

## Headers & Transport
- [ ] HTTPS enforced in production (`FORCE_HTTPS=true` or server config)
- [ ] `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` headers set

## Testing
- [ ] Each model has a corresponding factory
- [ ] Feature tests cover happy path and forbidden (403) path for ownership checks
- [ ] No test uses production database — `DB_DATABASE=:memory:` in `.env.testing`

## Pre-launch Sign-off
- [ ] `php artisan route:list` reviewed — no unintended public routes
- [ ] `composer audit` clean
- [ ] All checklist items above checked off
