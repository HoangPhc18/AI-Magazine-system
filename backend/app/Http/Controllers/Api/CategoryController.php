<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ApprovedArticle;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function articles(Category $category)
    {
        $articles = ApprovedArticle::where('category_id', $category->id)
            ->where('status', 'approved')
            ->latest()
            ->get();
        return response()->json($articles);
    }
    
    /**
     * Get subcategories for a given category
     */
    public function subcategories(Category $category)
    {
        $subcategories = $category->subcategories()->orderBy('name')->get();
        return response()->json($subcategories);
    }
}
