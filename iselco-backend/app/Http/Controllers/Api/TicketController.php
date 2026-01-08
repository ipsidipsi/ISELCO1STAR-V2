<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Services\TicketActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ticket Controller
 * 
 * Manages ticket CRUD operations and lifecycle transitions
 * Implements complete workflow: NEW → SEEN → ASSIGNED → IN_PROGRESS → RESOLVED → CLOSED
 */
class TicketController extends Controller
{
    /**
     * List tickets with filtering
     * 
     * GET /api/tickets?status=new&department_id=1&assigned_to=5
     * 
     * Automatic role-based filtering:
     * - Normal users: Only tickets they created OR are assigned to
     * - Department admins: Only tickets in their assigned departments
     * - Superadmin: All tickets
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['priority', 'category', 'department', 'requestor', 'assignedTo']);

        // Apply role-based filtering
        if (!$user->isSuperadmin()) {
            $query->where(function($q) use ($user) {
                // ALWAYS show tickets I created or am assigned to
                $q->where('requestor_id', $user->id)
                  ->orWhere('assigned_to_id', $user->id);

                // If Department Admin, ALSO show tickets in my departments
                if ($user->isDepartmentAdmin()) {
                    $accessibleDepartmentIds = $user->getAccessibleDepartmentIds();
                    $q->orWhereIn('department_id', $accessibleDepartmentIds);
                }
                
                // If Normal User (not Dept Admin), ALSO show unassigned tickets (to allow picking them up if capable)
                // BUT EXCLUDE "Forgot Password" tickets for normal users (security)
                if (!$user->isDepartmentAdmin()) {
                     // Get Forgot Password category ID
                     $forgotPwdCategory = \App\Models\Category::where('name', 'Forgot Password')->first();
                     $forgotPwdId = $forgotPwdCategory ? $forgotPwdCategory->id : 1;

                     $q->orWhere(function($subQ) use ($forgotPwdId) {
                        $subQ->whereNull('assigned_to_id')
                             ->where('category_id', '!=', $forgotPwdId);
                     });
                } else {
                    // For Department Admins:
                    // We already added "tickets in my departments" via $accessibleDepartmentIds logic above.
                    // However, we explicitly want to make sure Forgot Password tickets are included if they fall in their dept.
                    // The existing logic covers it: $q->orWhereIn('department_id', $accessibleDepartmentIds);
                    // But to be safe and explicit, we ensure that unlike normal users, we DO NOT filter them out.
                }
            });
        }

        // Filter by user's own requests
        if ($request->has('requested_by_me') && $request->requested_by_me == 'true') {
            $query->where('requestor_id', $user->id);
        }

        // Filter by status (supports comma-separated values)
        if ($request->has('status')) {
            $statuses = explode(',', $request->status);
            if (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by department (only if superadmin or dept admin accessing their own departments)
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by assigned user
        if ($request->has('assigned_to')) {
            $query->where('assigned_to_id', $request->assigned_to);
        }

        // Filter by requestor
        if ($request->has('created_by')) {
            $query->where('requestor_id', $request->created_by);
        }

        // Filter: Assigned to me
        if ($request->boolean('assigned_to_me')) {
            $query->where('assigned_to_id', $request->user()->id);
        }

        // Filter: Requested by me
        if ($request->boolean('requested_by_me')) {
            $query->where('requestor_id', $request->user()->id);
        }

        // Filter: Exclude status
        if ($request->has('exclude_status')) {
            $query->where('status', '!=', $request->exclude_status);
        }

        // Search by ticket number or title
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ticket_number', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%");
            });
        }

        // Sort
        $query->orderBy('created_at', 'desc');

        // Paginate or get all
        if ($request->has('all') && $request->input('all') === 'true') {
            $tickets = $query->get();
        } else {
            $tickets = $query->paginate($request->input('per_page', 20));
        }

        return response()->json($tickets);
    }

    /**
     * Get single ticket with details
     * 
     * GET /api/tickets/{id}
     */
    public function show($id)
    {
        $ticket = Ticket::with([
            'priority',
            'category',
            'department',
            'requestor',
            'assignedTo',
            'timeline.user',
            'comments.user',
            'attachments'
        ])->findOrFail($id);

        return response()->json($ticket);
    }

