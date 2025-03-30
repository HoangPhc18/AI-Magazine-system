<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Article\ApproveRequest;
use App\Http\Requests\Api\Admin\Article\UpdateRequest;
use App\Http\Resources\ArticleResource;
use App\Models\RewrittenArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $articles = RewrittenArticle::with(['originalArticle.category'])
            ->latest()
            ->paginate(10);

        return ArticleResource::collection($articles);
    }

    public function show(RewrittenArticle $rewrittenArticle): ArticleResource
    {
        return new ArticleResource($rewrittenArticle->load(['originalArticle.category']));
    }

    public function approve(ApproveRequest $request, RewrittenArticle $rewrittenArticle): JsonResponse
    {
        $rewrittenArticle->update([
            'status' => $request->status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Article status updated successfully',
            'article' => new ArticleResource($rewrittenArticle->load(['originalArticle.category']))
        ]);
    }

    public function update(UpdateRequest $request, RewrittenArticle $rewrittenArticle): JsonResponse
    {
        $rewrittenArticle->update([
            'rewritten_content' => $request->rewritten_content,
        ]);

        return response()->json([
            'message' => 'Article updated successfully',
            'article' => new ArticleResource($rewrittenArticle->load(['originalArticle.category']))
        ]);
    }

    public function destroy(RewrittenArticle $rewrittenArticle): JsonResponse
    {
        $rewrittenArticle->delete();

        return response()->json([
            'message' => 'Article deleted successfully'
        ]);
    }
} 