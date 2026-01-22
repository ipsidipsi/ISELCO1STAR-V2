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

    /**
     * Delete all notifications for current user
     */
    public function deleteAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'All notifications deleted']);
    }

    /**
     * Get user notification preferences
     */
    public function getPreferences(Request $request)
    {
        $preferences = $request->user()->notificationPreference ?? new \App\Models\NotificationPreference([
            'user_id' => $request->user()->id,
            'is_muted' => false,
            'web_push_enabled' => true,
            'browser_enabled' => true,
            'sound_enabled' => true
        ]);

        return response()->json($preferences);
    }

    /**
     * Update user notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'is_muted' => 'sometimes|boolean',
            'web_push_enabled' => 'sometimes|boolean',
            'browser_enabled' => 'sometimes|boolean',
            'sound_enabled' => 'sometimes|boolean',
        ]);

        $preferences = $request->user()->notificationPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json($preferences);
    }

    /**
     * Subscribe to Web Push notifications
     */
    public function subscribeWebPush(Request $request)
    {
        $validated = $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys' => 'required|array'
        ]);

        $request->user()->notificationPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['web_push_subscription' => $validated['subscription']]
        );

        return response()->json(['message' => 'Subscribed to Web Push']);
    }
}
