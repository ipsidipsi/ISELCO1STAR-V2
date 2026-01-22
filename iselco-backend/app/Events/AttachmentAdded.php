<?php

namespace App\Events;

use App\Models\Attachment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttachmentAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attachment;
    public $ticketId;
    public $commentId;

    /**
     * Create a new event instance.
     */
    public function __construct(Attachment $attachment, $ticketId, $commentId)
    {
        // Load uploader relationship
        $attachment->load('uploader');
        
        $this->attachment = $attachment;
        $this->ticketId = $ticketId;
        $this->commentId = $commentId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.' . $this->ticketId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'attachment.added';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'attachment' => $this->attachment,
            'comment_id' => $this->commentId,
        ];
    }
}