    /**
     * Create new ticket
     * 
     * POST /api/tickets
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'category_id' => 'required|exists:categories,id',
            'priority_id' => 'required|exists:priorities,id',
            'assigned_to_id' => 'nullable|exists:users,id',
        ]);

        // Prevent users from assigning tickets to themselves
        if ($request->assigned_to_id && $request->assigned_to_id == $request->user()->id) {
            return response()->json([
                'error' => 'You cannot assign a ticket to yourself',
                'message' => 'Tickets must be assigned to someone other than the requestor.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Generate ticket number: TKT-YYYYMMDD-XXXX
            $today = today();
            $count = Ticket::whereDate('created_at', $today)->count() + 1;
            $ticketNumber = 'TKT-' . $today->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            // Create ticket
            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'new',
                'priority_id' => $request->priority_id,
                'category_id' => $request->category_id,
                'department_id' => $request->department_id,
                'requestor_id' => $request->user()->id,
                'assigned_to_id' => $request->assigned_to_id,
            ]);

            // Create timeline entry
            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => null,
                'status_to' => 'new',
                'user_id' => $request->user()->id,
                'notes' => 'Ticket created',
            ]);

            // Log activity: Ticket created
            TicketActivityLogger::logCreated($ticket, $request->user());

            // If assigned, update status and timeline
            if ($request->assigned_to_id) {
                $assignedUser = \App\Models\User::find($request->assigned_to_id);
                
                $ticket->update([
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ]);

                TicketTimeline::create([
                    'ticket_id' => $ticket->id,
                    'status_from' => 'new',
                    'status_to' => 'assigned',
                    'user_id' => $request->user()->id,
                    'notes' => 'Assigned to user',
                ]);

                // Log activity: Status changed and assigned
                TicketActivityLogger::logStatusChange($ticket, 'new', 'assigned', $request->user());
                TicketActivityLogger::logAssigned($ticket, $assignedUser, $request->user());
            }

            DB::commit();

            // Load relationships for response
            $ticket->load(['priority', 'category', 'department', 'requestor', 'assignedTo']);

            return response()->json($ticket, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ticket creation failed: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to create ticket',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update ticket details
     * 
     * PATCH /api/tickets/{id}
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority_id' => 'sometimes|exists:priorities,id',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        $ticket->update($request->only(['title', 'description', 'priority_id', 'category_id']));

        return response()->json($ticket);
    }

    /**
     * Accept ticket (mark as seen/assigned)
     * 
     * POST /api/tickets/{id}/accept
     */
    public function accept(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = $request->user();

        // Prevent ticket owner from accepting their own ticket
        if ($ticket->requestor_id === $user->id) {
            return response()->json([
                'error' => 'You cannot accept your own ticket',
                'message' => 'Tickets must be assigned to someone other than the requestor.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $ticket->status;

            // Update ticket
            $ticket->update([
                'status' => 'assigned',
                'assigned_to_id' => $user->id,
                'assigned_at' => now(),
            ]);

            // Create timeline entry
            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => $oldStatus,
                'status_to' => 'assigned',
                'user_id' => $user->id,
                'notes' => 'Accepted ticket',
                'created_at' => now(),
            ]);

            // Log activity
            TicketActivityLogger::logStatusChange($ticket, $oldStatus, 'assigned', $user);
            TicketActivityLogger::logAssigned($ticket, $user, $user);

            DB::commit();

            return response()->json(['message' => 'Ticket accepted', 'ticket' => $ticket]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to accept ticket'], 500);
        }
    }

    /**
     * Start working on ticket
     * 
     * POST /api/tickets/{id}/start
     */
    public function start(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        DB::beginTransaction();
        try {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => $oldStatus,
                'status_to' => 'in_progress',
                'user_id' => $request->user()->id,
                'notes' => 'Started working on ticket',
                'created_at' => now(),
            ]);

            // Log activity
            TicketActivityLogger::logStarted($ticket, $request->user());
            TicketActivityLogger::logStatusChange($ticket, $oldStatus, 'in_progress', $request->user());

            DB::commit();

            return response()->json(['message' => 'Started working', 'ticket' => $ticket]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Start work failed: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to start ticket',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark ticket as resolved
     * 
     * POST /api/tickets/{id}/resolve
     * Body: { notes, photo? }
     */
    public function resolve(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'notes' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => $oldStatus,
                'status_to' => 'resolved',
                'user_id' => $request->user()->id,
                'notes' => $request->notes,
                'created_at' => now(),
            ]);

            // Log activity
            TicketActivityLogger::logResolved($ticket, $request->user(), $request->notes);
            TicketActivityLogger::logStatusChange($ticket, $oldStatus, 'resolved', $request->user());

            DB::commit();

            return response()->json(['message' => 'Ticket marked as resolved', 'ticket' => $ticket]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to resolve ticket'], 500);
        }
    }

    /**
     * Verify and close ticket
     * 
     * POST /api/tickets/{id}/verify
     */
    public function verify(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        DB::beginTransaction();
        try {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => $oldStatus,
                'status_to' => 'closed',
                'user_id' => $request->user()->id,
                'notes' => 'Verified and closed',
                'created_at' => now(),
            ]);

