<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Category Model
 * 
 * Represents ticket categories
 * Can be department-specific or global (available to all departments)
 * 
 * Examples:
 * - "Computer Repair" (IT department only)
 * - "Password Reset" (Global)
 * - "Leave Request" (HR department only)
 */
class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'department_id',
        'priority_id',
        'icon',
        'color',
        'is_active',
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
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * The department this category belongs to (null if global).
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The default priority for this category.
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    /**
     * Tickets using this category.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only get active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get global categories (available to all departments).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('department_id');
    }

    /**
     * Scope to get department-specific categories.
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to get categories available to a specific department.
     * Includes both global and department-specific categories.
     */
    public function scopeAvailableForDepartment($query, int $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->whereNull('department_id') // Global categories
              ->orWhere('department_id', $departmentId); // Department-specific
        })->where('is_active', true);
    }
}
