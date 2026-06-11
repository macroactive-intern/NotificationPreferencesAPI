<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotificationPreference;

class UserNotificationPreferencePolicy
{
    public function update(User $user, UserNotificationPreference $preference): bool
    {
        return $user->id === $preference->user_id;
    }
}
