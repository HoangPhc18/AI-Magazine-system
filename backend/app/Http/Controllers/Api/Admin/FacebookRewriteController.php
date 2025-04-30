<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FacebookPost;
use App\Models\RewrittenArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class FacebookRewriteController extends Controller
{
    /**
     * Get a random unprocessed Facebook post
     */
    public function getRandomPost()
    {
        $post = FacebookPost::where('processed', 0)
            ->inRandomOrder()
            ->first();

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'No unprocessed Facebook posts available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'post' => $post
        ]);
    }

    /**
     * Rewrite a Facebook post using the rewrite service
     */
    public function rewritePost(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:facebook_posts,id'
        ]);

        $postId = $request->post_id;
        $post = FacebookPost::findOrFail($postId);

        try {
            // Call the Facebook rewrite service
            $rewriteServiceUrl = config('services.facebook_rewrite.url', 'http://localhost:55025/facebook-rewrite');
            $endpoint = $rewriteServiceUrl . '/api/rewrite';

            $response = Http::timeout(120)
                ->post($endpoint, [
                    'text' => $post->content,
                    'post_id' => $post->id
                ]);

            if (!$response->successful()) {
                Log::error('Facebook rewrite service error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'post_id' => $postId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error from rewrite service: ' . $response->status()
                ], 500);
            }

            $result = $response->json();

            // Check if rewrite was successful
            if (!isset($result['rewritten']) || !isset($result['saved_to_db'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response from rewrite service'
                ], 500);
            }

            // Return success response with the rewritten content
            return response()->json([
                'success' => true,
                'message' => 'Facebook post rewritten successfully',
                'rewritten' => $result['rewritten'],
                'saved_to_db' => $result['saved_to_db'],
                'post' => $post
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook rewrite error', [
                'message' => $e->getMessage(),
                'post_id' => $postId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a rewritten article manually from a Facebook post
     */
    public function createRewrittenArticle(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:facebook_posts,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id'
        ]);

        try {
            $post = FacebookPost::findOrFail($request->post_id);
            
            // Create the rewritten article
            $slug = Str::slug($request->title) . '-' . Str::random(6);
            
            $rewrittenArticle = RewrittenArticle::create([
                'title' => $request->title,
                'slug' => $slug,
                'content' => $request->content,
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'original_article_id' => $post->id,
                'ai_generated' => true,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Mark the Facebook post as processed
            $post->update([
                'processed' => 1,
                'updated_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Rewritten article created successfully',
                'article' => $rewrittenArticle
            ]);
            
        } catch (\Exception $e) {
            Log::error('Create rewritten article error', [
                'message' => $e->getMessage(),
                'post_id' => $request->post_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a batch of Facebook posts
     */
    public function processBatch(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        $limit = $request->input('limit', 5);

        try {
            // Call the batch processing endpoint
            $rewriteServiceUrl = config('services.facebook_rewrite.url', 'http://localhost:55025/facebook-rewrite');
            $endpoint = $rewriteServiceUrl . '/api/process-batch';

            $response = Http::timeout(300)
                ->post($endpoint, [
                    'limit' => $limit
                ]);

            if (!$response->successful()) {
                Log::error('Facebook batch rewrite service error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error from rewrite service: ' . $response->status()
                ], 500);
            }

            $result = $response->json();

            return response()->json([
                'success' => true,
                'message' => 'Batch processing completed',
                'processed_count' => $result['processed_count'] ?? 0,
                'results' => $result['results'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook batch rewrite error', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
} 