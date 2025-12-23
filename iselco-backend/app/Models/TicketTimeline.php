<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTimeline extends Model
{
    public $timestamps = false; // We manually set created_at

    protected $fillable = [
        'ticket_id',
        'status_from',
        'status_to',
        'user_id',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
