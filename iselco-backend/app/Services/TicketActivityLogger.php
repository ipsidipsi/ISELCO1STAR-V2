<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Models\Comment;
use App\Models\Attachment;

use App\Events\TicketActivityLogged;

class TicketActivityLogger
{
    /**
     * Helper to create activity and dispatch event
     */
    private static function createAndLog(array $data): void
    {
        $activity = TicketActivity::create($data);
        event(new TicketActivityLogged($activity));
    }

    /**
     * Get user display name (employee_name or username fallback)
     */
    private static function getUserName(User $user): string
    {
        return $user->employee_name ?: $user->username;
    }

    /**
     * Log ticket creation
     */
    public static function logCreated(Ticket $ticket, User $user): void
    {
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'ticket_created',
            'description' => self::getUserName($user) . " created this ticket",
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

        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'description' => self::getUserName($user) . " changed status from {$oldStatusFormatted} to {$newStatusFormatted}",
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
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'activity_type' => 'assigned',
            'description' => self::getUserName($actor) . " assigned this ticket to " . self::getUserName($assignee),
            'metadata' => [
                'assignee_id' => $assignee->id,
                'assignee_name' => self::getUserName($assignee),
            ],
        ]);
    }

    /**
     * Log ticket reassignment
     */
    public static function logReassigned(Ticket $ticket, User $oldAssignee, User $newAssignee, User $actor): void
    {
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'activity_type' => 'reassigned',
            'description' => self::getUserName($actor) . " reassigned from " . self::getUserName($oldAssignee) . " to " . self::getUserName($newAssignee),
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
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'started',
            'description' => self::getUserName($user) . " started working on this ticket",
        ]);
    }

    /**
     * Log resolution
     */
    public static function logResolved(Ticket $ticket, User $user, ?string $notes = null): void
    {
        $description = self::getUserName($user) . " marked this ticket as resolved";
        if ($notes) {
            $description .= " with notes: \"{$notes}\"";
        }

        self::createAndLog([
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
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'verified',
            'description' => self::getUserName($user) . " verified and closed this ticket",
        ]);
    }

    /**
     * Log reopening
     */
    public static function logReopened(Ticket $ticket, User $user, string $reason): void
    {
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'reopened',
            'description' => self::getUserName($user) . " rejected the solution and reopened: \"{$reason}\"",
            'metadata' => ['reason' => $reason],
        ]);
    }

    /**
     * Log comment addition
     */
    public static function logComment(Ticket $ticket, Comment $comment, User $user): void
    {
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'comment_added',
            'description' => self::getUserName($user) . " added a comment",
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
        
        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'attachment_uploaded',
            'description' => self::getUserName($user) . " uploaded {$attachment->file_name} ({$fileSizeMB} MB)",
            'metadata' => [
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_size' => $attachment->file_size,
            ],
        ]);
    }
}
