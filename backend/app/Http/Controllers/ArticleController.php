<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $articles = Article::latest()->paginate(10);
        return response()->json([
            'status' => 'success',
            'data' => $articles
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'source_name' => 'required|string|max:255',
            'source_url' => 'required|string|max:255',
            'published_at' => 'required|date',
        ]);

        $slug = Str::slug($request->title);
        
        try {
            // Kiểm tra xem bài viết đã tồn tại chưa
            $existingArticle = Article::where('slug', $slug)
                ->orWhere('source_url', $request->source_url)
                ->first();
            
            if ($existingArticle) {
                $message = "Bài viết '{$request->title}' đã tồn tại";
                Log::warning($message, [
                    'slug' => $slug,
                    'source_url' => $request->source_url
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => $message
                ], 422);
            }
            
            // Debug thông tin request
            Log::info('Yêu cầu tạo bài viết mới:', [
                'title' => $request->title,
                'slug' => $slug,
                'source' => $request->source_name
            ]);
            
            $article = Article::create([
                'title' => $request->title,
                'slug' => $slug,
                'summary' => $request->summary,
                'content' => $request->content,
                'source_name' => $request->source_name,
                'source_url' => $request->source_url,
                'source_icon' => $request->source_icon,
                'published_at' => $request->published_at,
                'category' => $request->category,
                'meta_data' => $request->meta_data,
                'is_processed' => false,
                'is_ai_rewritten' => false,
            ]);
            
            Log::info('Bài viết đã được tạo thành công:', [
                'id' => $article->id,
                'title' => $article->title
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Bài viết đã được lưu thành công',
                'data' => $article
            ], 201);
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            Log::error('Lỗi khi lưu bài viết:', [
                'message' => $errorMessage,
                'code' => $errorCode,
                'title' => $request->title ?? 'N/A',
                'slug' => $slug ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể lưu bài viết',
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'debug_info' => [
                    'title' => $request->title,
                    'slug' => $slug
                ]
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $article = Article::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy bài viết',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
            'source_name' => 'string|max:255',
            'source_url' => 'string|max:255',
            'published_at' => 'date',
        ]);

        try {
            $article = Article::findOrFail($id);
            
            if ($request->has('title')) {
                $article->slug = Str::slug($request->title);
            }
            
            $article->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Bài viết đã được cập nhật thành công',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật bài viết: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật bài viết',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $article = Article::findOrFail($id);
            $article->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Bài viết đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa bài viết: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể xóa bài viết',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách bài viết chưa được xử lý AI
     */
    public function getUnprocessedArticles()
    {
        $articles = Article::unprocessed()->latest()->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $articles
        ]);
    }

    /**
     * Cập nhật nội dung AI cho bài viết
     */
    public function updateAiContent(Request $request, string $id)
    {
        $request->validate([
            'ai_rewritten_content' => 'required|string',
        ]);

        try {
            $article = Article::findOrFail($id);
            $article->markAsRewritten($request->ai_rewritten_content);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Nội dung AI đã được cập nhật thành công',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật nội dung AI: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật nội dung AI',
                'error' => $e->getMessage()
            ], 500);
        }
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
        Log::info('Yêu cầu import bài viết từ scraper:', [
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
            Log::error('Lỗi transaction khi import bài viết:', [
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
        
        Log::info("Import hoàn thành: $importedCount bài viết đã import, $skippedCount bài viết bị bỏ qua, trong {$executionTime}s");
        
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
