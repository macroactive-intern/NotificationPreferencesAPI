<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Auth\Access\Response;

class UserNotificationPreferencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserNotificationPreference $userNotificationPreference): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, UserNotificationPreference $userNotificationPreference): bool
    {
        return $user->id === $userNotificationPreference->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserNotificationPreference $userNotificationPreference): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserNotificationPreference $userNotificationPreference): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserNotificationPreference $userNotificationPreference): bool
    {
        return false;
    }
}
