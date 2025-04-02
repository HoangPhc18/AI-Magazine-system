<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewrittenArticle;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\AISetting;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RewrittenArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = RewrittenArticle::query();

        // Filter by status
        if (request()->has('status') && request('status') != '') {
            $query->where('status', request('status'));
        }

        // Filter by creation date range
        if (request()->has('created_from') && request('created_from') != '') {
            $query->whereDate('created_at', '>=', request('created_from'));
        }

        if (request()->has('created_to') && request('created_to') != '') {
            $query->whereDate('created_at', '<=', request('created_to'));
        }

        // Filter by category
        if (request()->has('category_id') && request('category_id') != '') {
            $query->where('category_id', request('category_id'));
        }

        // Delete any approved articles automatically
        $query->where(function($q) {
            $q->where('status', '!=', 'approved')
              ->orWhereNull('status');
        });

        $rewrittenArticles = $query->with(['category', 'user'])
            ->latest()
            ->paginate(10);

        return view('admin.rewritten-articles.index', compact('rewrittenArticles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        $originalArticles = ApprovedArticle::where('status', 'published')->get();
        return view('admin.rewritten-articles.create', compact('categories', 'originalArticles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'original_article_id' => 'nullable|exists:approved_articles,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|max:2048',
        ]);

        // Generate slug from title
        $validated['slug'] = Str::slug($validated['title']);
        
        // Set user ID
        $validated['user_id'] = Auth::id();
        
        // Set default status
        $validated['status'] = 'pending';

        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('articles', 'public');
            $validated['featured_image'] = $imagePath;
        }

        $rewrittenArticle = RewrittenArticle::create($validated);

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'AI rewritten article created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RewrittenArticle $rewrittenArticle)
    {
        return view('admin.rewritten-articles.show', compact('rewrittenArticle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RewrittenArticle $rewrittenArticle)
    {
        $categories = Category::all();
        return view('admin.rewritten-articles.edit', compact('rewrittenArticle', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RewrittenArticle $rewrittenArticle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:pending,approved,rejected',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('articles', 'public');
            $validated['featured_image'] = $imagePath;
        }

        // Generate slug from title
        $validated['slug'] = Str::slug($validated['title']);

        $rewrittenArticle->update($validated);

        // If article is approved, move it to approved articles
        if ($validated['status'] === 'approved') {
            $this->moveToApprovedArticles($rewrittenArticle);
        }

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Article updated successfully.');
    }

    public function approve(RewrittenArticle $rewrittenArticle)
    {
        $categories = Category::all();
        return view('admin.approved-articles.create', compact('rewrittenArticle', 'categories'));
    }

    /**
     * Store the approved article from a rewritten article
     */
    public function storeApproved(ApproveArticleRequest $request, RewrittenArticle $rewrittenArticle)
    {
        try {
            // Xác thực dữ liệu từ form
            $validated = $request->validated();
            
            // Thêm slug vào dữ liệu đã xác thực
            $baseSlug = Str::slug($validated['title'] ?? $rewrittenArticle->title);
            $uniqueString = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $microtime = str_replace('.', '', microtime(true));
            $uniqueSlug = "{$baseSlug}-{$uniqueString}-{$microtime}";
            
            // Kiểm tra xem slug đã tồn tại chưa
            $existingCount = ApprovedArticle::where('slug', $uniqueSlug)->count();
            if ($existingCount > 0) {
                // Thêm một số ngẫu nhiên nếu vẫn bị trùng
                $uniqueSlug = "{$baseSlug}-{$uniqueString}-{$microtime}-" . rand(1000, 9999);
            }
            
            // Ghi log slug được tạo
            Log::info('Tạo slug mới cho bài viết được duyệt', [
                'rewritten_article_id' => $rewrittenArticle->id,
                'new_slug' => $uniqueSlug
            ]);
            
            $validated['slug'] = $uniqueSlug;
            
            // Tạo bài viết đã duyệt từ bài viết được viết lại
            try {
                $approvedArticle = $this->moveToApprovedArticles($rewrittenArticle, $validated);
                
                // Thông báo thành công
                return redirect()->route('admin.approved-articles.show', $approvedArticle)
                    ->with('success', 'Bài viết đã được duyệt và chuyển đến bài viết đã xuất bản thành công.');
            } catch (\Illuminate\Database\QueryException $e) {
                // Kiểm tra xem lỗi có phải do trùng lặp slug không
                if (strpos($e->getMessage(), 'approved_articles_slug_unique') !== false) {
                    Log::warning('Lỗi trùng lặp slug, tạo slug mới', [
                        'rewritten_article_id' => $rewrittenArticle->id,
                        'previous_slug' => $uniqueSlug,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Tạo slug mới với số ngẫu nhiên lớn hơn
                    $uniqueSlug = "{$baseSlug}-{$uniqueString}-{$microtime}-" . rand(10000, 99999);
                    $validated['slug'] = $uniqueSlug;
                    
                    // Thử lại với slug mới
                    $approvedArticle = $this->moveToApprovedArticles($rewrittenArticle, $validated);
                    
                    return redirect()->route('admin.approved-articles.show', $approvedArticle)
                        ->with('success', 'Bài viết đã được duyệt và chuyển đến bài viết đã xuất bản thành công (slug mới được tạo).');
                }
                
                // Nếu là lỗi khác, ném lại ngoại lệ
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi duyệt bài viết', [
                'rewritten_article_id' => $rewrittenArticle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.rewritten-articles.show', $rewrittenArticle)
                ->with('error', 'Đã xảy ra lỗi khi duyệt bài viết: ' . $e->getMessage());
        }
    }

    public function reject(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->update(['status' => 'rejected']);
        
        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Article has been rejected.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->delete();
        
        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Article deleted successfully.');
    }

    /**
     * Move a rewritten article to the approved articles table
     */
    private function moveToApprovedArticles(RewrittenArticle $rewrittenArticle, array $validated)
    {
        try {
            // Lưu ID của bài viết để sử dụng sau khi xóa
            $rewrittenArticleId = $rewrittenArticle->id;
            $rewrittenArticleTitle = $rewrittenArticle->title;
            $originalArticleId = $rewrittenArticle->original_article_id;

            // Tạo bài viết đã duyệt
            $approvedArticle = new ApprovedArticle();
            $approvedArticle->title = $validated['title'] ?? $rewrittenArticle->title;
            $approvedArticle->content = $validated['content'] ?? $rewrittenArticle->content;
            $approvedArticle->category_id = $validated['category_id'] ?? $rewrittenArticle->category_id;
            $approvedArticle->slug = $validated['slug'];
            $approvedArticle->seo_title = $validated['seo_title'] ?? $rewrittenArticle->title;
            $approvedArticle->seo_description = $validated['seo_description'] ?? substr(strip_tags($rewrittenArticle->content), 0, 160);
            $approvedArticle->featured_image = $rewrittenArticle->featured_image;
            $approvedArticle->status = 'published';
            $approvedArticle->user_id = Auth::id();
            $approvedArticle->is_ai_generated = $rewrittenArticle->is_ai_generated;
            $approvedArticle->original_article_id = $originalArticleId;
            $approvedArticle->word_count = str_word_count(strip_tags($validated['content'] ?? $rewrittenArticle->content));
            $approvedArticle->published_at = now();
            $approvedArticle->save();

            // Cập nhật trạng thái bài viết gốc nếu có
            if ($originalArticleId) {
                $originalArticle = OriginalArticle::find($originalArticleId);
                if ($originalArticle) {
                    $originalArticle->rewritten = true;
                    $originalArticle->save();
                }
            }

            // Cập nhật trạng thái của bài viết được viết lại trước khi xóa
            $rewrittenArticle->status = 'approved';
            $rewrittenArticle->save();
            
            // Xóa bài viết được viết lại khỏi bảng rewritten_articles
            try {
                $rewrittenArticle->forceDelete();
                
                // Ghi log để debug
                Log::info('Bài viết đã được duyệt và xóa trong moveToApprovedArticles', [
                    'rewritten_article_id' => $rewrittenArticleId,
                    'rewritten_article_title' => $rewrittenArticleTitle,
                    'approved_article_id' => $approvedArticle->id,
                    'slug' => $validated['slug']
                ]);
                
                // Kiểm tra xem bài viết đã thực sự bị xóa chưa
                $checkArticle = RewrittenArticle::withTrashed()->find($rewrittenArticleId);
                if ($checkArticle) {
                    Log::warning('Bài viết vẫn tồn tại sau khi forceDelete', [
                        'rewritten_article_id' => $rewrittenArticleId,
                        'is_trashed' => $checkArticle->trashed(),
                        'status' => $checkArticle->status
                    ]);
                    
                    // Thử xóa một lần nữa bằng query builder
                    DB::table('rewritten_articles')->where('id', $rewrittenArticleId)->delete();
                }
            } catch (\Exception $e) {
                Log::error('Lỗi khi xóa bài viết khỏi rewritten_articles trong moveToApprovedArticles', [
                    'rewritten_article_id' => $rewrittenArticleId,
                    'error' => $e->getMessage()
                ]);
            }

            return $approvedArticle;
        } catch (\Exception $e) {
            Log::error('Lỗi trong moveToApprovedArticles', [
                'error' => $e->getMessage(),
                'rewritten_article_id' => $rewrittenArticle->id
            ]);
            throw $e;
        }
    }

    /**
     * Show form for rewriting an article using AI
     */
    public function rewriteForm($originalArticleId = null)
    {
        try {
            // Log để kiểm tra hàm có được gọi không
            Log::info('Accessing rewriteForm method', [
                'originalArticleId' => $originalArticleId,
                'user_id' => Auth::id()
            ]);
            
            $categories = Category::all();
            $originalArticles = ApprovedArticle::where('status', 'published')->get();
            $selectedArticle = null;
            
            if ($originalArticleId) {
                $selectedArticle = ApprovedArticle::findOrFail($originalArticleId);
                Log::info('Selected original article', ['article_id' => $selectedArticle->id, 'title' => $selectedArticle->title]);
            }
            
            // Get AI settings
            try {
                $aiSettings = DB::table('ai_settings')->first();
                Log::info('AI settings loaded', ['settings' => $aiSettings ? true : false]);
            } catch (\Exception $e) {
                Log::error('Error querying ai_settings table: ' . $e->getMessage());
                return redirect()->route('admin.rewritten-articles.index')
                    ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng liên hệ quản trị viên.');
            }
            
            if (!$aiSettings) {
                Log::error('AI settings not found');
                return redirect()->route('admin.ai-settings.index')
                    ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng cài đặt API trước.');
            }
            
            if (empty($aiSettings->api_key)) {
                Log::error('AI API key is empty');
                return redirect()->route('admin.ai-settings.index')
                    ->with('error', 'API key AI chưa được cấu hình. Vui lòng thêm API key của bạn trong phần cài đặt AI.');
            }
            
            // Check daily limit
            if ($aiSettings->max_daily_rewrites > 0) {
                $today = now()->startOfDay();
                $dailyCount = RewrittenArticle::where('user_id', Auth::id())
                    ->where('created_at', '>=', $today)
                    ->where('ai_generated', true)
                    ->count();
                
                Log::info('Checking daily limit', [
                    'daily_count' => $dailyCount,
                    'max_limit' => $aiSettings->max_daily_rewrites
                ]);
                
                if ($dailyCount >= $aiSettings->max_daily_rewrites) {
                    return redirect()->route('admin.rewritten-articles.index')
                        ->with('error', "Bạn đã đạt đến giới hạn tối đa hàng ngày là {$aiSettings->max_daily_rewrites} bài viết AI.");
                }
            }
            
            return view('admin.rewritten-articles.rewrite', compact('categories', 'originalArticles', 'selectedArticle', 'aiSettings'));
        } catch (\Exception $e) {
            Log::error('Error in rewriteForm method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'Đã xảy ra lỗi khi hiển thị form viết lại: ' . $e->getMessage());
        }
    }

    /**
     * Process AI rewriting of an article
     */
    public function rewriteArticle(Request $request)
    {
        $validated = $request->validate([
            'original_article_id' => 'required|exists:approved_articles,id',
            'category_id' => 'required|exists:categories,id',
        ]);
        
        $originalArticle = ApprovedArticle::findOrFail($validated['original_article_id']);
        
        try {
            $aiSettings = DB::table('ai_settings')->first();
        } catch (\Exception $e) {
            Log::error('Error querying ai_settings table: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng liên hệ quản trị viên.');
        }
        
        if (!$aiSettings) {
            return redirect()->back()->with('error', 'AI settings are not configured.');
        }
        
        // Initialize AI service
        $aiService = new AIService();
        
        try {
            // Generate AI content
            $result = $aiService->rewriteArticle($originalArticle->content);
            
            if (!$result['success']) {
                Log::error('AI rewriting failed', [
                    'article_id' => $originalArticle->id,
                    'error' => $result['error']
                ]);
                return redirect()->back()
                    ->with('error', 'Failed to generate AI content: ' . $result['error']);
            }
            
            // Create the rewritten article
            $rewrittenArticle = new RewrittenArticle();
            $rewrittenArticle->title = $originalArticle->title . ' (AI Rewritten)';
            
            // Tạo slug duy nhất với nhiều yếu tố
            $baseSlug = Str::slug($originalArticle->title);
            $uniqueString = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $microtime = str_replace('.', '', microtime(true));
            $uniqueSlug = "{$baseSlug}-ai-rewritten-{$uniqueString}-{$microtime}";
            
            $rewrittenArticle->slug = $uniqueSlug;
            $rewrittenArticle->content = $result['content'];
            $rewrittenArticle->meta_title = $originalArticle->meta_title;
            $rewrittenArticle->meta_description = $originalArticle->meta_description;
            $rewrittenArticle->featured_image = $originalArticle->featured_image;
            $rewrittenArticle->category_id = $validated['category_id'];
            $rewrittenArticle->user_id = Auth::id();
            $rewrittenArticle->original_article_id = $originalArticle->id;
            $rewrittenArticle->ai_generated = true;
            $rewrittenArticle->status = $aiSettings->auto_approval ? 'approved' : 'pending';
            $rewrittenArticle->save();
            
            // If auto-approval is enabled, move to approved articles
            if ($aiSettings->auto_approval) {
                $this->moveToApprovedArticles($rewrittenArticle);
                return redirect()->route('admin.rewritten-articles.index')
                    ->with('success', 'Article has been rewritten by AI and automatically approved and published.');
            }
            
            return redirect()->route('admin.rewritten-articles.edit', $rewrittenArticle)
                ->with('success', 'Article has been rewritten by AI. Please review and make any necessary edits before approving.');
            
        } catch (\Exception $e) {
            Log::error('AI rewriting error: ' . $e->getMessage(), [
                'article_id' => $originalArticle->id
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred during AI rewriting: ' . $e->getMessage());
        }
    }

    /**
     * Show form for AI rewriting of an existing rewritten article
     */
    public function aiRewriteForm(RewrittenArticle $rewrittenArticle)
    {
        $categories = Category::orderBy('name')->get();
        
        try {
            $aiSettings = DB::table('ai_settings')->first();
        } catch (\Exception $e) {
            Log::error('Error querying ai_settings table: ' . $e->getMessage());
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng liên hệ quản trị viên.');
        }
        
        if (!$aiSettings) {
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'AI settings are not configured. Please configure them first.');
        }
        
        if (empty($aiSettings->api_key)) {
            return redirect()->route('admin.rewritten-articles.index')
                ->with('error', 'AI API key is not configured. Please add your API key in the AI settings.');
        }
        
        // Check daily limit
        if ($aiSettings->max_daily_rewrites > 0) {
            $today = now()->startOfDay();
            $dailyCount = RewrittenArticle::where('user_id', Auth::id())
                ->where('created_at', '>=', $today)
                ->where('ai_generated', true)
                ->count();
            
            if ($dailyCount >= $aiSettings->max_daily_rewrites) {
                return redirect()->route('admin.rewritten-articles.index')
                    ->with('error', "Bạn đã đạt đến giới hạn tối đa hàng ngày là {$aiSettings->max_daily_rewrites} bài viết AI.");
            }
        }
        
        return view('admin.rewritten-articles.ai-rewrite', compact('rewrittenArticle', 'categories', 'aiSettings'));
    }

    /**
     * Process AI rewriting of an existing rewritten article
     */
    public function aiRewrite(Request $request, RewrittenArticle $rewrittenArticle)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);
        
        try {
            $aiSettings = DB::table('ai_settings')->first();
        } catch (\Exception $e) {
            Log::error('Error querying ai_settings table: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Cài đặt AI chưa được thiết lập. Vui lòng liên hệ quản trị viên.');
        }
        
        if (!$aiSettings) {
            return redirect()->back()->with('error', 'Cài đặt AI chưa được thiết lập.');
        }
        
        // Initialize AI service
        $aiService = new AIService();
        
        try {
            // Generate AI content from the existing rewritten article
            $result = $aiService->rewriteArticle($rewrittenArticle->content);
            
            if (!$result['success']) {
                Log::error('AI rewriting failed', [
                    'article_id' => $rewrittenArticle->id,
                    'error' => $result['error']
                ]);
                return redirect()->back()
                    ->with('error', 'Không thể tạo nội dung AI: ' . $result['error']);
            }
            
            // Update the rewritten article with new AI content
            $rewrittenArticle->content = $result['content'];
            $rewrittenArticle->category_id = $validated['category_id'];
            $rewrittenArticle->ai_generated = true;
            $rewrittenArticle->save();
            
            // If the article is from an approved article and auto-approval is enabled, update the original
            if ($rewrittenArticle->status === 'approved' && $rewrittenArticle->original_article_id && $aiSettings->auto_approval) {
                $originalArticle = ApprovedArticle::find($rewrittenArticle->original_article_id);
                if ($originalArticle) {
                    $originalArticle->content = $result['content'];
                    $originalArticle->save();
                    
                    return redirect()->route('admin.rewritten-articles.show', $rewrittenArticle)
                        ->with('success', 'Bài viết đã được viết lại bằng AI và cập nhật vào bài viết gốc.');
                }
            }
            
            return redirect()->route('admin.rewritten-articles.show', $rewrittenArticle)
                ->with('success', 'Bài viết đã được viết lại bằng AI. Vui lòng xem lại và chỉnh sửa nếu cần thiết.');
            
        } catch (\Exception $e) {
            Log::error('AI rewriting error: ' . $e->getMessage(), [
                'article_id' => $rewrittenArticle->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Đã xảy ra lỗi trong quá trình viết lại bằng AI: ' . $e->getMessage());
        }
    }
}
