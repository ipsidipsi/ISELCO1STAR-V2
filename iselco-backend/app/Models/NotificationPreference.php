<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'is_muted',
        'web_push_enabled',
        'browser_enabled',
        'sound_enabled',
        'web_push_subscription'
    ];

    protected $casts = [
        'is_muted' => 'boolean',
        'web_push_enabled' => 'boolean',
        'browser_enabled' => 'boolean',
        'sound_enabled' => 'boolean',
        'web_push_subscription' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
