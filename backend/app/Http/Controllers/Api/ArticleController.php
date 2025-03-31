<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use App\Models\Category;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = ApprovedArticle::with('category')
            ->where('status', 'approved')
            ->latest()
            ->get();
        return response()->json($articles);
    }

    public function show(ApprovedArticle $article)
    {
        if ($article->status !== 'approved') {
            return response()->json(['message' => 'Article not found'], 404);
        }
        return response()->json($article->load('category'));
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $articles = ApprovedArticle::with('category')
            ->where('status', 'approved')
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->latest()
            ->get();
        return response()->json($articles);
    }
}
