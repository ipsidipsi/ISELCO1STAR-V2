<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Department;

class TrackItWebhookController extends Controller
{
    /**
     * Get ETSD Users for Sync
     * Returns list of users in ETSD department
     */
    public function getEtsdUsers()
    {
        // Find ETSD Department
        $etsd = Department::where('code', 'ETSD')
            ->orWhere('name', 'like', '%Information And Communication Technology%') // Adjust naming if needed
            ->orWhere('name', 'like', '%Technical Services%') 
            ->first();

        if (!$etsd) {
             // Fallback: If no generic department found, return all? Or specific IDs?
             // Let's assume standard Dept ID 1 or find by known name.
             // For now, return error or empty.
             return response()->json(['data' => []]);
        }
        
        // Get Users in this Department
        // Also include those assigned via 'departments' pivot
        $users = User::where('department_id', $etsd->id)
            ->orWhereHas('departments', function($q) use ($etsd) {
                $q->where('departments.id', $etsd->id);
            })
            ->get(['id', 'employee_name', 'username', 'mobile_number', 'is_active', 'password']); // Include password hash for sync comparison (optional) or just identity
            
        return response()->json(['data' => $users, 'department' => $etsd]);
    }

    /**
     * Verify Credentials (Pass-through Auth)
     */
    public function verifyCredentials(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $validated['username'])->first();

        if (!$user) {
            return response()->json(['valid' => false, 'message' => 'User not found'], 404);
        }

        if (!\Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
            return response()->json(['valid' => false, 'message' => 'Invalid password'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['valid' => false, 'message' => 'User inactive'], 403);
        }

        return response()->json(['valid' => true, 'user_id' => $user->id]);
    }

    /**
     * Handle status updates from TrackIt
     */
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer', // Star ID
            'status' => 'required|string', // TrackIt Status
            'notes' => 'nullable|string',
            'updated_at' => 'nullable|date',
            'assignee_id' => 'nullable|integer', // STAR User ID
        ]);

        $ticket = Ticket::findOrFail($validated['id']);
        $oldStatus = $ticket->status;
        $trackItStatus = $validated['status'];
        $notes = $validated['notes'] ?? 'Updated via TrackIt';

        $newStatus = $oldStatus;
        $action = 'updated';

        // Map Status
        switch ($trackItStatus) {
            case 'IN_PROGRESS':
                $newStatus = 'in_progress';
                $action = 'started working on';
                break;
            case 'COMPLETED':
                $newStatus = 'resolved';
                $action = 'resolved';
                break;
            case 'PENDING_PARTS':
                $newStatus = 'in_progress'; // Stay in progress but log note
                $action = 'is waiting for parts';
                break;
            case 'SALVAGE_RECOMMENDED':
                $newStatus = 'resolved';
                $notes .= " (Salvage Recommended)";
                $action = 'recommended salvage for';
                break;
            case 'CANCELLED':
                $newStatus = 'closed';
                $action = 'cancelled';
                break;
        }

        // Only update if status changed or just a note
        if ($newStatus !== $oldStatus || $request->has('assignee_id')) {
            DB::beginTransaction();
            try {
                $updateData = [
                    'status' => $newStatus,
                    'updated_at' => now(), // Touch timestamp
                ];

                // Sync Assignee
                if ($request->has('assignee_id') && $request->assignee_id) {
                     // Translate TrackIt User ID to STAR User ID is tricky IF we don't send STAR User ID.
                     // The request should send the STAR User ID (which TrackIt knows as star_user_id)
                     // NOT the TrackIt User ID.
                     // Let's assume TrackIt sends 'star_user_id' as 'assignee_id'.
                     $updateData['assigned_to_id'] = $request->assignee_id;
                     $updateData['assigned_at'] = now();
                }

                $ticket->update($updateData);

                if ($newStatus === 'in_progress' && !$ticket->started_at) {
                    $ticket->update(['started_at' => now()]);
                }
                if ($newStatus === 'resolved' && !$ticket->resolved_at) {
                    $ticket->update(['resolved_at' => now()]);
                }
                if ($newStatus === 'closed' && !$ticket->closed_at) {
                    $ticket->update(['closed_at' => now()]);
                }

                // Add Timeline
                // Use assigned_to_id if available, else requestor? No, Admin (1).
                // Or maybe the Tech? TrackIt sends technician_name?
                $actorId = $ticket->assigned_to_id ?? 1; // Default to Admin if unassigned

                TicketTimeline::create([
                    'ticket_id' => $ticket->id,
                    'status_from' => $oldStatus,
                    'status_to' => $newStatus,
                    'user_id' => $actorId,
                    'notes' => "TrackIt Update: $action. $notes",
                    'created_at' => now(),
                ]);

                DB::commit();
                return response()->json(['message' => 'Ticket updated successfully']);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to update ticket'], 500);
            }
        }

        return response()->json(['message' => 'No status change required']);
    }
}
