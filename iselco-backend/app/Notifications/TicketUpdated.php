<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;

class TicketUpdated extends Notification implements ShouldBroadcastNow
{
    // Removed Queueable trait to ensure immediate broadcast without queue interference

    public $ticket;
    public $actor; // The user who performed the action
    public $actionType;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket, User $actor, string $actionType, string $message)
    {
        $this->ticket = $ticket;
        $this->actor = $actor;
        $this->actionType = $actionType;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     * Saved to the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_title' => $this->ticket->title,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->employee_name ?: $this->actor->username,
            'action_type' => $this->actionType,
            'message' => $this->message,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'data' => $this->toArray($notifiable),
            'created_at' => now(),
            'read_at' => null,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     * This is REQUIRED for notifications to actually broadcast
     */
    public function broadcastOn(): array
    {
        // Return empty array - Laravel will use the notifiable's receivesBroadcastNotificationsOn() method
        // or default to private-App.Models.User.{id}
        return [];
    }
}
