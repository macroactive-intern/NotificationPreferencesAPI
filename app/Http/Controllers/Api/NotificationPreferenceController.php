<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(UserNotificationPreference::all());
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

        // Ownership fields set directly, never via fill(), because user_id is
        // intentionally absent from $fillable.
        $userId     = $request->user()->id;
        $preference = UserNotificationPreference::where('user_id', $userId)
            ->where('channel', $channel)
            ->where('event_type', $event)
            ->first();

        if ($preference) {
            $this->authorize('update', $preference);
            $preference->fill($validated)->save();

            return response()->json($preference);
        }

        try {
            $preference             = new UserNotificationPreference($validated);
            $preference->user_id    = $userId;
            $preference->channel    = $channel;
            $preference->event_type = $event;
            $preference->save();

            return response()->json($preference, 201);
        } catch (UniqueConstraintViolationException) {
            // Concurrent request created the same record — update the row it created
            $preference = UserNotificationPreference::where('user_id', $userId)
                ->where('channel', $channel)
                ->where('event_type', $event)
                ->first();
            $this->authorize('update', $preference);
            $preference->fill($validated)->save();

            return response()->json($preference);
        }
    }
}
