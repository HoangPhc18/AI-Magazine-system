<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RewrittenArticleResource;
use App\Models\RewrittenArticle;
use App\Models\ApprovedArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewrittenArticleController extends Controller
{
    public function index()
    {
        $articles = RewrittenArticle::with(['originalArticle', 'category'])
            ->where('status', '!=', 'approved')
            ->get();
        return RewrittenArticleResource::collection($articles);
    }

    public function show(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->load(['originalArticle', 'category']);
        return new RewrittenArticleResource($rewrittenArticle);
    }

    public function update(Request $request, RewrittenArticle $rewrittenArticle)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'status' => 'sometimes|in:draft,pending,approved,rejected',
        ]);

        $rewrittenArticle->update($validated);
        return new RewrittenArticleResource($rewrittenArticle);
    }

    public function approve(RewrittenArticle $rewrittenArticle)
    {
        DB::transaction(function () use ($rewrittenArticle) {
            // Create approved article
            ApprovedArticle::create([
                'title' => $rewrittenArticle->title,
                'content' => $rewrittenArticle->content,
                'category_id' => $rewrittenArticle->category_id,
                'original_article_id' => $rewrittenArticle->original_article_id,
                'status' => 'published',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);

            // Permanently delete the rewritten article to avoid duplication
            $rewrittenArticle->forceDelete();
        });

        return response()->json(['message' => 'Article approved and published successfully']);
    }

    public function reject(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->update(['status' => 'rejected']);
        return new RewrittenArticleResource($rewrittenArticle);
    }

    public function destroy(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->delete();
        return response()->json(['message' => 'Article deleted successfully']);
    }
} 