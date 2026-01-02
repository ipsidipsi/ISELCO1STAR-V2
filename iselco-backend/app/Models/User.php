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
        'status',
        'status_reason',
        'status_changed_at',
        'status_changed_by',
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
            'status_changed_at' => 'datetime',
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
     * Temporary roles assigned to this user (with expiry dates).
     * 
     * For "Officer in Charge" scenarios when someone is on leave
     */
    public function temporaryRoles()
    {
        return $this->belongsToMany(Role::class, 'role_user_temporary')
            ->withPivot('expires_at', 'assigned_by_user_id', 'reason')
            ->wherePivot('expires_at', '>', now())
            ->withTimestamps();
    }

    /**
     * All active roles (permanent + non-expired temporary).
     */
    public function allActiveRoles()
    {
        // Load permanent roles
        $permanentRoles = $this->roles;
        
        // Load temporary roles (automatically filters expired via wherePivot)
        $this->load('temporaryRoles');
        $temporaryRoles = $this->temporaryRoles;
        
        // Merge and return unique roles
        return $permanentRoles->merge($temporaryRoles)->unique('id');
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
     * Includes both permanent and non-expired temporary assignments.
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot('is_supervisor', 'expires_at', 'assigned_by_user_id', 'reason')
            ->withTimestamps();
    }

    /**
     * Get only active department assignments (permanent + non-expired temporary).
     */
    public function activeDepartments()
    {
        return $this->departments()
            ->where(function ($query) {
                $query->whereNull('department_user.expires_at')
                    ->orWhere('department_user.expires_at', '>', now());
            });
    }

    /**
     * Departments where this user is a supervisor.
     * Includes both permanent and non-expired temporary supervisor assignments.
     */
    public function supervisedDepartments()
    {
        return $this->activeDepartments()->wherePivot('is_supervisor', true);
    }

    /**
     * User who last changed this user's status.
     */
    public function statusChangedBy()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
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

    /**
     * User's notification preferences.
     */
    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    // ==================== RBAC METHODS ====================

    /**
     * Check if user has a specific role.
     * 
     * Checks both permanent and active temporary roles
     * 
     * @param string $roleSlug Role slug (e.g., 'superadmin', 'department_admin')
     * @return bool
     */
    public function hasRole(string $roleSlug): bool
    {
        // Check permanent roles
        $hasPermanent = $this->roles()->where('slug', $roleSlug)->exists();
        
        if ($hasPermanent) {
            return true;
        }
        
        // Check active temporary roles
        return $this->temporaryRoles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles.
     * 
     * Checks both permanent and active temporary roles
     * 
     * @param array $roleSlugs Array of role slugs
     * @return bool
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        // Check permanent roles
        $hasPermanent = $this->roles()->whereIn('slug', $roleSlugs)->exists();
        
        if ($hasPermanent) {
            return true;
        }
        
        // Check active temporary roles
        return $this->temporaryRoles()->whereIn('slug', $roleSlugs)->exists();
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
     * - User supervises this department (permanent or temporary)
     * - User is assigned to this department (permanent or non-expired temporary)
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

        // Check if user is assigned to this department (including temporary)
        return $this->activeDepartments()
            ->where('departments.id', $departmentId)
            ->exists();
    }

    /**
     * Get all department IDs this user can access.
     * 
     * @return array
     */
    public function getAccessibleDepartmentIds(): array
    {
        if ($this->isSuperadmin()) {
            return Department::pluck('id')->toArray();
        }

        $departmentIds = [];
        
        // Add primary department
        if ($this->department_id) {
            $departmentIds[] = $this->department_id;
        }
        
        // Add assigned departments (permanent + active temporary)
        $assignedIds = $this->activeDepartments()->pluck('departments.id')->toArray();
        $departmentIds = array_merge($departmentIds, $assignedIds);
        
        return array_unique($departmentIds);
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

    // ==================== STATUS MANAGEMENT ====================

    /**
     * Suspend user account (can be reactivated).
     * 
     * @param string|null $reason
     * @param int|null $changedBy
     * @return void
     */
    public function suspend(?string $reason = null, ?int $changedBy = null): void
    {
        $this->update([
            'status' => 'suspended',
            'is_active' => false,
            'status_reason' => $reason,
            'status_changed_at' => now(),
            'status_changed_by' => $changedBy ?? auth()->id(),
        ]);
    }

    /**
     * Mark user as on leave.
     * 
     * @param string|null $reason
     * @param int|null $changedBy
     * @return void
     */
    public function markOnLeave(?string $reason = null, ?int $changedBy = null): void
    {
        $this->update([
            'status' => 'on_leave',
            'is_active' => false,
            'status_reason' => $reason,
            'status_changed_at' => now(),
            'status_changed_by' => $changedBy ?? auth()->id(),
        ]);
    }

    /**
     * Retire user (soft delete).
     * 
     * @param string|null $reason
     * @param int|null $changedBy
     * @return void
     */
    public function retire(?string $reason = null, ?int $changedBy = null): void
    {
        $this->update([
            'status' => 'retired',
            'is_active' => false,
            'status_reason' => $reason ?? 'Employee retired',
            'status_changed_at' => now(),
            'status_changed_by' => $changedBy ?? auth()->id(),
        ]);

        // Soft delete the user
        $this->delete();
    }

    /**
     * Terminate user employment (soft delete).
     * 
     * @param string|null $reason
     * @param int|null $changedBy
     * @return void
     */
    public function terminate(?string $reason = null, ?int $changedBy = null): void
    {
        $this->update([
            'status' => 'terminated',
            'is_active' => false,
            'status_reason' => $reason ?? 'Employment terminated',
            'status_changed_at' => now(),
            'status_changed_by' => $changedBy ?? auth()->id(),
        ]);

        // Soft delete the user
        $this->delete();
    }

    /**
     * Reactivate user account.
     * 
     * @param string|null $reason
     * @param int|null $changedBy
     * @return void
     */
    public function reactivate(?string $reason = null, ?int $changedBy = null): void
    {
        // Restore if soft deleted
        if ($this->trashed()) {
            $this->restore();
        }

        $this->update([
            'status' => 'active',
            'is_active' => true,
            'status_reason' => $reason,
            'status_changed_at' => now(),
            'status_changed_by' => $changedBy ?? auth()->id(),
        ]);
    }

    /**
     * Check if user is currently employable (active or on leave).
     * 
     * @return bool
     */
    public function isEmployable(): bool
    {
        return in_array($this->status, ['active', 'on_leave']);
    }

    /**
     * Check if user is permanently separated (retired or terminated).
     * 
     * @return bool
     */
    public function isPermanentlySeparated(): bool
    {
        return in_array($this->status, ['retired', 'terminated']);
    }
}
