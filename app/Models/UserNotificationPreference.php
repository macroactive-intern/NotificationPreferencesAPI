<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'channel',
        'event_type',
        'enabled',
        'quiet_hours_start',
        'quiet_hours_end',
    ];
}
