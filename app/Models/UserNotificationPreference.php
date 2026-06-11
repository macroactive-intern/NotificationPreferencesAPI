<?php

namespace App\Models;

use App\Models\Scopes\OwnedByUserScope;
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

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OwnedByUserScope());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
