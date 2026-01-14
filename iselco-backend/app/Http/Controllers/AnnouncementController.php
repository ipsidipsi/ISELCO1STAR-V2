<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    /**
     * Get active announcements for the current user
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $announcements = Announcement::active()
            ->forUser($user)
            ->with(['creator:id,employee_name,username', 'departments:id,name'])
            ->latest()
            ->get()
            ->map(function ($announcement) use ($user) {
                // Check if read
                $isRead = $announcement->readByUsers()
                    ->where('user_id', $user->id)
                    ->exists();

                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'image_url' => $announcement->image_path ? Storage::url($announcement->image_path) : null,
                    'type' => $announcement->type,
                    'created_at' => $announcement->created_at,
                    'creator' => $announcement->creator->employee_name ?? $announcement->creator->username,
                    'departments' => $announcement->departments->pluck('name'),
                    'is_read' => $isRead
                ];
            });

        return response()->json($announcements);
    }

    /**
     * Get history of announcements created by current user
     */
    public function history()
    {
        $announcements = Announcement::where('created_by', auth()->id())
            ->with('departments')
            ->latest()
            ->paginate(10);

        return response()->json($announcements);
    }

    /**
     * Create a new announcement
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:all,department,selected_users',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt|max:10240', // 10MB max, diverse types
            'department_ids' => 'required_if:type,department|array',
            'user_ids' => 'required_if:type,selected_users|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = auth()->user();

        // 1. Validate Permissions
        if ($request->type === 'all') {
            if (!$user->can('broadcast.universal') && !$user->hasRole('superadmin')) {
                return response()->json(['message' => 'You do not have permission to broadcast to all users.'], 403);
            }
        } else {
            if (!$user->can('broadcast.create') && !$user->hasRole('superadmin')) {
                  // Fallback: Check if they have universal permission (implies create)
                 if (!$user->can('broadcast.universal')) {
                    return response()->json(['message' => 'You do not have permission to create broadcasts.'], 403);
                 }
            }
        }

        // 2. Validate Scoped Access (for Department Broadcasts)
        if ($request->type === 'department' && !$user->hasRole('superadmin')) {
            $accessibleDeptIds = $user->getAccessibleDepartmentIds();
            $targetDeptIds = $request->department_ids;

            // Check if ALL requested departments are accessible
            $diff = array_diff($targetDeptIds, $accessibleDeptIds);
            if (!empty($diff)) {
                return response()->json(['message' => 'You can only broadcast to departments you have access to.'], 403);
            }
        }

        try {
            DB::beginTransaction();

            // Handle Image Upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('announcements', 'public');
            }

            $announcement = Announcement::create([
                'title' => $request->title,
                'content' => $request->content,
                'image_path' => $imagePath,
                'type' => $request->type,
                'created_by' => $user->id,
                'expires_at' => $request->expires_at
            ]);

            // Sync Relationships
            if ($request->type === 'department') {
                $announcement->departments()->sync($request->department_ids);
            } elseif ($request->type === 'selected_users') {
                $announcement->targetUsers()->sync($request->user_ids);
            }

            DB::commit();

            return response()->json([
                'message' => 'Announcement broadcasted successfully',
                'data' => $announcement
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create announcement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark announcement as read
     */
    public function markAsRead($id)
    {
        $announcement = Announcement::findOrFail($id);
        
        // Use syncWithoutDetaching to avoid duplicates
        $announcement->readByUsers()->syncWithoutDetaching([
            auth()->id() => ['read_at' => now()]
        ]);

        return response()->json(['message' => 'Marked as read']);
    }
}
