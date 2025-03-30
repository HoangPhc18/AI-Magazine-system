<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): CategoryCollection
    {
        return new CategoryCollection(Category::all());
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function articles(Category $category): ArticleCollection
    {
        $articles = $category->articles()
            ->with('rewrittenArticle')
            ->whereHas('rewrittenArticle', function($query) {
                $query->where('status', 'approved');
            })
            ->latest()
            ->paginate(10);

        return new ArticleCollection($articles);
    }
}
