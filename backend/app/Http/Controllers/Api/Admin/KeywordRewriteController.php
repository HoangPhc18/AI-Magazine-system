<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KeywordRewrite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\KeywordRewriteResource;
use Illuminate\Support\Facades\Validator;

class KeywordRewriteController extends Controller
{
    /**
     * Display a listing of keyword rewrites.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $keywordRewrites = KeywordRewrite::with('creator')
            ->latest()
            ->paginate($request->input('per_page', 10));

        return KeywordRewriteResource::collection($keywordRewrites);
    }

    /**
     * Store a newly created keyword rewrite.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $keywordRewrite = KeywordRewrite::create([
            'keyword' => $request->keyword,
            'created_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // Send the keyword to the AI service for processing
        try {
            $keywordRewriteUrl = env('KEYWORD_REWRITE_URL', 'http://localhost:55025/keyword-rewrite');
            $endpoint = $keywordRewriteUrl . '/api/keyword_rewrite/process';
            $backendUrl = env('BACKEND_URL', 'http://localhost');
            
            // Đảm bảo BACKEND_URL không có dấu / ở cuối
            $backendUrl = rtrim($backendUrl, '/');
            
            $response = Http::post($endpoint, [
                'keyword' => $request->keyword,
                'rewrite_id' => $keywordRewrite->id,
                'callback_url' => $backendUrl . '/api/keyword-rewrites/callback',
            ]);

            if ($response->successful()) {
                $keywordRewrite->update(['status' => 'processing']);
                return response()->json([
                    'message' => 'Keyword rewrite task initiated successfully',
                    'keyword_rewrite' => new KeywordRewriteResource($keywordRewrite)
                ], 201);
            } else {
                $keywordRewrite->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to communicate with AI service'
                ]);
                
                return response()->json([
                    'message' => 'Failed to initiate keyword rewrite task',
                    'keyword_rewrite' => new KeywordRewriteResource($keywordRewrite)
                ], 500);
            }
        } catch (\Exception $e) {
            $keywordRewrite->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Error occurred while processing the request',
                'error' => $e->getMessage(),
                'keyword_rewrite' => new KeywordRewriteResource($keywordRewrite)
            ], 500);
        }
    }

    /**
     * Display the specified keyword rewrite.
     */
    public function show(KeywordRewrite $keywordRewrite): KeywordRewriteResource
    {
        return new KeywordRewriteResource($keywordRewrite->load('creator'));
    }

    /**
     * Update the keyword rewrite data from the AI service callback.
     */
    public function callback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rewrite_id' => 'required|exists:keyword_rewrites,id',
            'status' => 'required|in:completed,failed',
            'source_url' => 'nullable|string',
            'source_title' => 'nullable|string',
            'source_content' => 'nullable|string',
            'rewritten_content' => 'nullable|string',
            'error_message' => 'nullable|string',
            'all_articles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $keywordRewrite = KeywordRewrite::findOrFail($request->rewrite_id);
        
        // Prepare data for update
        $updateData = [
            'status' => $request->status,
            'source_url' => $request->source_url,
            'source_title' => $request->source_title,
            'source_content' => $request->source_content,
            'rewritten_content' => $request->rewritten_content,
            'error_message' => $request->error_message,
        ];
        
        // Add all_articles field if present
        if ($request->has('all_articles')) {
            $updateData['all_articles'] = json_encode($request->all_articles);
        }
        
        $keywordRewrite->update($updateData);

        // Lưu thông báo vào session với rewrite_id
        session()->flash('keyword_rewrite_completed_' . $request->rewrite_id, true);
        session()->flash('keyword_rewrite_status_' . $request->rewrite_id, $request->status);
        
        if ($request->status == 'completed') {
            // Check if we have multiple articles
            $articleCount = 0;
            if ($request->has('all_articles') && is_array($request->all_articles)) {
                $successfulArticles = array_filter($request->all_articles, function($article) {
                    return isset($article['status']) && $article['status'] === 'completed';
                });
                $articleCount = count($successfulArticles);
            }
            
            if ($articleCount > 1) {
                session()->flash('keyword_rewrite_message_' . $request->rewrite_id, 'Đã tạo ' . $articleCount . ' bài viết thành công từ từ khóa "' . $keywordRewrite->keyword . '"');
            } else {
                session()->flash('keyword_rewrite_message_' . $request->rewrite_id, 'Bài viết đã được tạo thành công từ từ khóa "' . $keywordRewrite->keyword . '"');
            }
        } else {
            session()->flash('keyword_rewrite_message_' . $request->rewrite_id, 'Xử lý từ khóa "' . $keywordRewrite->keyword . '" thất bại: ' . $request->error_message);
        }

        return response()->json([
            'message' => 'Keyword rewrite updated successfully',
            'keyword_rewrite' => new KeywordRewriteResource($keywordRewrite)
        ]);
    }

    /**
     * Delete the specified keyword rewrite.
     */
    public function destroy(KeywordRewrite $keywordRewrite): JsonResponse
    {
        $keywordRewrite->delete();

        return response()->json([
            'message' => 'Keyword rewrite deleted successfully'
        ]);
    }
} 