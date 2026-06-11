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
        $profile = UserProfile::where('owner_id', $request->user()->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
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
            $profile->fill($validated)->save();

            return response()->json($profile);
        }

        try {
            // Ownership field set directly — owner_id is absent from $fillable
            $profile           = new UserProfile($validated);
            $profile->owner_id = $userId;
            $profile->save();

            return response()->json($profile, 201);
        } catch (UniqueConstraintViolationException) {
            // Concurrent request created the profile — update the row it created
            $profile = UserProfile::where('owner_id', $userId)->first();
            $profile->fill($validated)->save();

            return response()->json($profile);
        }
    }
}
