<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Attachment Controller
 * 
 * Handles file uploads and downloads for tickets and comments
 * Supports images, PDFs, and documents up to 25MB
 */
class AttachmentController extends Controller
{
    /**
     * Upload file
     * 
     * POST /api/attachments
     * Body: multipart/form-data with file, attachable_type, attachable_id
     * 
     * Allowed types: images, PDF, documents (doc, docx, xls, xlsx), zip, rar
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:25600', // 25MB max
                'mimes:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar'
            ],
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer',
        ]);

        try {
            $file = $request->file('file');
            
            // Generate unique filename with timestamp
            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            
            // Store file in storage/app/public/attachments
            $path = $file->storeAs('attachments', $filename, 'public');

            // Create attachment record
            $attachment = Attachment::create([
                'attachable_type' => $request->attachable_type,
                'attachable_id' => $request->attachable_id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => $request->user()->id, // Fixed: was uploaded_by_user_id
            ]);

            return response()->json($attachment, 201);

        } catch (\Exception $e) {
            \Log::error('File upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'File upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file
     * 
     * GET /api/attachments/{id}/download
     */
    public function download($id)
    {
        $attachment = Attachment::findOrFail($id);

        // Check if file exists
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Delete attachment
     * 
     * DELETE /api/attachments/{id}
     * 
     * Only uploader or superadmin can delete
     */
    public function destroy(Request $request, $id)
    {
        $attachment = Attachment::findOrFail($id);

        // Authorization: only uploader or superadmin can delete
        if ($attachment->uploaded_by !== $request->user()->id && !$request->user()->isSuperadmin()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only delete your own attachments'
            ], 403);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Delete database record
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }
}
