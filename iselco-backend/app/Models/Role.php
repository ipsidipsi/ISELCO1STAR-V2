<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Role Model
 * 
 * Represents a role in the RBAC system
 * Roles group permissions together for easier assignment
 * 
 * Examples:
 * - Superadmin: Full system access
 * - Department Admin: Department-scoped management
 * - User: Basic ticket creation and participation
 * - Custom roles created by superadmin
 */
class Role extends Model
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
        'is_system',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * The users that have this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * The permissions that belong to this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    // ==================== HELPER METHODS ====================

    /**
     * Grant a permission to this role.
     * 
     * @param Permission|string $permission Permission model or slug
     * @return void
     */
    public function givePermissionTo($permission): void
    {
        // If string slug is provided, find the permission
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }

        // Attach permission if not already attached
        if (!$this->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Revoke a permission from this role.
     * 
     * @param Permission|string $permission Permission model or slug
     * @return void
     */
    public function revokePermissionTo($permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }

    /**
     * Check if this role has a specific permission.
     * 
     * @param string $permissionSlug
     * @return bool
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Sync permissions for this role (replace all existing).
     * 
     * @param array $permissionIds Array of permission IDs
     * @return void
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only get system roles (cannot be deleted).
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to only get custom roles (can be modified/deleted).
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }
}
