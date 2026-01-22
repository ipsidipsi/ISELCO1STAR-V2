<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for ticket comments
Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) {
    // Load user roles to avoid "Call to undefined method" error
    $user->load('roles');
    
    // User can access ticket channel if they:
    // 1. Created the ticket (requestor)
    // 2. Are assigned to the ticket
    // 3. Are admin/superadmin
    // 4. Have access to the ticket's department
    
    $ticket = \App\Models\Ticket::find($ticketId);
    
    if (!$ticket) {
        return false;
    }
    
    // Admin/Superadmin can access all tickets
    if ($user->isSuperadmin() || $user->hasRole('admin')) {
        return true;
    }
    
    // Requestor can access their own tickets
    if ($ticket->requestor_id === $user->id) {
        return true;
    }
    
    // Assigned user can access the ticket
    if ($ticket->assigned_to_id === $user->id) {
        return true;
    }
    
    // User from same department can access (for department-based access)
    if ($ticket->department_id === $user->department_id) {
        return true;
    }
    
    return false;
});
