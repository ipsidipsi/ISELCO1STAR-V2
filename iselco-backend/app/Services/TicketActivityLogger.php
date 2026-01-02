<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Models\Comment;
use App\Models\Attachment;
use App\Notifications\TicketUpdated;
use Illuminate\Support\Facades\Notification;

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
     * Helper: Notify relevant users (Requestor <-> Assignee)
     */
    private static function notifyUsers(Ticket $ticket, User $actor, string $actionType, string $message): void
    {
        $recipients = collect();

        // 1. Notify Requestor (if actor is NOT requestor)
        // if ($ticket->requestor_id !== $actor->id) {
            $recipients->push($ticket->requestor);
        // }

        // 2. Notify Assignee (if exists and actor is NOT assignee)
        if ($ticket->assigned_to_id) { // && $ticket->assigned_to_id !== $actor->id) {
            $recipients->push($ticket->assignedTo);
        }

        // 3. (Optional) If unassigned and created/updated, maybe notify Department Admins? 
        // For now, keep it simple: direct stakeholders only.
        
        \Log::info('TicketActivityLogger: Preparing to notify users', [
            'ticket_id' => $ticket->id,
            'actor_id' => $actor->id,
            'recipients_count' => $recipients->count(),
            'recipient_ids' => $recipients->pluck('id')->toArray(),
            'action' => $actionType
        ]);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketUpdated($ticket, $actor, $actionType, $message));
        }
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
        
        // Check if ticket is assigned or unassigned
        if (!$ticket->assigned_to_id) {
            // Unassigned ticket: Notify entire department (admins + tech users)
            self::notifyDepartment($ticket, $user, 'ticket_created', "New unassigned ticket created by " . self::getUserName($user));
        } else {
            // Pre-assigned ticket: Notify assignee and department managers
            self::notifyUsers($ticket, $user, 'ticket_created', "New ticket created and assigned to you");
        }
    }

    /**
     * Helper: Notify Admins (Superadmins & Department Admins)
     */
    private static function notifyAdmins(Ticket $ticket, User $actor, string $actionType, string $message): void
    {
        $recipients = collect();

        // 1. Superadmins & Global Admins
        $superadmins = User::whereHas('roles', function($q) {
            $q->whereIn('slug', ['superadmin', 'admin']);
        })->where('id', '!=', $actor->id)->get();
        
        $recipients = $recipients->merge($superadmins);

        // 2. Department Admins (if ticket has department)
        if ($ticket->department_id) {
            $deptAdmins = User::whereHas('roles', function($q) {
                $q->where('slug', 'department_admin');
            })
            ->whereHas('activeDepartments', function($q) use ($ticket) {
                $q->where('departments.id', $ticket->department_id);
            })
            ->where('id', '!=', $actor->id)
            ->get();
            
            $recipients = $recipients->merge($deptAdmins);
        }

        // Filter duplicates
        $recipients = $recipients->unique('id');
        
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketUpdated($ticket, $actor, $actionType, $message));
            
            // Manual broadcast workaround - since Laravel notification broadcasting is broken,
            // we broadcast directly like the chat system does (which works)
            foreach ($recipients as $recipient) {
                $latestNotification = $recipient->notifications()->latest()->first();
                if ($latestNotification) {
                    broadcast(new \App\Events\NotificationCreated([
                        'id' => $latestNotification->id,
                        'type' => $latestNotification->type,
                        'data' => $latestNotification->data,
                        'read_at' => $latestNotification->read_at,
                        'created_at' => $latestNotification->created_at->toISOString(),
                    ], $recipient->id));
                }
            }
        }
    }

    /**
     * Helper: Notify Department (All dept admins + department users)
     * Used for unassigned tickets to notify everyone in the department
     */
    private static function notifyDepartment(Ticket $ticket, User $actor, string $actionType, string $message): void
    {
        if (!$ticket->department_id) {
            return; // No department, can't notify
        }

        $recipients = collect();
        
        // 1. Superadmins & Global Admins (always included)
        $superadmins = User::whereHas('roles', function($q) {
            $q->whereIn('slug', ['superadmin', 'admin']);
        })->where('id', '!=', $actor->id)->get();
        
        $recipients = $recipients->merge($superadmins);

        // 2. Department Admins for this specific department
        $deptAdmins = User::whereHas('roles', function($q) {
            $q->where('slug', 'department_admin');
        })
        ->whereHas('activeDepartments', function($q) use ($ticket) {
            $q->where('departments.id', $ticket->department_id);
        })
        ->where('id', '!=', $actor->id)
        ->get();
        
        $recipients = $recipients->merge($deptAdmins);

        // 3. All users in the department (primary department or active departments)
        $deptUsers = User::where(function($q) use ($ticket) {
            $q->where('department_id', $ticket->department_id)
              ->orWhereHas('activeDepartments', function($subQ) use ($ticket) {
                  $subQ->where('departments.id', $ticket->department_id);
              });
        })
        ->where('is_active', true)
        ->where('id', '!=', $actor->id)
        ->get();
        
        $recipients = $recipients->merge($deptUsers);

        // Filter duplicates
        $recipients = $recipients->unique('id');
        
        \Log::info('[TicketActivityLogger] Notifying department for unassigned ticket', [
            'ticket_id' => $ticket->id,
            'department_id' => $ticket->department_id,
            'actor_id' => $actor->id,
            'recipients_count' => $recipients->count(),
            'recipient_ids' => $recipients->pluck('id')->toArray(),
        ]);
        
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketUpdated($ticket, $actor, $actionType, $message));
            
            // Manual broadcast workaround
            foreach ($recipients as $recipient) {
                $latestNotification = $recipient->notifications()->latest()->first();
                if ($latestNotification) {
                    broadcast(new \App\Events\NotificationCreated([
                        'id' => $latestNotification->id,
                        'type' => $latestNotification->type,
                        'data' => $latestNotification->data,
                        'read_at' => $latestNotification->read_at,
                        'created_at' => $latestNotification->created_at->toISOString(),
                    ], $recipient->id));
                }
            }
        }
    }

    /**
     * Log status change
     */
    public static function logStatusChange(Ticket $ticket, string $oldStatus, string $newStatus, User $user): void
    {
        $oldStatusFormatted = str_replace('_', ' ', ucfirst($oldStatus));
        $newStatusFormatted = str_replace('_', ' ', ucfirst($newStatus));
        $desc = self::getUserName($user) . " changed status from {$oldStatusFormatted} to {$newStatusFormatted}";

        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'activity_type' => 'status_changed',
            'description' => $desc,
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
        ]);

        self::notifyUsers($ticket, $user, 'status_changed', "Status changed to {$newStatusFormatted}");
    }

    /**
     * Log ticket assignment
     */
    public static function logAssigned(Ticket $ticket, User $assignee, User $actor): void
    {
        $desc = self::getUserName($actor) . " assigned this ticket to " . self::getUserName($assignee);

        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'activity_type' => 'assigned',
            'description' => $desc,
            'metadata' => [
                'assignee_id' => $assignee->id,
                'assignee_name' => self::getUserName($assignee),
            ],
        ]);

        // Notify the new assignee
        if ($assignee->id !== $actor->id) {
            $assignee->notify(new TicketUpdated($ticket, $actor, 'assigned', "You have been assigned this ticket"));
        }
        
        // Notify requestor
        if ($ticket->requestor_id !== $actor->id) {
            $ticket->requestor->notify(new TicketUpdated($ticket, $actor, 'assigned', "Ticket assigned to " . self::getUserName($assignee)));
        }
    }

    /**
     * Log ticket reassignment
     */
    public static function logReassigned(Ticket $ticket, User $oldAssignee, User $newAssignee, User $actor): void
    {
        $desc = self::getUserName($actor) . " reassigned from " . self::getUserName($oldAssignee) . " to " . self::getUserName($newAssignee);

        self::createAndLog([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'activity_type' => 'reassigned',
            'description' => $desc,
            'metadata' => [
                'old_assignee_id' => $oldAssignee->id,
                'new_assignee_id' => $newAssignee->id,
            ],
        ]);

        // Notify new assignee
        $newAssignee->notify(new TicketUpdated($ticket, $actor, 'reassigned', "Ticket reassigned to you"));
        
        // Notify old assignee? Optional.
        
        // Notify requestor
        if ($ticket->requestor_id !== $actor->id) {
            $ticket->requestor->notify(new TicketUpdated($ticket, $actor, 'reassigned', "Ticket reassigned to " . self::getUserName($newAssignee)));
        }
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

        self::notifyUsers($ticket, $user, 'started', "Started working on ticket");
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

        self::notifyUsers($ticket, $user, 'resolved', "Ticket marked as resolved");
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

        self::notifyUsers($ticket, $user, 'verified', "Ticket verified and closed");
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

        self::notifyUsers($ticket, $user, 'reopened', "Ticket reopened: {$reason}");
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

        self::notifyUsers($ticket, $user, 'comment_added', "Added a comment");
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

        self::notifyUsers($ticket, $user, 'attachment_uploaded', "Uploaded attachment: {$attachment->file_name}");
    }
}

