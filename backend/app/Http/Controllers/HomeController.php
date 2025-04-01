<?php

namespace App\Http\Controllers;

use App\Models\ApprovedArticle;
use App\Models\Category;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $articles = ApprovedArticle::with('category')
            ->where('status', 'approved')
            ->latest()
            ->get();
        
        return view('home', compact('articles'));
    }

    public function showArticle($id)
    {
        $article = ApprovedArticle::with('category')
            ->where('id', $id)
            ->where('status', 'approved')
            ->first();
        
        if (!$article) {
            abort(404);
        }
        
        return view('articles.show', compact('article'));
    }

    public function categoryArticles($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $articles = ApprovedArticle::with('category')
            ->where('category_id', $categoryId)
            ->where('status', 'approved')
            ->latest()
            ->get();
        
        return view('articles.category', compact('articles', 'category'));
    }
} 