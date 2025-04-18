<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPost;
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
            $apiUrl = config('services.facebook_scraper.api_url', 'http://localhost:5000');
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
        return view('admin.facebook-posts.show', compact('facebookPost'));
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
} 