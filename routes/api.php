<?php

use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\UserDeviceController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/notification-preferences/{channel}/{event}', [NotificationPreferenceController::class, 'update']);

    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);

    Route::post('/devices', [UserDeviceController::class, 'store']);
    Route::delete('/devices/{id}', [UserDeviceController::class, 'destroy']);
});
