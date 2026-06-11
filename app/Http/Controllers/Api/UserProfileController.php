<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = UserProfile::where('owner_id', $request->user()->id)->first();

        if (! $profile) {
            return response()->json(null, 204);
        }

        return response()->json($profile);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => 'sometimes|nullable|string|max:255',
            'timezone'     => 'sometimes|nullable|string|max:100',
            'locale'       => 'sometimes|nullable|string|max:10',
        ]);

        $profile = UserProfile::where('owner_id', $request->user()->id)->first();

        if ($profile) {
            $profile->fill($validated)->save();

            return response()->json($profile);
        }

        // Creating a new profile: owner_id set directly, never from input
        $profile = new UserProfile($validated);
        $profile->owner_id = $request->user()->id;
        $profile->save();

        return response()->json($profile, 201);
    }
}
