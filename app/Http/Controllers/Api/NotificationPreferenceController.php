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
        // Validate the route parameter — channel comes from the URL, not the body
        validator(['channel' => $channel], [
            'channel' => 'required|in:email,push,sms',
        ])->validate();

        $validated = $request->validate([
            'enabled'           => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_end'   => 'sometimes|nullable|date_format:H:i',
        ]);

        // Equivalent to updateOrCreate — ownership fields set directly after the query,
        // never via fill(), because user_id is intentionally absent from $fillable.
        $preference = UserNotificationPreference::where('user_id', $request->user()->id)
            ->where('channel', $channel)
            ->where('event_type', $event)
            ->first();

        if ($preference) {
            $preference->fill($validated)->save();

            return response()->json($preference);
        }

        $preference = new UserNotificationPreference($validated);
        $preference->user_id    = $request->user()->id;
        $preference->channel    = $channel;
        $preference->event_type = $event;
        $preference->save();

        return response()->json($preference, 201);
    }
}
