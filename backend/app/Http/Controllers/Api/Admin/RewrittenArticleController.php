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
        $articles = RewrittenArticle::with(['originalArticle', 'category'])->get();
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
        $rewrittenArticle->update(['status' => 'approved']);
        return new RewrittenArticleResource($rewrittenArticle);
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