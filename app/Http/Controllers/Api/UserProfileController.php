<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $profile = UserProfile::where('owner_id', $userId)->first();

        if (! $profile) {
            try {
                $profile           = new UserProfile();
                $profile->owner_id = $userId;
                $profile->save();
            } catch (UniqueConstraintViolationException) {
                // Concurrent request won the race — read the row it created
                $profile = UserProfile::where('owner_id', $userId)->first();
            }
        }

        return response()->json($profile);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => 'sometimes|nullable|string|max:255',
            'timezone'     => 'sometimes|nullable|string|max:255',
            'locale'       => 'sometimes|nullable|string|max:50',
        ]);

        $userId  = $request->user()->id;
        $profile = UserProfile::where('owner_id', $userId)->first();

        if ($profile) {
            $this->authorize('update', $profile);
            $profile->fill($validated)->save();

            return response()->json($profile);
        }

        try {
            $profile           = new UserProfile($validated);
            $profile->owner_id = $userId;
            $profile->save();

            return response()->json($profile, 201);
        } catch (UniqueConstraintViolationException) {
            // Concurrent request created the profile — update the row it created
            $profile = UserProfile::where('owner_id', $userId)->first();
            $this->authorize('update', $profile);
            $profile->fill($validated)->save();

            return response()->json($profile);
        }
    }
}
