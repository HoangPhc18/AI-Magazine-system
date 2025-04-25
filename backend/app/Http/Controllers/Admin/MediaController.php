<?php

namespace App\Http\Controllers\Admin;

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

        $media = $query->latest()->paginate(20);

        return view('admin.media.index', compact('media'));
    }

    /**
     * Show the form for creating a new media item.
     */
    public function create()
    {
        return view('admin.media.create');
    }

    /**
     * Store a newly created media item in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max
            'name' => 'nullable|string|max:255',
        ]);

        $successCount = 0;
        $errors = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                try {
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
                    Media::create([
                        'name' => $request->name ?? $originalName,
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'mime_type' => $mimeType,
                        'size' => $file->getSize(),
                        'type' => $type,
                        'user_id' => Auth::id(),
                    ]);
                    
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error uploading {$originalName}: {$e->getMessage()}";
                }
            }
        }

        if ($successCount > 0) {
            return redirect()->route('admin.media.index')
                ->with('success', "{$successCount} file(s) uploaded successfully" . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""));
        }

        return back()->withErrors($errors);
    }

    /**
     * Display the specified media item.
     */
    public function show(Media $media)
    {
        return view('admin.media.show', compact('media'));
    }

    /**
     * Remove the specified media item from storage.
     */
    public function destroy(Media $media)
    {
        // Delete the file from storage
        Storage::disk('public')->delete($media->file_path);
        
        // Delete the database record
        $media->delete();

        return redirect()->route('admin.media.index')
            ->with('success', 'File deleted successfully');
    }
    
    /**
     * API endpoint for selecting media (used in editor)
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

        $media = $query->latest()->paginate(40);
        
        // Format media items to include URL
        $mediaItems = collect($media->items())->map(function($item) {
            // Add URL to each item
            $item->url = $item->getUrlAttribute();
            // Add is_image flag to each item
            $item->is_image = $item->getIsImageAttribute();
            // Add is_document flag to each item
            $item->is_document = $item->getIsDocumentAttribute();
            return $item;
        });

        return response()->json([
            'media' => $mediaItems,
            'pagination' => [
                'total' => $media->total(),
                'per_page' => $media->perPage(),
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
            ]
        ]);
    }
}
