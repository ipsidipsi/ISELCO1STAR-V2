<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Priority Model
 * 
 * Represents ticket priority levels
 * Used for sorting and SLA tracking
 * 
 * Standard levels:
 * 1 = Low
 * 2 = Medium
 * 3 = High
 * 4 = Critical/Urgent
 */
class Priority extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'level',
        'color',
        'sla_hours',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sla_hours' => 'integer',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Tickets with this priority.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Categories that use this as default priority.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to order by priority level (highest first).
     */
    public function scopeHighestFirst($query)
    {
        return $query->orderBy('level', 'desc');
    }

    /**
     * Scope to order by priority level (lowest first).
     */
    public function scopeLowestFirst($query)
    {
        return $query->orderBy('level', 'asc');
    }
}
