<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get user notifications (paginated)
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($notifications);
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Mark ALL as read
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All marked as read']);
    }

    /**
     * Smart Read: Mark all notifications for a specific ticket as read
     * Use when user opens the ticket detail page
     */
    public function markTicketAsRead(Request $request, $ticketId)
    {
        // Find unread notifications where data->ticket_id equals $ticketId
        // Note: JSON queries might vary by DB, but standard Laravel filter on collection is safe for small sets,
        // or querying the DB column 'data->ticket_id' if using MySQL 5.7+
        
        // Using DB query for efficiency
        $request->user()->unreadNotifications()
            ->where('type', 'App\Notifications\TicketUpdated')
            ->where('data->ticket_id', $ticketId)
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Ticket notifications cleared']);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }
}
