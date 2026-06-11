<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = [
        'device_token',
        'platform',
        'active',
    ];

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
