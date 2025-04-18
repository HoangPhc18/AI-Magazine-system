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

            // Tạo một job ID giả lập
            $jobId = 'job_' . time() . rand(1000, 9999);
            
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
            Log::info('Running scraper command', ['command' => $command]);
            
            try {
                // Chạy script Python không đồng bộ (không chờ kết quả)
                $process = Process::timeout(1)->start($command);
                
                Log::info('Process started successfully', ['job_id' => $jobId]);
                
                // Trả về thành công ngay lập tức, không chờ kết quả
                return response()->json([
                    'success' => true,
                    'message' => 'Đã bắt đầu thu thập bài viết',
                    'job_id' => $jobId
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
            // Get API URL from config
            $apiUrl = config('services.facebook_scraper.api_url', 'http://localhost:5000');
            $apiUrl = rtrim($apiUrl, '/') . "/api/jobs/{$jobId}";
            
            // Log API call
            Log::info('Checking job status', ['job_id' => $jobId, 'api_url' => $apiUrl]);
            
            // Call the API
            $response = Http::timeout(10)->get($apiUrl);
            
            // Handle successful response
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Job status retrieved', ['job_id' => $jobId, 'status' => $data]);
                
                return response()->json($data);
            }
            
            // Handle unsuccessful response
            Log::error('Error getting job status', [
                'job_id' => $jobId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy trạng thái job: ' . $response->body(),
            ], $response->status());
            
        } catch (\Exception $e) {
            // Log exceptions
            Log::error('Job status exception', [
                'job_id' => $jobId,
                'message' => $e->getMessage()
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
            // Get API URL from config
            $apiUrl = config('services.facebook_scraper.api_url', 'http://localhost:5000');
            $apiUrl = rtrim($apiUrl, '/') . "/api/jobs";
            
            // Log API call
            Log::info('Getting all jobs', ['api_url' => $apiUrl]);
            
            // Call the API
            $response = Http::timeout(10)->get($apiUrl);
            
            // Handle successful response
            if ($response->successful()) {
                $data = $response->json();
                Log::info('All jobs retrieved', ['count' => count($data['active'] ?? []) + count($data['completed'] ?? [])]);
                
                return response()->json($data);
            }
            
            // Handle unsuccessful response
            Log::error('Error getting all jobs', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách jobs: ' . $response->body(),
            ], $response->status());
            
        } catch (\Exception $e) {
            // Log exceptions
            Log::error('Get all jobs exception', [
                'message' => $e->getMessage()
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
