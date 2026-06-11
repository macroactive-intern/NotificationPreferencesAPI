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
        // Validate both route parameters — they come from the URL, not the body
        validator(
            ['channel' => $channel, 'event' => $event],
            [
                'channel' => 'required|in:email,push,sms',
                'event'   => 'required|string|max:100|alpha_dash',
            ]
        )->validate();

        $validated = $request->validate([
            'enabled'           => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_end'   => 'sometimes|nullable|date_format:H:i',
        ]);

        // OwnedByUserScope already filters all queries to the authenticated user,
        // so no explicit user_id WHERE clause is needed here.
        $userId     = $request->user()->id;
        $preference = UserNotificationPreference::where('channel', $channel)
            ->where('event_type', $event)
            ->first();

        if ($preference) {
            $preference->fill($validated)->save();

            return response()->json($preference);
        }

        try {
            // Ownership field set directly — user_id is absent from $fillable
            $preference             = new UserNotificationPreference($validated);
            $preference->user_id    = $userId;
            $preference->channel    = $channel;
            $preference->event_type = $event;
            $preference->save();

            return response()->json($preference, 201);
        } catch (UniqueConstraintViolationException) {
            // Concurrent request created the same record — update the row it created
            $preference = UserNotificationPreference::where('channel', $channel)
                ->where('event_type', $event)
                ->first();
            $preference->fill($validated)->save();

            return response()->json($preference);
        }
    }
}
