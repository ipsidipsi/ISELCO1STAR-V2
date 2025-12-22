<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Department Model
 * 
 * Represents organizational departments
 * Data is synced from external API: http://26.183.28.177:8000/api/employees/departments
 * 
 * Departments are used for:
 * - Ticket routing
 * - User organization
 * - Permission scoping
 */
class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'synced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Users whose primary department is this one.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * All users assigned to this department (including supervisors).
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withPivot('is_supervisor')
            ->withTimestamps();
    }

    /**
     * Users who supervise this department.
     */
    public function supervisors()
    {
        return $this->assignedUsers()->wherePivot('is_supervisor', true);
    }

    /**
     * Tickets assigned to this department.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Categories specific to this department.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only get active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
