<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AnnouncementAttachment extends Model
{
    protected $fillable = [
        'announcement_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size'
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset(Storage::url($this->file_path)) : null;
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }
}
