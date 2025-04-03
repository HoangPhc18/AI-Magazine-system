<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = ApprovedArticle::with('category')
            ->where('status', 'approved')
            ->latest()
            ->get();
        return response()->json([
            'data' => $articles
        ]);
    }

    public function show(ApprovedArticle $article)
    {
        if ($article->status !== 'approved') {
            return response()->json(['message' => 'Article not found'], 404);
        }
        return response()->json([
            'data' => $article->load('category')
        ]);
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
        return response()->json([
            'data' => $articles
        ]);
    }

    public function latest(Request $request)
    {
        $limit = $request->input('limit', 6);
        $articles = ApprovedArticle::with('category')
            ->where('status', 'approved')
            ->latest()
            ->take($limit)
            ->get();
        return response()->json([
            'data' => $articles
        ]);
    }
    
    /**
     * Import bài viết từ scraper
     */
    public function importFromScraper(Request $request)
    {
        $request->validate([
            'articles' => 'required|array',
            'articles.*.title' => 'required|string|max:255',
            'articles.*.content' => 'required|string',
            'articles.*.source_name' => 'required|string|max:255',
            'articles.*.source_url' => 'required|string|max:255',
        ]);

        $startTime = microtime(true);
        $importedCount = 0;
        $errors = [];
        $skippedCount = 0;
        $importedArticles = [];
        
        // Log thông tin các bài viết cần import
        Log::info('Yêu cầu import bài viết từ scraper (API):', [
            'article_count' => count($request->articles),
            'first_article' => isset($request->articles[0]) ? $request->articles[0]['title'] : 'N/A'
        ]);

        // Chuẩn bị dữ liệu để kiểm tra trùng lặp
        $articleTitles = array_map(function($article) {
            return Str::slug($article['title']);
        }, $request->articles);
        
        $articleUrls = array_map(function($article) {
            return $article['source_url'];
        }, $request->articles);
        
        // Kiểm tra hàng loạt bài viết đã tồn tại (bao gồm cả đã xóa)
        $existingArticlesBySlug = Article::withTrashed()->whereIn('slug', $articleTitles)->pluck('slug')->toArray();
        $existingArticlesByUrl = Article::withTrashed()->whereIn('source_url', $articleUrls)->pluck('source_url')->toArray();
        
        Log::debug('Danh sách bài viết đã tồn tại:', [
            'existing_slugs' => $existingArticlesBySlug,
            'existing_urls' => $existingArticlesByUrl
        ]);

        // Xử lý từng bài viết
        DB::beginTransaction();
        
        try {
            foreach ($request->articles as $articleData) {
                try {
                    $slug = Str::slug($articleData['title']);
                    
                    // Kiểm tra bài viết đã tồn tại từ kết quả kiểm tra hàng loạt
                    if (in_array($slug, $existingArticlesBySlug) || 
                        in_array($articleData['source_url'], $existingArticlesByUrl)) {
                        $errors[] = "Bài viết '{$articleData['title']}' đã tồn tại";
                        $skippedCount++;
                        continue;
                    }
                    
                    $article = Article::create([
                        'title' => $articleData['title'],
                        'slug' => $slug,
                        'summary' => $articleData['summary'] ?? null,
                        'content' => $articleData['content'],
                        'source_name' => $articleData['source_name'],
                        'source_url' => $articleData['source_url'],
                        'source_icon' => $articleData['source_icon'] ?? null,
                        'published_at' => $articleData['date'] ?? now(),
                        'category' => $articleData['category'] ?? null,
                        'meta_data' => isset($articleData['meta_data']) ? $articleData['meta_data'] : null,
                        'is_processed' => false,
                        'is_ai_rewritten' => false,
                    ]);
                    
                    $importedCount++;
                    $importedArticles[] = $articleData['title'];
                    
                    Log::info("Đã import bài viết: {$articleData['title']}", [
                        'id' => $article->id,
                        'source' => $articleData['source_name']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Lỗi khi import bài viết:', [
                        'title' => $articleData['title'] ?? 'Unknown',
                        'source' => $articleData['source_name'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    $errors[] = "Lỗi khi import bài viết '{$articleData['title']}': " . $e->getMessage();
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Lỗi transaction khi import bài viết (API):', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi hệ thống khi import bài viết',
                'error' => $e->getMessage()
            ], 500);
        }

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        Log::info("Import hoàn thành (API): $importedCount bài viết đã import, $skippedCount bài viết bị bỏ qua, trong {$executionTime}s");
        
        if (count($importedArticles) > 0) {
            Log::info("Các bài viết đã import: " . implode(", ", array_slice($importedArticles, 0, 5)) . 
                (count($importedArticles) > 5 ? "... và " . (count($importedArticles) - 5) . " bài khác" : ""));
        }

        return response()->json([
            'status' => $importedCount > 0 ? 'success' : 'warning',
            'message' => $importedCount > 0 
                ? "Đã import thành công $importedCount bài viết" 
                : "Không có bài viết nào được import",
            'skipped' => $skippedCount,
            'execution_time' => $executionTime . 's',
            'errors' => $errors
        ]);
    }
}
