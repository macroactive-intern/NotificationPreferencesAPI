<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    // UNFIXED — $guarded = [] allows every column through fill(). Phase 5 replaces this with $fillable.
    protected $guarded = [];
}
