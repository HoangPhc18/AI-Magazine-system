<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Display a listing of the media.
     */
    public function index(Request $request)
    {
        $query = Media::query();

        // Filter by type if specified
        if ($request->has('type') && in_array($request->type, ['image', 'document'])) {
            $query->where('type', $request->type);
        }

        // Search by name if specified
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $media = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'media' => $media->items(),
            'pagination' => [
                'total' => $media->total(),
                'per_page' => $media->perPage(),
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created media item in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            
            // Determine the media type based on mime type
            $mimeType = $file->getMimeType();
            $type = Str::startsWith($mimeType, 'image/') ? 'image' : 'document';
            
            // Define storage path based on type
            $path = $type . 's/' . date('Y/m');
            
            // Store the file
            $filePath = $file->storeAs($path, $fileName, 'public');
            
            // Create the media record
            $media = Media::create([
                'name' => $request->name ?? $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
                'type' => $type,
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'media' => $media
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified media item.
     */
    public function show(Media $media)
    {
        return response()->json([
            'media' => $media
        ]);
    }

    /**
     * Remove the specified media item from storage.
     */
    public function destroy(Media $media)
    {
        try {
            // Delete the file from storage
            Storage::disk('public')->delete($media->file_path);
            
            // Delete the database record
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Select media items (used in editor)
     */
    public function select(Request $request)
    {
        $query = Media::query();

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['image', 'document'])) {
            $query->where('type', $request->type);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('file_name', 'like', '%' . $request->search . '%');
            });
        }

        $media = $query->latest()->paginate($request->input('per_page', 40));

        return response()->json([
            'media' => $media->items(),
            'pagination' => [
                'total' => $media->total(),
                'per_page' => $media->perPage(),
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
            ]
        ]);
    }
} 