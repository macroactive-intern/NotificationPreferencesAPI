<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

// Automatically filters UserNotificationPreference queries to the authenticated user.
// Only applied when a user is logged in — safe for console, queued jobs, and tests
// that query outside a request context (e.g. mass-assignment tests using fresh()).
class OwnedByUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $builder->where('user_id', auth()->id());
        }
    }
}
