<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KeywordRewrite;
use App\Models\RewrittenArticle;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class KeywordRewriteController extends Controller
{
    private $aiProcessPid = null;
    
    /**
     * Kiểm tra và khởi động dịch vụ AI nếu nó không hoạt động
     * 
     * @return bool Trả về true nếu dịch vụ đang hoạt động hoặc đã được khởi động thành công
     */
    private function ensureAIServiceRunning()
    {
        $aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:5003');
        $healthEndpoint = $aiServiceUrl . '/health';
        
        try {
            // Kiểm tra xem dịch vụ có đang chạy không
            $response = Http::timeout(2)->get($healthEndpoint);
            
            if ($response->successful()) {
                Log::info("AI service is already running");
                return true;
            }
        } catch (\Exception $e) {
            Log::warning("AI service health check failed: " . $e->getMessage());
        }
        
        // Dịch vụ không hoạt động, thử khởi động nó
        Log::info("Attempting to start AI service");
        
        try {
            // Lấy đường dẫn đến script khởi động
            $aiServiceFolder = base_path('../ai_service/keyword_rewrite');
            
            // Chọn script phù hợp với hệ điều hành
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                $startScript = $aiServiceFolder . '/start_service.bat';
                $command = "cmd /c start /min \"\" \"" . $startScript . "\"";
            } else {
                // Linux/Mac
                $startScript = $aiServiceFolder . '/start_service.sh';
                $command = "nohup \"" . $startScript . "\" > /dev/null 2>&1 &";
            }
            
            Log::info("Running command: " . $command);
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                pclose(popen($command, 'r'));
            } else {
                // Linux/Mac
                exec($command);
            }
            
            // Đợi dịch vụ khởi động
            $maxRetries = 10;
            $retryCount = 0;
            $serviceStarted = false;
            
            while ($retryCount < $maxRetries && !$serviceStarted) {
                try {
                    // Đợi một chút để dịch vụ khởi động
                    sleep(1);
                    $retryCount++;
                    
                    // Kiểm tra lại xem dịch vụ đã khởi động chưa
                    $response = Http::timeout(2)->get($healthEndpoint);
                    
                    if ($response->successful()) {
                        Log::info("AI service successfully started after {$retryCount} seconds");
                        $serviceStarted = true;
                        return true;
                    }
                } catch (\Exception $e) {
                    // Tiếp tục thử lại
                    Log::info("Waiting for AI service to start... ({$retryCount}/{$maxRetries})");
                }
            }
            
            if (!$serviceStarted) {
                Log::error("Failed to start AI service after {$maxRetries} attempts");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error starting AI service: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = KeywordRewrite::with('creator');
        
        // Apply search filters
        if ($request->has('search') && !empty($request->search)) {
            $query->where('keyword', 'like', '%' . $request->search . '%');
        }
        
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('created_at', $request->date);
        }
        
        $keywordRewrites = $query->latest()->paginate(10);
        
        return view('admin.keyword-rewrites.index', compact('keywordRewrites'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.keyword-rewrites.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
        ]);

        $keywordRewrite = KeywordRewrite::create([
            'keyword' => $request->keyword,
            'created_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // Đảm bảo dịch vụ AI đang chạy
        if (!$this->ensureAIServiceRunning()) {
            $keywordRewrite->update([
                'status' => 'failed',
                'error_message' => 'Không thể khởi động dịch vụ AI. Vui lòng kiểm tra lại cài đặt server.'
            ]);
            
            return redirect()->back()
                ->with('error', 'Không thể khởi động dịch vụ AI. Vui lòng liên hệ quản trị viên.')
                ->withInput();
        }

        // Send the keyword to the AI service for processing
        try {
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:5003');
            $endpoint = $aiServiceUrl . '/api/keyword_rewrite/process';
            
            Log::info("Attempting to connect to AI service at: " . $endpoint);
            
            $callbackUrl = env('BACKEND_URL', 'http://localhost:8000') . '/api/admin/keyword-rewrites/callback';
            Log::info("Using callback URL: " . $callbackUrl);
            
            $payload = [
                'keyword' => $request->keyword,
                'rewrite_id' => $keywordRewrite->id,
                'callback_url' => $callbackUrl,
            ];
            
            Log::info("Sending payload to AI service: ", $payload);
            
            // Thử phương thức 1: Sử dụng HTTP facade với timeout cao hơn và thêm options
            try {
                $response = Http::timeout(10)->post($endpoint, $payload);
                
                if ($response->successful()) {
                    $keywordRewrite->update(['status' => 'processing']);
                    Log::info("Successfully connected to AI service and started processing");
                    return redirect()->route('admin.keyword-rewrites.show', $keywordRewrite)
                        ->with('success', 'Yêu cầu tạo bài viết từ từ khóa đã được gửi đi. Hệ thống đang xử lý.');
                } else {
                    throw new \Exception("Received error response: " . $response->status() . " - " . $response->body());
                }
            } catch (\Exception $e) {
                Log::warning("First connection attempt failed: " . $e->getMessage());
                
                // Phương thức 2: Thử sử dụng cURL trực tiếp với options khác
                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                
                $result = curl_exec($ch);
                $error = curl_error($ch);
                $errorNo = curl_errno($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($result !== false && $httpCode >= 200 && $httpCode < 300) {
                    $keywordRewrite->update(['status' => 'processing']);
                    Log::info("Successfully connected to AI service using direct cURL");
                    return redirect()->route('admin.keyword-rewrites.show', $keywordRewrite)
                        ->with('success', 'Yêu cầu tạo bài viết từ từ khóa đã được gửi đi. Hệ thống đang xử lý.');
                } else {
                    throw new \Exception("cURL error $errorNo: $error, HTTP code: $httpCode");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to connect to AI service: " . $e->getMessage());
            
            // Thêm thông tin điều chỉnh cho người dùng
            $keywordRewrite->update([
                'status' => 'failed',
                'error_message' => 'Không thể kết nối đến dịch vụ AI: ' . $e->getMessage() . '. Vui lòng đảm bảo AI service đang chạy ở địa chỉ ' . $aiServiceUrl
            ]);
            
            return redirect()->back()
                ->with('error', 'Không thể kết nối đến dịch vụ AI. Đảm bảo bạn đã khởi động AI service (chạy file api.py trong thư mục ai_service/keyword_rewrite) và thử lại.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(KeywordRewrite $keywordRewrite)
    {
        return view('admin.keyword-rewrites.show', compact('keywordRewrite'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KeywordRewrite $keywordRewrite)
    {
        $keywordRewrite->delete();
        
        return redirect()->route('admin.keyword-rewrites.index')
            ->with('success', 'Bài viết từ từ khóa đã được xóa thành công.');
    }
    
    /**
     * Retry a failed keyword rewrite.
     */
    public function retry(KeywordRewrite $keywordRewrite)
    {
        if ($keywordRewrite->status !== 'failed') {
            return redirect()->back()
                ->with('error', 'Chỉ có thể thử lại cho các bài viết bị lỗi.');
        }
        
        // Đảm bảo dịch vụ AI đang chạy
        if (!$this->ensureAIServiceRunning()) {
            return redirect()->back()
                ->with('error', 'Không thể khởi động dịch vụ AI. Vui lòng liên hệ quản trị viên.');
        }
        
        try {
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:5003');
            $endpoint = $aiServiceUrl . '/api/keyword_rewrite/process';
            
            Log::info("Retry: Attempting to connect to AI service at: " . $endpoint);
            
            $callbackUrl = env('BACKEND_URL', 'http://localhost:8000') . '/api/admin/keyword-rewrites/callback';
            Log::info("Retry: Using callback URL: " . $callbackUrl);
            
            $payload = [
                'keyword' => $keywordRewrite->keyword,
                'rewrite_id' => $keywordRewrite->id,
                'callback_url' => $callbackUrl,
            ];
            
            Log::info("Retry: Sending payload to AI service: ", $payload);
            
            // Thử phương thức 1: Sử dụng HTTP facade với timeout cao hơn
            try {
                $response = Http::timeout(10)->post($endpoint, $payload);
                
                if ($response->successful()) {
                    $keywordRewrite->update(['status' => 'processing']);
                    Log::info("Retry: Successfully connected to AI service and started processing");
                    return redirect()->route('admin.keyword-rewrites.show', $keywordRewrite)
                        ->with('success', 'Yêu cầu tạo bài viết đã được gửi lại. Hệ thống đang xử lý.');
                } else {
                    throw new \Exception("Received error response: " . $response->status() . " - " . $response->body());
                }
            } catch (\Exception $e) {
                Log::warning("Retry: First connection attempt failed: " . $e->getMessage());
                
                // Phương thức 2: Thử sử dụng cURL trực tiếp
                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                
                $result = curl_exec($ch);
                $error = curl_error($ch);
                $errorNo = curl_errno($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($result !== false && $httpCode >= 200 && $httpCode < 300) {
                    $keywordRewrite->update(['status' => 'processing']);
                    Log::info("Retry: Successfully connected to AI service using direct cURL");
                    return redirect()->route('admin.keyword-rewrites.show', $keywordRewrite)
                        ->with('success', 'Yêu cầu tạo bài viết đã được gửi lại. Hệ thống đang xử lý.');
                } else {
                    throw new \Exception("cURL error $errorNo: $error, HTTP code: $httpCode");
                }
            }
        } catch (\Exception $e) {
            Log::error("Retry: Failed to connect to AI service: " . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Không thể kết nối đến dịch vụ AI. Đảm bảo bạn đã khởi động AI service (chạy file api.py trong thư mục ai_service/keyword_rewrite) và thử lại.');
        }
    }
    
    /**
     * Convert a completed keyword rewrite to an article.
     */
    public function convert(KeywordRewrite $keywordRewrite)
    {
        if ($keywordRewrite->status !== 'completed') {
            return redirect()->back()
                ->with('error', 'Chỉ có thể chuyển đổi các bài viết đã hoàn thành.');
        }
        
        if (empty($keywordRewrite->rewritten_content)) {
            return redirect()->back()
                ->with('error', 'Không thể chuyển đổi bài viết vì nội dung trống.');
        }
        
        try {
            // Find or create a general category
            $category = Category::firstOrCreate(
                ['slug' => 'tin-tuc'],
                ['name' => 'Tin tức', 'description' => 'Tin tức tổng hợp']
            );
            
            // Create a new rewritten article
            $rewrittenArticle = RewrittenArticle::create([
                'title' => $keywordRewrite->source_title ?? "Bài viết về {$keywordRewrite->keyword}",
                'slug' => Str::slug($keywordRewrite->source_title ?? "Bài viết về {$keywordRewrite->keyword}") . '-' . time(),
                'content' => $keywordRewrite->rewritten_content,
                'meta_title' => $keywordRewrite->source_title,
                'meta_description' => Str::limit(strip_tags($keywordRewrite->rewritten_content), 160),
                'user_id' => auth()->id(),
                'category_id' => $category->id,
                'status' => 'pending',
            ]);
            
            return redirect()->route('admin.rewritten-articles.edit', $rewrittenArticle->id)
                ->with('success', 'Bài viết đã được chuyển đổi thành công. Bạn có thể chỉnh sửa trước khi xuất bản.');
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Đã xảy ra lỗi khi chuyển đổi bài viết: ' . $e->getMessage());
        }
    }
    
    /**
     * Quick process a keyword in one step for immediate results
     */
    public function quickProcess(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
        ]);

        // Tạo mới keyword rewrite
        $keywordRewrite = KeywordRewrite::create([
            'keyword' => $request->keyword,
            'created_by' => auth()->id(),
            'status' => 'processing',
        ]);

        Log::info("Quick process: Started for keyword '{$request->keyword}'");

        // Đảm bảo dịch vụ AI đang chạy
        if (!$this->ensureAIServiceRunning()) {
            $keywordRewrite->update([
                'status' => 'failed',
                'error_message' => 'Không thể khởi động dịch vụ AI. Vui lòng kiểm tra lại cài đặt server.'
            ]);
            
            return redirect()->back()
                ->with('error', 'Không thể khởi động dịch vụ AI. Vui lòng liên hệ quản trị viên.')
                ->withInput();
        }

        // Gửi yêu cầu xử lý từ khóa đến AI service
        try {
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:5003');
            $endpoint = $aiServiceUrl . '/api/keyword_rewrite/process';
            
            $callbackUrl = env('BACKEND_URL', 'http://localhost:8000') . '/api/admin/keyword-rewrites/callback';
            
            $payload = [
                'keyword' => $request->keyword,
                'rewrite_id' => $keywordRewrite->id,
                'callback_url' => $callbackUrl,
            ];
            
            Log::info("Quick process: Sending request to AI service", $payload);
            
            // Gửi yêu cầu và bắt đầu theo dõi trạng thái
            $response = Http::timeout(10)->post($endpoint, $payload);
            
            if ($response->successful()) {
                // Tạo view đặc biệt với JavaScript tự động cập nhật trạng thái
                return view('admin.keyword-rewrites.quick-process', [
                    'keywordRewrite' => $keywordRewrite,
                    'keyword' => $request->keyword
                ]);
            } else {
                throw new \Exception("Received error response: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Quick process: Error connecting to AI service: " . $e->getMessage());
            
            $keywordRewrite->update([
                'status' => 'failed',
                'error_message' => 'Không thể kết nối đến dịch vụ AI: ' . $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Không thể kết nối đến dịch vụ AI. Vui lòng đảm bảo AI service đang chạy.')
                ->withInput();
        }
    }
    
    /**
     * Get current status of a keyword rewrite for AJAX updates
     */
    public function getStatus(KeywordRewrite $keywordRewrite)
    {
        // Refresh từ database để có dữ liệu mới nhất
        $keywordRewrite->refresh();
        
        return response()->json([
            'id' => $keywordRewrite->id,
            'status' => $keywordRewrite->status,
            'source_url' => $keywordRewrite->source_url,
            'source_title' => $keywordRewrite->source_title,
            'rewritten_content' => $keywordRewrite->rewritten_content ? 
                        substr($keywordRewrite->rewritten_content, 0, 200) . '...' : null,
            'error_message' => $keywordRewrite->error_message,
            'updated_at' => $keywordRewrite->updated_at->diffForHumans(),
            'redirect_url' => $keywordRewrite->status === 'completed' || $keywordRewrite->status === 'failed' ? 
                        route('admin.keyword-rewrites.show', $keywordRewrite) : null
        ]);
    }

    /**
     * Kiểm tra trạng thái của keyword rewrite thông qua AJAX
     */
    public function checkStatus(KeywordRewrite $keywordRewrite)
    {
        // Trả về thông tin trạng thái
        $message = '';
        
        // Nếu có thông báo từ session, ưu tiên lấy từ đó
        if (session()->has('keyword_rewrite_message_' . $keywordRewrite->id)) {
            $message = session('keyword_rewrite_message_' . $keywordRewrite->id);
        } else {
            // Tạo thông báo mặc định dựa trên trạng thái
            if ($keywordRewrite->status == 'completed') {
                $message = 'Bài viết đã được tạo thành công từ từ khóa "' . $keywordRewrite->keyword . '"';
            } elseif ($keywordRewrite->status == 'failed') {
                $message = 'Xử lý từ khóa "' . $keywordRewrite->keyword . '" thất bại: ' . $keywordRewrite->error_message;
            } else {
                $message = 'Đang xử lý từ khóa "' . $keywordRewrite->keyword . '"';
            }
        }
        
        return response()->json([
            'status' => $keywordRewrite->status,
            'message' => $message,
        ]);
    }
} 