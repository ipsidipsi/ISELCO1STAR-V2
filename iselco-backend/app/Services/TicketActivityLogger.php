<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Models\Comment;
use App\Models\Attachment;

class TicketActivityLogger
{
    /**
     * Log ticket creation
     */
    public static function logCreated(Ticket $ticket, User $user): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'ticket_created',
            'description' => "{$user->employee_name} created this ticket",
            'metadata' => [
                'status' => $ticket->status,
                'priority' => $ticket->priority?->name,
            ],
        ]);
    }

    /**
     * Log status change
     */
    public static function logStatusChange(Ticket $ticket, string $oldStatus, string $newStatus, User $user): void
    {
        $oldStatusFormatted = str_replace('_', ' ', ucfirst($oldStatus));
        $newStatusFormatted = str_replace('_', ' ', ucfirst($newStatus));

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'description' => "{$user->employee_name} changed status from {$oldStatusFormatted} to {$newStatusFormatted}",
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
        ]);
    }

    /**
     * Log ticket assignment
     */
    public static function logAssigned(Ticket $ticket, User $assignee, User $actor): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'activity_type' => 'assigned',
            'description' => "{$actor->employee_name} assigned this ticket to {$assignee->employee_name}",
            'metadata' => [
                'assignee_id' => $assignee->id,
                'assignee_name' => $assignee->employee_name,
            ],
        ]);
    }

    /**
     * Log ticket reassignment
     */
    public static function logReassigned(Ticket $ticket, User $oldAssignee, User $newAssignee, User $actor): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'activity_type' => 'reassigned',
            'description' => "{$actor->employee_name} reassigned from {$oldAssignee->employee_name} to {$newAssignee->employee_name}",
            'metadata' => [
                'old_assignee_id' => $oldAssignee->id,
                'new_assignee_id' => $newAssignee->id,
            ],
        ]);
    }

    /**
     * Log start work
     */
    public static function logStarted(Ticket $ticket, User $user): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'started',
            'description' => "{$user->employee_name} started working on this ticket",
        ]);
    }

    /**
     * Log resolution
     */
    public static function logResolved(Ticket $ticket, User $user, ?string $notes = null): void
    {
        $description = "{$user->employee_name} marked this ticket as resolved";
        if ($notes) {
            $description .= " with notes: \"{$notes}\"";
        }

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'resolved',
            'description' => $description,
            'metadata' => ['notes' => $notes],
        ]);
    }

    /**
     * Log verification (closing)
     */
    public static function logVerified(Ticket $ticket, User $user): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'verified',
            'description' => "{$user->employee_name} verified and closed this ticket",
        ]);
    }

    /**
     * Log reopening
     */
    public static function logReopened(Ticket $ticket, User $user, string $reason): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'reopened',
            'description' => "{$user->employee_name} rejected the solution and reopened: \"{$reason}\"",
            'metadata' => ['reason' => $reason],
        ]);
    }

    /**
     * Log comment addition
     */
    public static function logComment(Ticket $ticket, Comment $comment, User $user): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'comment_added',
            'description' => "{$user->employee_name} added a comment",
            'metadata' => [
                'comment_id' => $comment->id,
                'comment_preview' => substr($comment->message, 0, 100),
            ],
        ]);
    }

    /**
     * Log attachment upload
     */
    public static function logAttachment(Ticket $ticket, Attachment $attachment, User $user): void
    {
        $fileSizeMB = round($attachment->file_size / (1024 * 1024), 2);
        
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'attachment_uploaded',
            'description' => "{$user->employee_name} uploaded {$attachment->file_name} ({$fileSizeMB} MB)",
            'metadata' => [
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_size' => $attachment->file_size,
            ],
        ]);
    }
}
