<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Priority;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Priority Management Controller
 * 
 * Handles CRUD operations for ticket priority levels
 * Admin-only access (requires superadmin or department_admin role)
 */
class PriorityController extends Controller
{
    /**
     * List all priorities
     * 
     * GET /api/admin/priorities
     */
    public function index()
    {
        $priorities = Priority::orderBy('level', 'desc')->get();

        // Add ticket count for each priority
        $priorities->each(function ($priority) {
            $priority->ticket_count = Ticket::where('priority_id', $priority->id)->count();
        });

        return response()->json($priorities);
    }

    /**
     * Create new priority
     * 
     * POST /api/admin/priorities
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:priorities,name',
            'level' => 'required|integer|min:1|max:5',
            'color' => 'required|string|max:20',
            'sla_hours' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $priority = Priority::create([
            'name' => $request->name,
            'level' => $request->level,
            'color' => $request->color,
            'sla_hours' => $request->sla_hours,
        ]);

        return response()->json([
            'message' => 'Priority created successfully',
            'priority' => $priority
        ], 201);
    }

    /**
     * Get single priority
     * 
     * GET /api/admin/priorities/{id}
     */
    public function show($id)
    {
        $priority = Priority::findOrFail($id);
        $priority->ticket_count = Ticket::where('priority_id', $priority->id)->count();

        return response()->json($priority);
    }

    /**
     * Update priority
     * 
     * PUT/PATCH /api/admin/priorities/{id}
     */
    public function update(Request $request, $id)
    {
        $priority = Priority::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('priorities')->ignore($id),
            ],
            'level' => 'required|integer|min:1|max:5',
            'color' => 'required|string|max:20',
            'sla_hours' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $priority->update($request->only([
            'name',
            'level',
            'color',
            'sla_hours',
        ]));

        return response()->json([
            'message' => 'Priority updated successfully',
            'priority' => $priority
        ]);
    }

    /**
     * Delete priority
     * 
     * DELETE /api/admin/priorities/{id}
     * Only allows deletion if not referenced by any tickets
     */
    public function destroy($id)
    {
        $priority = Priority::findOrFail($id);

        // Check if priority is used by any tickets
        $ticketCount = Ticket::where('priority_id', $id)->count();
        
        if ($ticketCount > 0) {
            return response()->json([
                'message' => "Cannot delete priority. It is currently used by {$ticketCount} ticket(s)."
            ], 422);
        }

        $priority->delete();

        return response()->json([
            'message' => 'Priority deleted successfully'
        ]);
    }
}
