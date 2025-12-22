<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 * 
 * Represents system users (employees synced from external API)
 * Supports RBAC (Role-Based Access Control) with multiple roles
 * Authentication via mobile_number instead of email
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * Fields synced from external API:
     * - employee_name, empbadge_number, department_id
     * 
     * Fields managed internally:
     * - username, mobile_number, password, is_active, must_change_password
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'mobile_number',
        'employee_name',
        'empbadge_number',
        'department_id',
        'is_active',
        'must_change_password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * The roles that belong to the user.
     * 
     * A user can have multiple roles (e.g., User + Department Admin)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Get all permissions for this user through their roles.
     * 
     * Aggregates permissions from all assigned roles
     */
    public function permissions()
    {
        return $this->hasManyThrough(
            Permission::class,
            Role::class,
            'id', // Foreign key on roles table
            'id', // Foreign key on permissions table
            'id', // Local key on users table
            'id'  // Local key on roles table
        )->distinct();
    }

    /**
     * The primary department this user belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * All departments this user is assigned to (for admins supervising multiple departments).
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot('is_supervisor')
            ->withTimestamps();
    }

    /**
     * Departments where this user is a supervisor.
     */
    public function supervisedDepartments()
    {
        return $this->departments()->wherePivot('is_supervisor', true);
    }

    /**
     * Tickets created by this user (as requestor).
     */
    public function createdTickets()
    {
        return $this->hasMany(Ticket::class, 'requestor_id');
    }

    /**
     * Tickets assigned to this user (as fixer).
     */
    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to_id');
    }

    /**
     * Comments posted by this user.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Files uploaded by this user.
     */
    public function uploadedAttachments()
    {
        return $this->hasMany(Attachment::class, 'uploaded_by_user_id');
    }

    // ==================== RBAC METHODS ====================

    /**
     * Check if user has a specific role.
     * 
     * @param string $roleSlug Role slug (e.g., 'superadmin', 'department_admin')
     * @return bool
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles.
     * 
     * @param array $roleSlugs Array of role slugs
     * @return bool
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Check if user has a specific permission.
     * 
     * Checks through all user's roles
     * 
     * @param string $permissionSlug Permission slug (e.g., 'tickets.view.all')
     * @return bool
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Superadmin has all permissions
        if ($this->isSuperadmin()) {
            return true;
        }

        // Check if any of user's roles has this permission
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Check if user has any of the given permissions.
     * 
     * @param array $permissionSlugs Array of permission slugs
     * @return bool
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlugs) {
                $query->whereIn('slug', $permissionSlugs);
            })
            ->exists();
    }

    /**
     * Check if user can access a specific department.
     * 
     * True if:
     * - User is superadmin
     * - User's primary department matches
     * - User supervises this department
     * 
     * @param int $departmentId
     * @return bool
     */
    public function canAccessDepartment(int $departmentId): bool
    {
        // Superadmin can access all departments
        if ($this->isSuperadmin()) {
            return true;
        }

        // Check if it's user's primary department
        if ($this->department_id === $departmentId) {
            return true;
        }

        // Check if user supervises this department
        return $this->supervisedDepartments()
            ->where('departments.id', $departmentId)
            ->exists();
    }

    // ==================== ACCESSORS ====================

    /**
     * Check if user is a superadmin.
     * 
     * @return bool
     */
    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Check if user is a department admin.
     * 
     * @return bool
     */
    public function isDepartmentAdmin(): bool
    {
        return $this->hasRole('department_admin');
    }

    /**
     * Get user's full display name.
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->employee_name ?? $this->username;
    }
}
