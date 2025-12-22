<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Comment Controller
 * 
 * Manages ticket comments for real-time chat functionality
 * Supports read receipts and internal comments
 */
class CommentController extends Controller
{
    /**
     * Get comments for a ticket
     * 
     * GET /api/tickets/{ticketId}/comments
     */
    public function index($ticketId)
    {
        $comments = Comment::where('ticket_id', $ticketId)
            ->with(['user', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json($comments);
    }

    /**
     * Post a comment
     * 
     * POST /api/tickets/{ticketId}/comments
     * Body: { message, is_internal? }
     */
    public function store(Request $request, $ticketId)
    {
        // Verify ticket exists
        $ticket = Ticket::findOrFail($ticketId);

        $request->validate([
            'message' => 'required|string',
            'is_internal' => 'sometimes|boolean',
        ]);

        $comment = Comment::create([
            'ticket_id' => $ticketId,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'is_internal' => $request->is_internal ?? false,
            'read_by' => json_encode([]), // Empty read receipts initially
        ]);

        // Load relationships for response
        $comment->load(['user', 'attachments']);

        return response()->json($comment, 201);
    }

    /**
     * Mark comment as read
     * 
     * PATCH /api/comments/{id}/read
     */
    public function markAsRead(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        
        // Get current read_by array
        $readBy = json_decode($comment->read_by, true) ?? [];
        
        // Add current user if not already in array
        if (!in_array($request->user()->id, $readBy)) {
            $readBy[] = $request->user()->id;
            $comment->update(['read_by' => json_encode($readBy)]);
        }

        return response()->json(['message' => 'Comment marked as read']);
    }

    /**
     * Update comment
     * 
     * PATCH /api/comments/{id}
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        // Only allow comment author to edit
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $comment->update(['message' => $request->message]);

        return response()->json($comment);
    }

    /**
     * Delete comment (soft delete)
     * 
     * DELETE /api/comments/{id}
     */
    public function destroy(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        // Only allow comment author or admin to delete
        if ($comment->user_id !== $request->user()->id && !$request->user()->isSuperadmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
