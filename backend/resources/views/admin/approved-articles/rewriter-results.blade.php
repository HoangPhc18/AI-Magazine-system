@extends('layouts.admin')

@section('title', 'Kết quả Viết lại Bài viết - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Kết quả Viết lại Bài viết</h1>
            <div class="flex space-x-3">
                <a href="{{ route('admin.approved-articles.index') }}" class="flex items-center px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Quay lại Bài viết
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-md" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-md" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-purple-100 border-l-4 border-purple-500 text-purple-700 p-6 mb-6 rounded-lg shadow-md" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-purple-800">Yêu cầu của bạn đã được xử lý!</h3>
                    <p class="mt-2 text-sm text-purple-700">
                        Module rewriter đã được kích hoạt để viết lại bài viết từ dữ liệu gốc. Quá trình này có thể mất vài phút để hoàn thành.
                        Các bài viết được viết lại sẽ xuất hiện trong danh sách bài viết đã viết lại sau khi quá trình hoàn tất.
                    </p>
                </div>
            </div>
            <div class="mt-4 bg-white rounded-md p-4 border border-purple-200">
                <h4 class="font-semibold text-purple-800 mb-2">Thông tin tiến trình:</h4>
                <ul class="ml-5 list-disc text-sm text-purple-700 space-y-1">
                    <li>Đã kích hoạt module rewriter thành công</li>
                    <li>Đang xử lý các bài viết gốc từ cơ sở dữ liệu</li>
                    <li>Quá trình viết lại sử dụng AI Gemini 1.5 Flash</li>
                    <li>Bài viết được viết lại sẽ tự động lưu vào hệ thống</li>
                </ul>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Kết quả thực thi Rewriter</h2>
                <p class="mb-4 text-gray-600">Dưới đây là kết quả chi tiết của quá trình viết lại bài viết.</p>
                
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">Trạng thái:</span>
                        <span id="rewriter-status-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Đang kiểm tra...
                        </span>
                    </div>
                    <span id="last-updated" class="text-xs text-gray-500">Đang cập nhật...</span>
                </div>
                
                <div class="mt-4">
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <div class="mb-2 flex justify-between items-center">
                            <h3 class="font-medium text-gray-800">Log quá trình thực thi:</h3>
                            <span class="text-xs text-gray-500">Thời gian thực thi: {{ now()->format('H:i:s - d/m/Y') }}</span>
                        </div>
                        <div class="font-mono text-sm text-gray-800 whitespace-pre-wrap max-h-96 overflow-y-auto p-3 border border-gray-200 rounded bg-gray-50">
                            @if(count($output) > 0)
                                @foreach($output as $line)
                                    {{ $line }}<br>
                                @endforeach
                            @else
                                <p class="text-gray-500 italic">Không có thông tin output nào được ghi nhận.</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Lưu ý quan trọng</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Quá trình viết lại bài viết diễn ra trong nền và có thể mất 3-5 phút mỗi bài, tùy thuộc vào độ dài và nội dung.</p>
                                <p class="mt-1">Kết quả viết lại sẽ được lưu vào hệ thống trong tab "Bài viết đã viết lại" và sẽ cần được duyệt trước khi xuất bản.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-between items-center bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-1">Quay lại quản lý bài viết</h4>
                        <p class="text-xs text-gray-500">Trở về trang danh sách bài viết đã duyệt</p>
                    </div>
                    <a href="{{ route('admin.approved-articles.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                        </svg>
                        Quay lại danh sách bài viết
                    </a>
                </div>
                
                <div class="mt-4 flex justify-between items-center bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <div>
                        <h4 class="text-sm font-medium text-purple-700 mb-1">Chạy Module Rewriter</h4>
                        <p class="text-xs text-purple-500">Viết lại bài viết gốc sử dụng AI Gemini 1.5 Flash</p>
                    </div>
                    <form action="{{ route('admin.approved-articles.run-rewriter') }}" method="POST" class="inline" id="run-rewriter-form">
                        @csrf
                        <button type="submit" id="run-rewriter-btn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            <span id="btn-text">Chạy Module Rewriter</span>
                            <svg id="loading-icon" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thông báo hoàn thành -->
<div id="completion-toast" class="fixed bottom-5 right-5 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 scale-0 flex items-center z-50">
    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    <span>Viết lại bài viết đã hoàn thành!</span>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phần tử chứa log output
    const logContainer = document.querySelector('.font-mono.text-sm.text-gray-800.whitespace-pre-wrap');
    const statusBadge = document.getElementById('rewriter-status-badge');
    const lastUpdatedEl = document.getElementById('last-updated');
    const runRewriterForm = document.getElementById('run-rewriter-form');
    const runRewriterBtn = document.getElementById('run-rewriter-btn');
    const btnText = document.getElementById('btn-text');
    const loadingIcon = document.getElementById('loading-icon');
    const completionToast = document.getElementById('completion-toast');
    
    let isFirstLoad = true;
    let wasRunning = false;
    let hasShownSuccess = false;
    let isCompleted = false;
    
    // Kiểm tra nếu có thông báo thành công từ server
    @if(session('success'))
        setTimeout(() => {
            // Đặt trạng thái là hoàn thành
            isCompleted = true;
            if (statusBadge) {
                statusBadge.textContent = 'Đã hoàn thành';
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
            }
            // Kích hoạt ngay lập tức để không chờ interval
            updateStatus();
        }, 500);
    @endif
    
    // Sự kiện submit form
    if (runRewriterForm) {
        runRewriterForm.addEventListener('submit', function(e) {
            // Hiển thị icon loading và vô hiệu hóa nút
            if (loadingIcon) loadingIcon.classList.remove('hidden');
            if (btnText) btnText.textContent = 'Đang kích hoạt...';
            if (runRewriterBtn) runRewriterBtn.disabled = true;
            
            // Thay đổi status badge
            if (statusBadge) {
                statusBadge.textContent = 'Đang kích hoạt...';
                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
            }
            
            // Reset trạng thái hiển thị thông báo
            hasShownSuccess = false;
            isCompleted = false;
        });
    }
    
    // Hàm kiểm tra nếu output chứa thông tin thành công
    function checkOutputForSuccess(logOutput) {
        if (!logOutput || !Array.isArray(logOutput)) return false;
        
        // Các mẫu văn bản cho biết quá trình đã hoàn thành
        const successPatterns = [
            'successfully rewritten',
            'database connection closed',
            'summary: processed',
            'đã viết lại thành công',
            'successfully', 
            'summary',
            'hoàn thành',
            'thành công',
            'saved rewritten article'
        ];
        
        // Kiểm tra output có chứa bất kỳ mẫu thành công nào và là dòng gần cuối
        const combinedText = logOutput.slice(-10).join(' ').toLowerCase();
        return successPatterns.some(pattern => combinedText.includes(pattern));
    }
    
    // Hàm hiển thị toast thông báo hoàn thành
    function showCompletionToast(message) {
        if (completionToast) {
            const textElement = completionToast.querySelector('span');
            if (textElement) textElement.textContent = message;
            
            completionToast.classList.remove('scale-0');
            completionToast.classList.add('scale-100');
            
            setTimeout(() => {
                completionToast.classList.remove('scale-100');
                completionToast.classList.add('scale-0');
            }, 5000);
        }
    }
    
    // Hàm cập nhật trạng thái
    function updateStatus() {
        fetch('{{ route("admin.approved-articles.check-rewriter-status") }}')
            .then(response => response.json())
            .then(data => {
                // Cập nhật thời gian cập nhật cuối
                if (lastUpdatedEl) {
                    lastUpdatedEl.textContent = `Cập nhật lúc: ${data.last_updated}`;
                }
                
                // Cập nhật log output nếu có
                if (logContainer && data.log_output && data.log_output.length > 0) {
                    let logHtml = '';
                    data.log_output.forEach(line => {
                        logHtml += `${line}<br>`;
                    });
                    logContainer.innerHTML = logHtml;
                    
                    // Tự động cuộn xuống dưới
                    logContainer.scrollTop = logContainer.scrollHeight;
                }
                
                // Cập nhật trạng thái đang chạy
                if (statusBadge) {
                    if (data.is_running) {
                        statusBadge.textContent = 'Đang chạy';
                        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800';
                        wasRunning = true;
                        
                        // Đảm bảo nút vẫn hiển thị đang loading
                        if (loadingIcon) loadingIcon.classList.remove('hidden');
                        if (btnText) btnText.textContent = 'Đang chạy...';
                        if (runRewriterBtn) runRewriterBtn.disabled = true;
                    } 
                    else if (wasRunning || isCompleted) {
                        // Đã từng chạy hoặc đã đánh dấu hoàn thành
                        statusBadge.textContent = 'Đã hoàn thành';
                        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                        
                        // Trở lại trạng thái bình thường
                        if (loadingIcon) loadingIcon.classList.add('hidden');
                        if (btnText) btnText.textContent = 'Chạy Module Rewriter';
                        if (runRewriterBtn) runRewriterBtn.disabled = false;
                        
                        // Hiển thị thông báo hoàn thành nếu chưa hiển thị
                        if (!hasShownSuccess) {
                            showCompletionToast("Viết lại bài viết đã hoàn thành!");
                            hasShownSuccess = true;
                            isCompleted = true;
                        }
                    } 
                    else {
                        statusBadge.textContent = 'Sẵn sàng';
                        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
                    }
                }
                
                // Kiểm tra success pattern trong log
                if (!isCompleted && !data.is_running && data.log_output && checkOutputForSuccess(data.log_output)) {
                    if (statusBadge) {
                        statusBadge.textContent = 'Đã hoàn thành';
                        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                    }
                    
                    if (!hasShownSuccess) {
                        showCompletionToast("Viết lại bài viết đã hoàn thành!");
                        hasShownSuccess = true;
                        isCompleted = true;
                    }
                    
                    // Trở lại trạng thái bình thường
                    if (loadingIcon) loadingIcon.classList.add('hidden');
                    if (btnText) btnText.textContent = 'Chạy Module Rewriter';
                    if (runRewriterBtn) runRewriterBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error fetching status:', error);
                // Nếu có lỗi, cũng phải reset trạng thái của nút
                if (loadingIcon) loadingIcon.classList.add('hidden');
                if (btnText) btnText.textContent = 'Chạy Module Rewriter';
                if (runRewriterBtn) runRewriterBtn.disabled = false;
            });
    }
    
    // Khởi tạo gọi trạng thái ngay lập tức
    updateStatus();
    
    // Thiết lập interval để cập nhật trạng thái mỗi 3 giây
    const statusInterval = setInterval(updateStatus, 3000);
    
    // Dọn dẹp khi người dùng rời trang
    window.addEventListener('beforeunload', function() {
        clearInterval(statusInterval);
    });
});
</script>
@endpush 