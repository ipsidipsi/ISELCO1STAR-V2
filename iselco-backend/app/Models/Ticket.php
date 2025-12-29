<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'status',
        'priority_id',
        'category_id',
        'department_id',
        'requestor_id',
        'assigned_to_id',
        'assigned_at',
        'started_at',
        'resolved_at',
        'closed_at',
        'reopened_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    // Relationships
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(TicketTimeline::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
