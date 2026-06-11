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
        // firstOrCreate behavior — owner_id is set directly because it is not in $fillable
        $profile = UserProfile::where('owner_id', $request->user()->id)->first();

        if (! $profile) {
            $profile = new UserProfile();
            $profile->owner_id = $request->user()->id;
            $profile->save();
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

        $profile = UserProfile::where('owner_id', $request->user()->id)->first();

        if ($profile) {
            $this->authorize('update', $profile);
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
