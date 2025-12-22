<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Permission Model
 * 
 * Represents a single permission in the RBAC system
 * Permissions define specific actions users can perform
 * 
 * Examples:
 * - tickets.create
 * - tickets.view.all
 * - tickets.assign
 * - users.manage
 * - reports.export
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * The roles that have this permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter permissions by category.
     * 
     * Categories group permissions for UI display
     * Examples: Tickets, Users, Reports, Settings
     * 
     * @param $query
     * @param string $category
     * @return mixed
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get all permission categories.
     * 
     * @param $query
     * @return array
     */
    public function scopeGetCategories($query): array
    {
        return $query->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
    }
}
