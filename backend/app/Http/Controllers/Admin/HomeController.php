<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\RewrittenArticle;

class HomeController extends Controller
{
    public function debugImagePaths()
    {
        // Test image paths
        $imagePaths = [
            'articles/m1GOVHnc4lonIaWGeAfX.jpg',
            '/articles/m1GOVHnc4lonIaWGeAfX.jpg',
            'storage/articles/m1GOVHnc4lonIaWGeAfX.jpg',
            '/storage/articles/m1GOVHnc4lonIaWGeAfX.jpg'
        ];
        
        $results = [];
        
        foreach ($imagePaths as $path) {
            $results[] = [
                'original_path' => $path,
                'asset_path' => asset($path),
                'storage_url' => Storage::url($path),
                'file_exists' => file_exists(public_path($path)) ? 'Yes' : 'No',
                'storage_exists' => Storage::disk('public')->exists(str_replace('storage/', '', $path)) ? 'Yes' : 'No'
            ];
        }
        
        // Also check a real article
        $article = RewrittenArticle::where('featured_image', 'like', '%m1GOVHnc4lonIaWGeAfX.jpg%')->first();
        
        if ($article) {
            $results[] = [
                'database_path' => $article->featured_image,
                'featured_image_url' => $article->featured_image_url,
                'storage_exists' => Storage::disk('public')->exists(str_replace('storage/', '', $article->featured_image)) ? 'Yes' : 'No'
            ];
        }
        
        return response()->json($results);
    }
} 