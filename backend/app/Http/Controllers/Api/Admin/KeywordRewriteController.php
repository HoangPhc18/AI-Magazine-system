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
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:5000');
            $endpoint = $aiServiceUrl . '/api/keyword_rewrite/process';
            
            $response = Http::post($endpoint, [
                'keyword' => $request->keyword,
                'rewrite_id' => $keywordRewrite->id,
                'callback_url' => env('BACKEND_URL', 'http://localhost:8000') . '/api/admin/keyword-rewrites/callback',
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $keywordRewrite = KeywordRewrite::findOrFail($request->rewrite_id);
        
        $keywordRewrite->update([
            'status' => $request->status,
            'source_url' => $request->source_url,
            'source_title' => $request->source_title,
            'source_content' => $request->source_content,
            'rewritten_content' => $request->rewritten_content,
            'error_message' => $request->error_message,
        ]);

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