<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $preferences = UserNotificationPreference::where('user_id', $request->user()->id)->get();

        return response()->json($preferences);
    }

    public function update(Request $request, string $channel, string $event): JsonResponse
    {
        $validated = $request->validate([
            'enabled'           => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_end'   => 'sometimes|nullable|date_format:H:i',
        ]);

        $preference = UserNotificationPreference::where('user_id', $request->user()->id)
            ->where('channel', $channel)
            ->where('event_type', $event)
            ->first();

        if ($preference) {
            $preference->fill($validated)->save();

            return response()->json($preference);
        }

        // Creating a new preference: ownership fields set directly, never from input
        $preference = new UserNotificationPreference($validated);
        $preference->user_id    = $request->user()->id;
        $preference->channel    = $channel;
        $preference->event_type = $event;
        $preference->save();

        return response()->json($preference, 201);
    }
}
