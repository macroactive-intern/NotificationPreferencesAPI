<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => 'required|string|max:255|unique:user_devices,device_token',
            'platform'     => 'required|string|in:ios,android,web',
            'active'       => 'sometimes|boolean',
        ]);

        try {
            // registered_by comes from the authenticated session, never from request input
            $device                = new UserDevice($validated);
            $device->registered_by = $request->user()->id;
            $device->save();

            return response()->json($device, 201);
        } catch (UniqueConstraintViolationException) {
            // Token passed validation but another request inserted it in the same window
            return response()->json(
                ['message' => 'The device token has already been registered.'],
                422
            );
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // Ownership filter in the query prevents existence enumeration:
        // a device that doesn't exist and a device owned by another user both return 404.
        $device = UserDevice::where('id', $id)
            ->where('registered_by', $request->user()->id)
            ->first();

        if (! $device) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $device->delete();

        return response()->json(null, 204);
    }
}
