<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use Illuminate\Http\Request;

class ApprovedArticleController extends Controller
{
    public function index()
    {
        $articles = ApprovedArticle::with('category')->get();
        return response()->json($articles);
    }

    public function show(ApprovedArticle $approvedArticle)
    {
        return response()->json($approvedArticle->load('category'));
    }

    public function update(Request $request, ApprovedArticle $approvedArticle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id'
        ]);

        $approvedArticle->update($validated);
        return response()->json($approvedArticle->load('category'));
    }

    public function archive(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->update(['status' => 'archived']);
        return response()->json(['message' => 'Article archived successfully']);
    }

    public function destroy(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->delete();
        return response()->json(['message' => 'Article deleted successfully']);
    }
} 