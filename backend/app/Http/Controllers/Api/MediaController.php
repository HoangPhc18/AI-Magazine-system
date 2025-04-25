<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Select media items (used in editor)
     * Public endpoint that doesn't require authentication
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
        
        // Format media items to include URL and type flags
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