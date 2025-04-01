<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovedArticleResource;
use App\Models\ApprovedArticle;
use Illuminate\Http\Request;

class ApprovedArticleController extends Controller
{
    public function index()
    {
        $articles = ApprovedArticle::with(['category', 'originalArticle'])->get();
        return response()->json([
            'data' => ApprovedArticleResource::collection($articles)
        ]);
    }

    public function show(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->load(['category', 'originalArticle']);
        return new ApprovedArticleResource($approvedArticle);
    }

    public function update(Request $request, ApprovedArticle $approvedArticle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id'
        ]);

        $approvedArticle->update($validated);
        return new ApprovedArticleResource($approvedArticle->load(['category', 'originalArticle']));
    }

    public function archive(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->update(['status' => 'archived']);
        return new ApprovedArticleResource($approvedArticle);
    }

    public function destroy(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->delete();
        return response()->json(['message' => 'Article deleted successfully']);
    }
} 