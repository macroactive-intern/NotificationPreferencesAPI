<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserProfile;

class UserProfilePolicy
{
    public function update(User $user, UserProfile $profile): bool
    {
        return $user->id === $profile->owner_id;
    }
}
