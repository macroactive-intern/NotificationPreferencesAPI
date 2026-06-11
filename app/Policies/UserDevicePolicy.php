<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Auth\Access\Response;

class UserDevicePolicy
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
    public function view(User $user, UserDevice $userDevice): bool
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

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserDevice $userDevice): bool
    {
        return false;
    }

    public function delete(User $user, UserDevice $userDevice): bool
    {
        return $user->id === $userDevice->registered_by;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserDevice $userDevice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserDevice $userDevice): bool
    {
        return false;
    }
}
