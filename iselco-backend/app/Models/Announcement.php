<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'image_path',
        'type', // all, department, selected_users
        'created_by',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $with = ['attachments', 'creator'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset(\Illuminate\Support\Facades\Storage::url($this->image_path)) : null;
    }

    // Relationships

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'announcement_departments');
    }

    public function targetUsers()
    {
        return $this->belongsToMany(User::class, 'announcement_users');
    }

    public function readByUsers()
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at');
    }

    public function attachments()
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // 1. Universal announcements
            $q->orWhere('type', 'all');

            // 2. Department-specific announcements
            $q->orWhere(function ($subQ) use ($user) {
                $subQ->where('type', 'department')
                     ->whereHas('departments', function ($deptQ) use ($user) {
                         // Check against user's accessible departments
                         $deptQ->whereIn('departments.id', $user->getAccessibleDepartmentIds());
                     });
            });

            // 3. Specific user announcements
            $q->orWhere(function ($subQ) use ($user) {
                $subQ->where('type', 'selected_users')
                     ->whereHas('targetUsers', function ($userQ) use ($user) {
                         $userQ->where('users.id', $user->id);
                     });
            });
        });
    }
}
