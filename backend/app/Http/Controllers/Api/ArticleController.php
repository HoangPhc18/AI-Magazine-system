<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(): ArticleCollection
    {
        $articles = Article::with(['category', 'rewrittenArticle'])
            ->latest()
            ->paginate(10);

        return new ArticleCollection($articles);
    }

    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article->load(['category', 'rewrittenArticle']));
    }

    public function search(Request $request): ArticleCollection
    {
        $keyword = $request->get('q');
        
        $articles = Article::with(['category', 'rewrittenArticle'])
            ->where(function($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");
            })
            ->latest()
            ->paginate(10);

        return new ArticleCollection($articles);
    }
}
