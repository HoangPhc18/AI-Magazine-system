<?php

namespace App\Http\Controllers;

use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Services\WebsiteConfigService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $configService;

    public function __construct(WebsiteConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function index()
    {
        $featuredArticles = ApprovedArticle::with('category')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->take(6)
            ->get();
            
        $recentArticles = ApprovedArticle::with('category')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->take(10)
            ->get();
        
        $categories = Category::withCount(['articles' => function($query) {
                $query->where('status', 'published')
                    ->whereNotNull('published_at');
            }])
            ->having('articles_count', '>', 0)
            ->get();
        
        // Get website configurations
        $generalConfig = $this->configService->getByGroup(WebsiteConfigService::GROUP_GENERAL);
        $socialConfig = $this->configService->getByGroup(WebsiteConfigService::GROUP_SOCIAL);
        
        return view('home', compact(
            'featuredArticles', 
            'recentArticles', 
            'categories', 
            'generalConfig', 
            'socialConfig'
        ));
    }

    public function showArticle($slug)
    {
        $article = ApprovedArticle::with(['category', 'user'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->firstOrFail();
        
        // Lấy bài viết liên quan
        $relatedArticles = ApprovedArticle::with('category')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->latest('published_at')
            ->take(4)
            ->get();
        
        return view('articles.show', compact('article', 'relatedArticles'));
    }

    public function categoryArticles($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        
        $articles = ApprovedArticle::with(['category', 'user'])
            ->where('category_id', $category->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->paginate(9);
        
        return view('articles.category', compact('articles', 'category'));
    }
    
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        $articles = ApprovedArticle::with(['category', 'user'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhere('meta_title', 'like', "%{$query}%")
                  ->orWhere('meta_description', 'like', "%{$query}%");
            })
            ->latest('published_at')
            ->paginate(9);
            
        return view('articles.search', compact('articles', 'query'));
    }
    
    public function allArticles()
    {
        $articles = ApprovedArticle::with(['category', 'user'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->paginate(12);
            
        return view('articles.all', compact('articles'));
    }
} 