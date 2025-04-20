<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPost;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPostController extends Controller
{
    /**
     * Display a listing of the facebook posts.
     */
    public function index()
    {
        $posts = FacebookPost::latest()->paginate(10);
        return view('admin.facebook-posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new facebook post scraping job.
     */
    public function create()
    {
        return view('admin.facebook-posts.create');
    }

    /**
     * Start a scraping job for the facebook posts.
     */
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'use_profile' => 'nullable|boolean',
            'chrome_profile' => 'nullable|string',
        ]);

        $url = $request->input('url');
        $useProfile = $request->boolean('use_profile', true);
        $chromeProfile = $request->input('chrome_profile', 'Default');
        
        // Ghi log thông tin request để debug
        Log::info('Facebook Post scrape request', [
            'url' => $url,
            'use_profile' => $useProfile,
            'chrome_profile' => $chromeProfile
        ]);
        
        // Thử sử dụng API trước
        try {
            // Xác định API URL
            $apiUrl = config('services.facebook_scraper.api_url', 'http://localhost:5000');
            $pingUrl = rtrim($apiUrl, '/') . "/health";
            
            // Ping API để kiểm tra xem service đang chạy không
            $pingResponse = Http::timeout(2)->get($pingUrl);
            
            $apiUrl = rtrim($apiUrl, '/') . '/api/scrape';
            
            $requestData = [
                'url' => $url,
                'use_profile' => $useProfile,
                'chrome_profile' => $chromeProfile,
                'limit' => 10
            ];
            
            Log::info('Calling Facebook Scraper API', ['api_url' => $apiUrl]);
            
            $response = Http::timeout(5)->post($apiUrl, $requestData);
            
            if ($response->successful()) {
                $data = $response->json();
                $jobId = $data['job_id'] ?? 'unknown';
                
                Log::info('Facebook Scraper API success', ['response' => $data]);
                
                return redirect()->route('admin.facebook-posts.index')
                                 ->with('success', "Đã bắt đầu thu thập bài viết qua API (Job ID: {$jobId}). Quá trình này có thể mất vài phút.");
            } else {
                Log::warning('Facebook Scraper API failed, falling back to command line', [
                    'status' => $response->status(),
                    'body' => $response->body() 
                ]);
                
                // API không hoạt động, thử sử dụng command line
                return $this->fallbackToCommandLine($url, $useProfile, $chromeProfile);
            }
        } catch (\Exception $e) {
            Log::warning('Facebook Scraper API exception, falling back to command line', [
                'message' => $e->getMessage()
            ]);
            
            // API gặp lỗi, thử sử dụng command line
            return $this->fallbackToCommandLine($url, $useProfile, $chromeProfile);
        }
    }
    
    /**
     * Sử dụng command line khi API không hoạt động
     */
    private function fallbackToCommandLine($url, $useProfile, $chromeProfile)
    {
        try {
            // Xác định đường dẫn đến file scraper
            $pythonScript = base_path('../ai_service/facebook_scraper/scraper_facebook.py');
            
            // Command để chạy script Python với các đối số
            $command = "python \"{$pythonScript}\" " . 
                       "--url \"{$url}\" " . 
                       "--save_to_db true " . 
                       "--headless true " . 
                       "--use_profile " . ($useProfile ? "true" : "false");
            
            if ($useProfile && $chromeProfile) {
                $command .= " --chrome_profile \"{$chromeProfile}\"";
            }
            
            Log::info('Falling back to command line', ['command' => $command]);
            
            // Thực thi lệnh (không đợi kết quả để không block request)
            $process = Process::timeout(300)->start($command);
            
            return redirect()->route('admin.facebook-posts.index')
                             ->with('success', 'Đã bắt đầu thu thập bài viết qua command line. Quá trình này có thể mất vài phút.');
        } catch (\Exception $e) {
            Log::error('Command line scraper failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                             ->with('error', 'Không thể thu thập bài viết: ' . $e->getMessage())
                             ->withInput();
        }
    }

    /**
     * Display the specified facebook post.
     */
    public function show(FacebookPost $facebookPost)
    {
        $categories = Category::all();
        return view('admin.facebook-posts.show', compact('facebookPost', 'categories'));
    }

    /**
     * Remove the specified facebook post.
     */
    public function destroy(FacebookPost $facebookPost)
    {
        $facebookPost->delete();
        return redirect()->route('admin.facebook-posts.index')
                         ->with('success', 'Bài viết đã được xóa thành công.');
    }

    /**
     * Mark specified facebook post as processed.
     */
    public function markAsProcessed(FacebookPost $facebookPost)
    {
        $facebookPost->update(['processed' => true]);
        return redirect()->back()->with('success', 'Bài viết đã được đánh dấu là đã xử lý.');
    }

    /**
     * Mark specified facebook post as unprocessed.
     */
    public function markAsUnprocessed(FacebookPost $facebookPost)
    {
        $facebookPost->update(['processed' => false]);
        return redirect()->back()->with('success', 'Bài viết đã được đánh dấu là chưa xử lý.');
    }

    /**
     * Rewrite Facebook post using AI.
     */
    public function rewrite(FacebookPost $facebookPost)
    {
        try {
            // Call the Facebook rewrite service API
            $rewriteServiceUrl = config('services.facebook_rewrite.url', 'http://localhost:5001');
            $endpoint = $rewriteServiceUrl . '/api/rewrite';
            
            // Tăng timeout từ 120 lên 600 giây (10 phút)
            $response = Http::timeout(600)
                ->post($endpoint, [
                    'text' => $facebookPost->content,
                    'post_id' => $facebookPost->id
                ]);
            
            if (!$response->successful()) {
                Log::error('Facebook rewrite service error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return redirect()->back()->with('error', 'Lỗi khi viết lại bài viết: ' . $response->status());
            }
            
            $result = $response->json();
            
            // Check if rewrite was successful
            if (!isset($result['rewritten']) || !isset($result['saved_to_db'])) {
                return redirect()->back()->with('error', 'Phản hồi không hợp lệ từ dịch vụ viết lại.');
            }
            
            // Redirect with success message
            if ($result['saved_to_db']) {
                return redirect()->back()->with('success', 'Bài viết đã được viết lại và lưu vào cơ sở dữ liệu thành công.');
            } else {
                $categories = Category::all();
                // If not saved to DB, pass rewritten content to view for manual saving
                return view('admin.facebook-posts.show', [
                    'facebookPost' => $facebookPost,
                    'categories' => $categories,
                    'rewrittenTitle' => $result['rewritten']['title'] ?? '',
                    'rewrittenContent' => $result['rewritten']['content'] ?? ''
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Facebook rewrite error', [
                'message' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Process a batch of Facebook posts.
     */
    public function processBatch(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        $limit = $request->input('limit', 5);

        try {
            // Call the batch processing endpoint
            $rewriteServiceUrl = config('services.facebook_rewrite.url', 'http://localhost:5001');
            $endpoint = $rewriteServiceUrl . '/api/process-batch';

            // Tăng timeout từ 300 lên 900 giây (15 phút) cho xử lý hàng loạt
            $response = Http::timeout(900)
                ->post($endpoint, [
                    'limit' => $limit
                ]);

            if (!$response->successful()) {
                Log::error('Facebook batch rewrite service error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return redirect()->back()->with('error', 'Lỗi khi xử lý hàng loạt: ' . $response->status());
            }

            $result = $response->json();
            $processedCount = $result['processed_count'] ?? 0;

            return redirect()->back()->with('success', "Đã xử lý thành công {$processedCount} bài viết.");

        } catch (\Exception $e) {
            Log::error('Facebook batch rewrite error', [
                'message' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Show form to manually create rewritten article.
     */
    public function showRewriteForm(FacebookPost $facebookPost)
    {
        $categories = Category::all();
        return view('admin.facebook-posts.rewrite', compact('facebookPost', 'categories'));
    }

    /**
     * Manually create a rewritten article.
     */
    public function saveRewrittenArticle(Request $request, FacebookPost $facebookPost)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id'
        ]);

        try {
            // Call the API to create rewritten article
            $rewriteServiceUrl = config('services.facebook_rewrite.url', 'http://localhost:5001');
            $endpoint = "/api/admin/facebook/create-article";
            
            // Tăng timeout cho API lưu bài viết
            $response = Http::timeout(180)
                ->post($endpoint, [
                    'post_id' => $facebookPost->id,
                    'title' => $request->title,
                    'content' => $request->content,
                    'category_id' => $request->category_id
                ]);
            
            if (!$response->successful()) {
                return redirect()->back()
                    ->with('error', 'Không thể lưu bài viết: ' . $response->body())
                    ->withInput();
            }
            
            return redirect()->route('admin.facebook-posts.index')
                ->with('success', 'Đã tạo bài viết viết lại thành công.');
                
        } catch (\Exception $e) {
            Log::error('Error saving rewritten article', [
                'message' => $e->getMessage(),
                'post_id' => $facebookPost->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Lỗi: ' . $e->getMessage())
                ->withInput();
        }
    }
} 