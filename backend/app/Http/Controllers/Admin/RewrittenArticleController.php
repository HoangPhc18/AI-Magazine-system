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
        } else {
            // Mặc định chỉ hiển thị các bài viết chưa được duyệt
            $query->where('status', '!=', 'approved');
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
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'pending';

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('public/featured_images');
            $validated['featured_image'] = str_replace('public/', '', $path);
        }

        RewrittenArticle::create($validated);

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Bài viết đã được tạo thành công và đang chờ duyệt.');
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
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('public/featured_images');
            $validated['featured_image'] = str_replace('public/', '', $path);
        }

        $rewrittenArticle->update($validated);

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Bài viết đã được cập nhật thành công.');
    }

    public function approve(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->update(['status' => 'approved']);

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Bài viết đã được phê duyệt thành công.');
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
            ->with('success', 'Bài viết đã bị từ chối.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RewrittenArticle $rewrittenArticle)
    {
        $rewrittenArticle->delete();

        return redirect()->route('admin.rewritten-articles.index')
            ->with('success', 'Bài viết đã được xóa thành công.');
    }

    /**
     * Process approving a rewritten article
     */
    private function moveToApprovedArticles(RewrittenArticle $rewrittenArticle)
    {
        try {
            DB::beginTransaction();
            
            // Tạo bài viết đã duyệt
            $approvedArticle = new ApprovedArticle();
            $approvedArticle->title = $rewrittenArticle->title;
            $approvedArticle->slug = $rewrittenArticle->slug;
            $approvedArticle->content = $rewrittenArticle->content;
            $approvedArticle->meta_title = $rewrittenArticle->meta_title;
            $approvedArticle->meta_description = $rewrittenArticle->meta_description;
            $approvedArticle->featured_image = $rewrittenArticle->featured_image;
            $approvedArticle->user_id = Auth::id();
            $approvedArticle->category_id = $rewrittenArticle->category_id;
            $approvedArticle->original_article_id = $rewrittenArticle->original_article_id;
            $approvedArticle->status = 'published';
            $approvedArticle->ai_generated = $rewrittenArticle->ai_generated;
            $approvedArticle->published_at = now();
            $approvedArticle->save();
            
            // Xóa bài viết đã duyệt từ RewrittenArticle để không hiển thị nữa
            $rewrittenArticle->forceDelete();
            
            DB::commit();

            Log::info('Bài viết đã được duyệt và chuyển sang ApprovedArticle', [
                'rewritten_id' => $rewrittenArticle->id,
                'approved_id' => $approvedArticle->id
            ]);
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi chuyển bài viết sang ApprovedArticle: ' . $e->getMessage(), [
                'rewritten_id' => $rewrittenArticle->id
            ]);
            return false;
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
        $request->validate([
            'original_article_id' => 'required|exists:approved_articles,id',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'content' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $originalArticle = ApprovedArticle::findOrFail($request->original_article_id);
            
            $rewrittenArticle = new RewrittenArticle();
            $rewrittenArticle->title = $originalArticle->title;
            $rewrittenArticle->slug = Str::slug($originalArticle->title . '-' . time());
            $rewrittenArticle->content = $request->content;
            $rewrittenArticle->meta_title = $originalArticle->meta_title;
            $rewrittenArticle->meta_description = $originalArticle->meta_description;
            $rewrittenArticle->user_id = Auth::id();
            $rewrittenArticle->category_id = $request->category_id;
            $rewrittenArticle->original_article_id = $originalArticle->id;
            $rewrittenArticle->status = 'pending';
            $rewrittenArticle->ai_generated = false;

            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                $path = $request->file('featured_image')->store('public/featured_images');
                $rewrittenArticle->featured_image = str_replace('public/', '', $path);
            } else {
                // Copy featured image from original article if exists
                if ($originalArticle->featured_image) {
                    $rewrittenArticle->featured_image = $originalArticle->featured_image;
                }
            }

            $rewrittenArticle->save();

            DB::commit();

            return redirect()->route('admin.rewritten-articles.index')
                ->with('success', 'Bài viết đã được tạo thành công và đang chờ duyệt.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating rewritten article: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo bài viết. Vui lòng thử lại.')
                ->withInput();
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