            // Log activity
            TicketActivityLogger::logVerified($ticket, $request->user());
            TicketActivityLogger::logStatusChange($ticket, $oldStatus, 'closed', $request->user());

            DB::commit();

            return response()->json(['message' => 'Ticket verified and closed', 'ticket' => $ticket]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to verify ticket'], 500);
        }
    }

    /**
     * Reject resolution and reopen
     * 
     * POST /api/tickets/{id}/reject
     * Body: { reason }
     */
    public function reject(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'reopened',
                'reopened_at' => now(),
            ]);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => $oldStatus,
                'status_to' => 'reopened',
                'user_id' => $request->user()->id,
                'notes' => 'Rejected: ' . $request->reason,
                'created_at' => now(),
            ]);

            // Log activity
            TicketActivityLogger::logReopened($ticket, $request->user(), $request->reason);
            TicketActivityLogger::logStatusChange($ticket, $oldStatus, 'reopened', $request->user());

            DB::commit();

            return response()->json(['message' => 'Ticket reopened', 'ticket' => $ticket]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to reject ticket'], 500);
        }
    }

    /**
     * Reopen closed ticket
     * 
     * POST /api/tickets/{id}/reopen
     * Body: { reason }
     */
    public function reopen(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'reopened',
                'reopened_at' => now(),
            ]);

            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => $oldStatus,
                'status_to' => 'reopened',
                'user_id' => $request->user()->id,
                'notes' => 'Reopened: ' . $request->reason,
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Ticket reopened', 'ticket' => $ticket]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to reopen ticket'], 500);
        }
    }

    /**
     * Get ticket statistics for dashboard
     * 
     * GET /api/tickets/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'pending' => Ticket::whereIn('status', ['new', 'seen', 'reopened'])->count(),
            'assigned_to_me' => Ticket::where('assigned_to_id', $user->id)
                ->where('status', '!=', 'in_progress')
                ->whereNotIn('status', ['closed'])
                ->count(),
            'in_progress' => Ticket::where('assigned_to_id', $user->id)
                ->where('status', 'in_progress')
                ->count(),
            'pending_verification' => Ticket::where('assigned_to_id', $user->id)
                ->where('status', 'resolved')
                ->count(),
            'closed' => Ticket::where('assigned_to_id', $user->id)
                ->where('status', 'closed')
                ->count(),
            'my_requests' => Ticket::where('requestor_id', $user->id)->count(),
        ]);
    }

    /**
     * Get tickets assigned to the current user
     * 
     * GET /api/tickets/assigned-to-me
     * 
     * For "Assigned to Me" dashboard section
     */
    public function assignedToMe(Request $request)
    {
        $user = $request->user();
        
        $tickets = Ticket::with(['priority', 'category', 'department', 'requestor'])
            ->where('assigned_to_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tickets);
    }

    /**
     * Get ticket activities/timeline
     * 
     * GET /api/tickets/{id}/activities
     */
    public function getActivities($id)
    {
        $ticket = Ticket::findOrFail($id);

        $activities = $ticket->activities()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($activities);
    }

    /**
     * Reset user password (from ticket)
     * 
     * POST /api/tickets/{id}/reset-password
     */
    public function resetPassword(Request $request, $id)
    {
        $user = $request->user();
        
        // Only Dept Admin and Superadmin can reset passwords
        if (!$user->isDepartmentAdmin() && !$user->isSuperadmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ticket = Ticket::findOrFail($id);
        $targetUser = $ticket->requestor; // The user who needs password reset

        if (!$targetUser) {
            return response()->json(['error' => 'User not found in this ticket'], 404);
        }

        DB::beginTransaction();
        try {
            // Reset password to 1234
            $targetUser->update([
                'password' => \Illuminate\Support\Facades\Hash::make('1234'),
                'must_change_password' => true, // Force change on next login
            ]);

            // Log activity: Password Reset
            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'status_from' => $ticket->status,
                'status_to' => 'resolved',
                'user_id' => $user->id,
                'notes' => 'Password reset to default (1234) by admin.',
                'created_at' => now(),
            ]);

            // Auto-close the ticket (as per user request)
            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
                'resolved_at' => now(), // Set resolved_at as well for record keeping
            ]);

             // Log activity
            TicketActivityLogger::logResolved($ticket, $user, 'Password reset by admin. Ticket closed.');
            TicketActivityLogger::logStatusChange($ticket, $ticket->status, 'closed', $user);

            DB::commit();

            return response()->json(['message' => 'Password reset successfully to 1234', 'ticket' => $ticket]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to reset password: ' . $e->getMessage()], 500);
        }
    }
}
