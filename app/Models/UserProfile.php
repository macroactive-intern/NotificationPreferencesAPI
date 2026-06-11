<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'display_name',
        'timezone',
        'locale',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
