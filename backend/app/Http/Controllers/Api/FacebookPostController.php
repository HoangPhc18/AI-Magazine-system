<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FacebookPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
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
        return response()->json($posts);
    }

    /**
     * Display the specified facebook post.
     */
    public function show($id)
    {
        $post = FacebookPost::findOrFail($id);
        return response()->json($post);
    }

    /**
     * Start a scraping job using the API.
     */
    public function scrapeFromApi(Request $request)
    {
        // Thêm debug log để kiểm tra request
        Log::info('Facebook Post API scrape request received', [
            'request_data' => $request->all(),
            'url' => $request->input('url'),
            'headers' => $request->header()
        ]);

        try {
            // Validate input
            $request->validate([
                'url' => 'required|url',
                'use_profile' => 'nullable|boolean',
                'chrome_profile' => 'nullable|string',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            // Get input values
            $url = $request->input('url');
            $useProfile = $request->boolean('use_profile', true);
            $chromeProfile = $request->input('chrome_profile', 'Default');
            $limit = $request->input('limit', 10);

            // Log request details for debugging
            Log::info('Facebook Post API scrape request processed', [
                'url' => $url,
                'use_profile' => $useProfile,
                'chrome_profile' => $chromeProfile,
                'limit' => $limit
            ]);

            // Tạo một job ID duy nhất
            $jobId = 'job_' . time() . rand(1000, 9999);
            
            // Thử kết nối với API để kiểm tra tình trạng hoạt động
            try {
                // Xác định API URL
                $apiUrl = config('services.facebook_scraper.api_url', 'http://localhost:55025/facebook-scraper');
                $pingUrl = rtrim($apiUrl, '/') . "/health";
                
                // Log thông tin kết nối API
                Log::info('Pinging Facebook Scraper API', ['ping_url' => $pingUrl]);
                
                // Ping API
                $pingResponse = Http::timeout(2)->get($pingUrl);
                
                if ($pingResponse->successful()) {
                    Log::info('API ping successful, API service is running');
                    
                    // API đang hoạt động, gửi yêu cầu scrape
                    $scrapeUrl = rtrim($apiUrl, '/') . "/api/scrape";
                    
                    $requestData = [
                        'url' => $url,
                        'use_profile' => $useProfile,
                        'chrome_profile' => $chromeProfile,
                        'limit' => $limit,
                        'job_id' => $jobId
                    ];
                    
                    // Log thông tin API call
                    Log::info('Calling Facebook Scraper API', [
                        'scrape_url' => $scrapeUrl,
                        'request_data' => $requestData
                    ]);
                    
                    // Gọi API scrape
                    $scrapeResponse = Http::timeout(5)->post($scrapeUrl, $requestData);
                    
                    if ($scrapeResponse->successful()) {
                        $data = $scrapeResponse->json();
                        Log::info('API scrape request successful', ['response' => $data]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'Đã bắt đầu thu thập bài viết qua API',
                            'job_id' => $data['job_id'] ?? $jobId
                        ]);
                    } else {
                        // API trả về lỗi
                        Log::warning('API scrape request returned error', [
                            'status' => $scrapeResponse->status(),
                            'body' => $scrapeResponse->body()
                        ]);
                        
                        throw new \Exception('API service returned error: ' . $scrapeResponse->status());
                    }
                } else {
                    // API không phản hồi
                    Log::warning('API ping failed, service may be down', [
                        'status' => $pingResponse->status(),
                        'body' => $pingResponse->body()
                    ]);
                    
                    throw new \Exception('API service is not responding');
                }
            } catch (\Exception $e) {
                // Lỗi khi kết nối API, chuyển sang thực thi trực tiếp
                Log::warning('API connection failed, falling back to direct execution', [
                    'message' => $e->getMessage()
                ]);
            }
            
            // Xác định đường dẫn đến file scraper
            $pythonScript = base_path('../ai_service/facebook_scraper/scraper_facebook.py');
            
            // Check if the Python script exists
            if (!file_exists($pythonScript)) {
                Log::error('Python script not found', ['path' => $pythonScript]);
                throw new \Exception("Python script not found at: {$pythonScript}");
            }
            
            // Command để chạy script Python với các đối số
            $command = "python \"{$pythonScript}\" " . 
                        "--url \"{$url}\" " . 
                        "--save_to_db true " . 
                        "--headless true " . 
                        "--limit {$limit} " . 
                        "--use_profile " . ($useProfile ? "true" : "false");
            
            if ($useProfile && $chromeProfile) {
                $command .= " --chrome_profile \"{$chromeProfile}\"";
            }
            
            // Log thông tin command sẽ chạy
            Log::info('Running scraper command directly', ['command' => $command]);
            
            try {
                // Chạy script Python không đồng bộ (không chờ kết quả)
                $process = Process::timeout(1)->start($command);
                
                Log::info('Process started successfully', ['job_id' => $jobId]);
                
                // Trả về thành công ngay lập tức, không chờ kết quả
                return response()->json([
                    'success' => true,
                    'message' => 'Đã bắt đầu thu thập bài viết trực tiếp',
                    'job_id' => $jobId,
                    'execution_mode' => 'direct'
                ]);
            } catch (\Exception $e) {
                Log::error('Error starting process', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Facebook Scraper API validation error', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Facebook Scraper API unhandled exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thu thập bài viết: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Execute the scrape command.
     */
    public function scrape(Request $request)
    {
        // Validate input
        $request->validate([
            'url' => 'required|url',
            'use_profile' => 'nullable|boolean',
            'chrome_profile' => 'nullable|string',
        ]);

        // Get input values
        $url = $request->input('url');
        $useProfile = $request->boolean('use_profile', true);
        $chromeProfile = $request->input('chrome_profile', 'Default');
        
        try {
            // Determine path to Python script
            $pythonScript = base_path('../ai_service/facebook_scraper/scraper_facebook.py');
            
            // Command to run Python script with arguments
            $command = "python \"{$pythonScript}\" " . 
                       "--url \"{$url}\" " . 
                       "--save_to_db true " . 
                       "--headless true " . 
                       "--use_profile " . ($useProfile ? "true" : "false");
            
            if ($useProfile && $chromeProfile) {
                $command .= " --chrome_profile \"{$chromeProfile}\"";
            }
            
            // Log the command for debugging
            Log::info('Running Facebook scraper command', ['command' => $command]);
            
            // Execute the command
            $process = proc_open($command, [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"],  // stderr
            ], $pipes);
            
            if (is_resource($process)) {
                // Close stdin
                fclose($pipes[0]);
                
                // Read stdout
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                
                // Read stderr
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                
                // Close process
                $returnCode = proc_close($process);
                
                // Log results
                Log::info('Facebook scraper command completed', [
                    'return_code' => $returnCode,
                    'output' => $output,
                    'error' => $error
                ]);
                
                return response()->json([
                    'success' => $returnCode === 0,
                    'message' => $returnCode === 0 ? 'Đã thu thập bài viết thành công' : 'Lỗi khi thu thập bài viết',
                    'output' => $output,
                    'error' => $error
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể khởi chạy quy trình thu thập'
            ], 500);
            
        } catch (\Exception $e) {
            // Log exceptions
            Log::error('Facebook scraper command exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi chạy lệnh thu thập: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the status of a specific job.
     */
    public function getJobStatus($jobId)
    {
        try {
            // Log phương thức được gọi
            Log::info('Facebook Post job status check requested', ['job_id' => $jobId]);
            
            // Get API URL from config
            $apiUrl = config('services.facebook_scraper.api_url', 'http://localhost:55025/facebook-scraper');
            $apiUrl = rtrim($apiUrl, '/') . "/api/jobs/{$jobId}";
            
            // Log API call
            Log::info('Checking job status via API', ['job_id' => $jobId, 'api_url' => $apiUrl]);
            
            try {
                // Call the API with reduced timeout
                $response = Http::timeout(3)->get($apiUrl);
                
                // Handle successful response
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('Job status retrieved from API', ['job_id' => $jobId, 'status' => $data]);
                    
                    return response()->json($data);
                }
                
                // Log API error
                Log::warning('Error getting job status from API', [
                    'job_id' => $jobId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            } catch (\Exception $e) {
                // Log API connection error
                Log::warning('Could not connect to Facebook Scraper API', [
                    'job_id' => $jobId,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Nếu API không khả dụng, mô phỏng một phản hồi trạng thái
            // Truy vấn cơ sở dữ liệu để kiểm tra xem đã có bài viết được tạo gần đây không
            $recentPostsCount = FacebookPost::where('created_at', '>=', now()->subMinutes(5))->count();
            
            // Nếu có bài viết gần đây, coi như job đã hoàn thành
            if ($recentPostsCount > 0) {
                $simulatedResponse = [
                    'status' => 'completed',
                    'job_id' => $jobId,
                    'posts_count' => $recentPostsCount,
                    'message' => 'Job hoàn thành, đã tìm thấy ' . $recentPostsCount . ' bài viết',
                    'simulated' => true
                ];
                
                Log::info('Returning simulated completed job status', [
                    'job_id' => $jobId,
                    'recent_posts' => $recentPostsCount
                ]);
                
                return response()->json($simulatedResponse);
            }
            
            // Trường hợp không có bài viết mới, trả về trạng thái đang chạy
            $simulatedResponse = [
                'status' => 'running',
                'job_id' => $jobId,
                'progress' => 'Đang xử lý...',
                'simulated' => true
            ];
            
            Log::info('Returning simulated running job status', ['job_id' => $jobId]);
            
            return response()->json($simulatedResponse);
            
        } catch (\Exception $e) {
            // Log exceptions
            Log::error('Job status exception', [
                'job_id' => $jobId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi kiểm tra trạng thái job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all jobs.
     */
    public function getAllJobs()
    {
        try {
            // Log phương thức được gọi
            Log::info('Facebook Post all jobs requested');
            
            // Get API URL from config
            $apiUrl = config('services.facebook_scraper.api_url', 'http://localhost:55025/facebook-scraper');
            $apiUrl = rtrim($apiUrl, '/') . "/api/jobs";
            
            // Log API call
            Log::info('Getting all jobs via API', ['api_url' => $apiUrl]);
            
            try {
                // Call the API with reduced timeout
                $response = Http::timeout(3)->get($apiUrl);
                
                // Handle successful response
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('All jobs retrieved from API', [
                        'active_count' => count($data['active'] ?? []), 
                        'completed_count' => count($data['completed'] ?? [])
                    ]);
                    
                    return response()->json($data);
                }
                
                // Log API error
                Log::warning('Error getting all jobs from API', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            } catch (\Exception $e) {
                // Log API connection error
                Log::warning('Could not connect to Facebook Scraper API', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Nếu API không khả dụng, mô phỏng một phản hồi
            // Lấy các bài đăng gần đây từ cơ sở dữ liệu
            $recentPosts = FacebookPost::where('created_at', '>=', now()->subHours(24))
                                      ->orderBy('created_at', 'desc')
                                      ->take(20)
                                      ->get();
            
            // Tạo danh sách các job giả lập dựa trên bài viết
            $simulatedJobs = [];
            
            if ($recentPosts->isNotEmpty()) {
                foreach ($recentPosts->chunk(5) as $index => $chunk) {
                    $jobId = 'job_' . (time() - $index * 3600) . rand(1000, 9999);
                    $simulatedJobs[] = [
                        'job_id' => $jobId,
                        'status' => 'completed',
                        'url' => $chunk->first()->source_url,
                        'posts_count' => $chunk->count(),
                        'created_at' => $chunk->first()->created_at->format('Y-m-d H:i:s'),
                        'completed_at' => $chunk->first()->created_at->addMinutes(rand(1, 10))->format('Y-m-d H:i:s'),
                        'simulated' => true
                    ];
                }
            }
            
            // Tạo một phản hồi mô phỏng với danh sách các job
            $simulatedResponse = [
                'active' => [],
                'completed' => $simulatedJobs,
                'simulated' => true
            ];
            
            Log::info('Returning simulated jobs list', [
                'jobs_count' => count($simulatedJobs)
            ]);
            
            return response()->json($simulatedResponse);
            
        } catch (\Exception $e) {
            // Log exceptions
            Log::error('Get all jobs exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách jobs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the status of a facebook post.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'processed' => 'required|boolean',
        ]);

        $post = FacebookPost::findOrFail($id);
        $post->update(['processed' => $request->boolean('processed')]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái bài viết',
            'post' => $post
        ]);
    }

    /**
     * Remove the specified facebook post.
     */
    public function destroy($id)
    {
        $post = FacebookPost::findOrFail($id);
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bài viết thành công'
        ]);
    }
}
