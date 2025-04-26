<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApprovedArticleController extends Controller
{
    /**
     * Display a listing of approved articles
     */
    public function index()
    {
        $query = ApprovedArticle::query();

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

        // Filter by publication date range
        if (request()->has('published_from') && request('published_from') != '') {
            $query->whereDate('published_at', '>=', request('published_from'));
        }

        if (request()->has('published_to') && request('published_to') != '') {
            $query->whereDate('published_at', '<=', request('published_to'));
        }

        // Filter by category
        if (request()->has('category_id') && request('category_id') != '') {
            $query->where('category_id', request('category_id'));
        }

        $articles = $query->with(['category', 'user', 'featuredImage'])->latest()->paginate(10);

        // Load categories for filter
        $categories = Category::all();

        return view('admin.approved-articles.index', compact('articles', 'categories'));
    }

    /**
     * Show the form for creating a new approved article
     */
    public function create()
    {
        $categories = Category::all();
        $images = Media::where('type', 'image')->latest()->take(10)->get();
        
        return view('admin.approved-articles.create', compact('categories', 'images'));
    }

    /**
     * Store a newly created approved article
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'original_article_id' => 'nullable|exists:rewritten_articles,id',
            'ai_generated' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'featured_image_id' => 'nullable|exists:media,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:published,unpublished',
        ]);

        try {
            // Bắt đầu transaction
            DB::beginTransaction();

            // Generate slug from title
            $validated['slug'] = Str::slug($validated['title']);
            
            // Set user ID
            $validated['user_id'] = Auth::id();
            
            // Handle featured image 
            if ($request->hasFile('featured_image')) {
                // Lấy file ảnh từ request
                $image = $request->file('featured_image');
                
                // Upload as a Media object
                $fileName = time() . '_' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                $path = 'images/' . date('Y/m');
                $filePath = $image->storeAs($path, $fileName, 'public');
                
                $media = Media::create([
                    'name' => $request->title,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'type' => 'image',
                    'user_id' => Auth::id(),
                ]);
                
                $validated['featured_image_id'] = $media->id;
                
                Log::info('New media created from article upload', [
                    'media_id' => $media->id,
                    'path' => $filePath,
                ]);
            }

            // Set published_at if status is published
            if ($validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            $approvedArticle = ApprovedArticle::create($validated);
            
            // Process article media entities from content
            $this->processArticleMedia($approvedArticle, $request->input('content_media_ids', []));
            
            // Commit transaction
            DB::commit();

            return redirect()->route('admin.approved-articles.show', $approvedArticle)
                ->with('success', 'Bài viết đã được tạo và xuất bản thành công.');
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::rollBack();
            
            Log::error('Lỗi khi tạo bài viết mới: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi tạo bài viết: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing an approved article
     */
    public function edit(ApprovedArticle $approvedArticle)
    {
        $categories = Category::all();
        $images = Media::where('type', 'image')->latest()->take(10)->get();
        
        return view('admin.approved-articles.edit', compact('approvedArticle', 'categories', 'images'));
    }

    /**
     * Update the specified approved article
     */
    public function update(Request $request, ApprovedArticle $approvedArticle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'featured_image_id' => 'nullable|exists:media,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:published,unpublished',
        ]);

        try {
            // Bắt đầu transaction để đảm bảo tính nhất quán dữ liệu
            DB::beginTransaction();
            
            // Generate slug from title
            $validated['slug'] = Str::slug($validated['title']);

            // Nếu không có featured_image_id mới và bài viết đã có
            // Giữ lại giá trị cũ để không bị mất
            if (!isset($validated['featured_image_id']) && $approvedArticle->featured_image_id) {
                $validated['featured_image_id'] = $approvedArticle->featured_image_id;
            }

            // Xử lý ảnh đại diện
            if ($request->hasFile('featured_image')) {
                // Lấy file ảnh từ request
                $image = $request->file('featured_image');
                
                // Upload as a Media object
                $fileName = time() . '_' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                $path = 'images/' . date('Y/m');
                $filePath = $image->storeAs($path, $fileName, 'public');
                
                $media = Media::create([
                    'name' => $request->title,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'type' => 'image',
                    'user_id' => Auth::id(),
                ]);
                
                $validated['featured_image_id'] = $media->id;
                
                Log::info('New media created from article update', [
                    'media_id' => $media->id,
                    'path' => $filePath,
                ]);
            }

            // Update publish status and date
            if ($validated['status'] === 'published' && $approvedArticle->status !== 'published') {
                $validated['published_at'] = now();
            } elseif ($validated['status'] === 'unpublished') {
                $validated['published_at'] = null;
            }

            $approvedArticle->update($validated);
            
            // Process article media entities from content
            $this->processArticleMedia($approvedArticle, $request->input('content_media_ids', []));
            
            // Commit transaction
            DB::commit();

            return redirect()->route('admin.approved-articles.index')
                ->with('success', 'Bài viết đã được cập nhật thành công.');
        } catch (\Exception $e) {
            // Rollback transaction nếu có lỗi
            DB::rollBack();
            
            Log::error('Lỗi khi cập nhật bài viết: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật bài viết: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified approved article
     */
    public function show(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->load('featuredImage', 'media');
        return view('admin.approved-articles.show', compact('approvedArticle'));
    }

    /**
     * Remove the specified article from publication
     */
    public function unpublish(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->update([
            'status' => 'unpublished',
            'published_at' => null
        ]);
        
        return redirect()->route('admin.approved-articles.index')
            ->with('success', 'Article has been unpublished.');
    }

    /**
     * Publish the specified article
     */
    public function publish(ApprovedArticle $approvedArticle)
    {
        $approvedArticle->update([
            'status' => 'published',
            'published_at' => now()
        ]);
        
        return redirect()->route('admin.approved-articles.index')
            ->with('success', 'Article has been published.');
    }

    /**
     * Remove the specified resource permanently
     */
    public function destroy(ApprovedArticle $approvedArticle)
    {
        try {
            DB::beginTransaction();
            
            $approvedArticle->delete();
            
            DB::commit();
            
            return redirect()->route('admin.approved-articles.index')
                ->with('success', 'Bài viết đã được xóa thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Lỗi khi xóa bài viết: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi xóa bài viết: ' . $e->getMessage());
        }
    }
    
    /**
     * Process article media entities from content
     */
    private function processArticleMedia($article, $mediaIds)
    {
        // If no media IDs were provided, no processing is needed
        if (empty($mediaIds)) {
            return;
        }
        
        // Convert string to array if needed
        if (is_string($mediaIds)) {
            $mediaIds = explode(',', $mediaIds);
        }
        
        // Filter out empty values
        $mediaIds = array_filter($mediaIds);
        
        if (empty($mediaIds)) {
            return;
        }
        
        Log::info('Processing article media', [
            'article_id' => $article->id,
            'media_ids' => $mediaIds
        ]);
        
        try {
            // Get the media items
            $mediaItems = Media::whereIn('id', $mediaIds)->get();
            
            if ($mediaItems->isEmpty()) {
                Log::warning('No media items found for IDs', ['media_ids' => $mediaIds]);
                return;
            }
            
            // Attach media to article (synchronize rather than attach to avoid duplicates)
            $article->media()->syncWithoutDetaching($mediaIds);
            
            Log::info('Media attached to article successfully', [
                'article_id' => $article->id,
                'media_count' => $mediaItems->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing article media', [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Run the scraper to fetch and process news articles
     */
    public function runScraper()
    {
        try {
            // Chạy lệnh docker trong nền (background) để tránh timeout
            $command = 'docker exec thp_214476_scraper python main.py --all > ' . storage_path('logs/scraper_output.log') . ' 2>&1 &';
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Trên Windows, sử dụng 'start /B' để chạy trong nền
                $command = 'start /B ' . $command;
                // Thực thi lệnh và không đợi kết quả
                pclose(popen($command, 'r'));
            } else {
                // Trên Linux/Unix
                exec($command);
            }
            
            // Ghi log thông báo đã kích hoạt scraper
            \Log::info('Scraper triggered in background');
            
            // Tạo mảng output mẫu để hiển thị
            $output = [
                'Scraper đã được kích hoạt và đang chạy trong nền.',
                'Quá trình sẽ tiếp tục chạy ngay cả khi bạn rời khỏi trang này.',
                'Bài viết sẽ được tự động thu thập và import vào hệ thống.',
                'Vui lòng quay lại danh sách bài viết sau vài phút để xem kết quả.'
            ];
            
            // Lưu output mẫu vào session
            session(['scraper_output' => $output]);
            
            return redirect()->route('admin.approved-articles.scraper-results')
                ->with('success', 'Đã kích hoạt scraper thành công. Quá trình đang chạy trong nền.');
                
        } catch (\Exception $e) {
            \Log::error('Error triggering scraper', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.approved-articles.index')
                ->with('error', 'Đã xảy ra lỗi khi kích hoạt scraper: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the results of the scraper execution
     */
    public function scraperResults()
    {
        $output = session('scraper_output', []);
        // Đảm bảo luôn có dữ liệu để hiển thị
        if (empty($output)) {
            $output = [
                'Không có dữ liệu output từ lần chạy gần nhất của scraper.',
                'Bạn có thể chạy scraper bằng cách nhấn nút "Chạy lại Scraper" bên dưới.'
            ];
        }
        
        // Kiểm tra xem có log output không
        $log_output = $this->getScraperLogOutput();
        if (!empty($log_output)) {
            $output = array_merge($output, ['---', 'Log output từ file:'], $log_output);
        }
        
        return view('admin.approved-articles.scraper-results', compact('output'));
    }
    
    /**
     * Check scraper status and return log output
     */
    private function getScraperLogOutput()
    {
        $log_file = storage_path('logs/scraper_output.log');
        if (file_exists($log_file)) {
            // Đọc 50 dòng cuối cùng của file log
            $lines = [];
            $fp = fopen($log_file, 'r');
            if ($fp) {
                // Đọc file vào mảng và lấy tối đa 50 dòng cuối
                $position = -1;
                $line_count = 0;
                $max_lines = 50;
                
                while ($line_count < $max_lines && fseek($fp, $position, SEEK_END) >= 0) {
                    $char = fgetc($fp);
                    if ($char === "\n") {
                        $line_count++;
                    }
                    $position--;
                }
                
                // Nếu đã đọc đủ số dòng hoặc đã đọc hết file
                if ($line_count < $max_lines || fseek($fp, $position, SEEK_END) < 0) {
                    rewind($fp); // Quay về đầu file
                }
                
                // Đọc các dòng
                while (!feof($fp) && count($lines) < $max_lines) {
                    $line = fgets($fp);
                    if ($line !== false) {
                        $lines[] = trim($line);
                    }
                }
                fclose($fp);
            }
            return $lines;
        }
        return [];
    }

    /**
     * Check the status of running scraper job (AJAX endpoint)
     */
    public function checkScraperStatus()
    {
        $log_output = $this->getScraperLogOutput();
        $is_running = false;
        
        // Kiểm tra xem scraper có đang chạy không bằng cách kiểm tra process
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: kiểm tra bằng tasklist
            $output = [];
            exec('tasklist /FI "IMAGENAME eq python.exe" /NH', $output);
            $is_running = count($output) > 0 && strpos(implode(' ', $output), 'python.exe') !== false;
        } else {
            // Linux/Unix: kiểm tra bằng ps
            $output = [];
            exec('ps aux | grep "[p]ython main.py --all"', $output);
            $is_running = count($output) > 0;
        }
        
        return response()->json([
            'is_running' => $is_running,
            'log_output' => $log_output,
            'last_updated' => now()->format('H:i:s - d/m/Y')
        ]);
    }

    /**
     * Run the article rewriter to process articles
     */
    public function runRewriter(Request $request)
    {
        try {
            // Chạy lệnh docker trong nền (background) để tránh timeout
            $command = 'docker exec thp_214476_rewrite python rewrite_from_db.py > ' . storage_path('logs/rewriter_output.log') . ' 2>&1 &';
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Trên Windows, sử dụng 'start /B' để chạy trong nền
                $command = 'start /B ' . $command;
                // Thực thi lệnh và không đợi kết quả
                pclose(popen($command, 'r'));
            } else {
                // Trên Linux/Unix
                exec($command);
            }
            
            // Ghi log thông báo đã kích hoạt rewriter
            \Log::info('Article rewriter triggered in background');
            
            // Tạo mảng output mẫu để hiển thị
            $output = [
                'Module rewriter đã được kích hoạt và đang chạy trong nền.',
                'Quá trình sẽ tiếp tục chạy ngay cả khi bạn rời khỏi trang này.',
                'Các bài viết sẽ được tự động viết lại và import vào hệ thống.',
                'Vui lòng kiểm tra danh sách bài viết đã viết lại sau vài phút.'
            ];
            
            // Lưu output mẫu vào session
            session(['rewriter_output' => $output]);
            
            return redirect()->route('admin.approved-articles.rewriter-results')
                ->with('success', 'Đã kích hoạt module rewriter thành công. Quá trình đang chạy trong nền.');
                
        } catch (\Exception $e) {
            \Log::error('Error triggering rewriter', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.approved-articles.index')
                ->with('error', 'Đã xảy ra lỗi khi kích hoạt rewriter: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the results of the rewriter execution
     */
    public function rewriterResults()
    {
        $output = session('rewriter_output', []);
        // Đảm bảo luôn có dữ liệu để hiển thị
        if (empty($output)) {
            $output = [
                'Không có dữ liệu output từ lần chạy gần nhất của rewriter.',
                'Bạn có thể chạy rewriter bằng cách nhấn nút "Chạy Rewriter" bên dưới.'
            ];
        }
        
        // Kiểm tra xem có log output không
        $log_output = $this->getRewriterLogOutput();
        if (!empty($log_output)) {
            $output = array_merge($output, ['---', 'Log output từ file:'], $log_output);
            
            // Kiểm tra nếu có thông báo thành công trong log
            $log_text = implode(' ', $log_output);
            if (strpos($log_text, 'Successfully rewritten and saved') !== false || 
                strpos($log_text, 'Saved rewritten article') !== false) {
                session()->flash('success', 'Quá trình viết lại bài viết đã hoàn thành thành công!');
            }
        }
        
        return view('admin.approved-articles.rewriter-results', compact('output'));
    }
    
    /**
     * Check rewriter status and return log output
     */
    private function getRewriterLogOutput()
    {
        $log_file = storage_path('logs/rewriter_output.log');
        if (file_exists($log_file)) {
            // Đọc 50 dòng cuối cùng của file log
            $lines = [];
            $fp = fopen($log_file, 'r');
            if ($fp) {
                // Đọc file vào mảng và lấy tối đa 50 dòng cuối
                $position = -1;
                $line_count = 0;
                $max_lines = 50;
                
                while ($line_count < $max_lines && fseek($fp, $position, SEEK_END) >= 0) {
                    $char = fgetc($fp);
                    if ($char === "\n") {
                        $line_count++;
                    }
                    $position--;
                }
                
                // Nếu đã đọc đủ số dòng hoặc đã đọc hết file
                if ($line_count < $max_lines || fseek($fp, $position, SEEK_END) < 0) {
                    rewind($fp); // Quay về đầu file
                }
                
                // Đọc các dòng
                while (!feof($fp) && count($lines) < $max_lines) {
                    $line = fgets($fp);
                    if ($line !== false) {
                        $lines[] = trim($line);
                    }
                }
                fclose($fp);
            }
            return $lines;
        }
        return [];
    }
    
    /**
     * Check the status of running rewriter job (AJAX endpoint)
     */
    public function checkRewriterStatus()
    {
        $log_output = $this->getRewriterLogOutput();
        $is_running = false;
        
        // Kiểm tra xem rewriter có đang chạy không bằng cách kiểm tra process trong Docker
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: kiểm tra bằng Docker
            $output = [];
            exec('docker exec thp_214476_rewrite ps aux | grep "[p]ython rewrite_from_db.py"', $output);
            $is_running = count($output) > 0;
        } else {
            // Linux/Unix: kiểm tra bằng Docker
            $output = [];
            exec('docker exec thp_214476_rewrite ps aux | grep "[p]ython rewrite_from_db.py"', $output);
            $is_running = count($output) > 0;
        }
        
        return response()->json([
            'is_running' => $is_running,
            'log_output' => $log_output,
            'last_updated' => now()->format('H:i:s - d/m/Y')
        ]);
    }
} 