@extends('layouts.admin')

@section('title', 'Kết quả Thu thập Bài viết - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Kết quả Thu thập Bài viết</h1>
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

        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-6 mb-6 rounded-lg shadow-md" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-green-800">Yêu cầu của bạn đã được xử lý thành công!</h3>
                    <p class="mt-2 text-sm text-green-700">
                        Module scraper đã được kích hoạt để thu thập bài viết mới từ các nguồn. Quá trình này có thể mất vài phút để hoàn thành.
                        Các bài viết mới thu thập sẽ xuất hiện trong danh sách bài viết đã duyệt sau khi quá trình hoàn tất.
                    </p>
                </div>
            </div>
            <div class="mt-4 bg-white rounded-md p-4 border border-green-200">
                <h4 class="font-semibold text-green-800 mb-2">Thông tin tiến trình:</h4>
                <ul class="ml-5 list-disc text-sm text-green-700 space-y-1">
                    <li>Đã kích hoạt module scraper thành công</li>
                    <li>Đang tìm kiếm bài viết từ các nguồn khác nhau</li>
                    <li>Bài viết sẽ được tự động xử lý và import vào hệ thống</li>
                    <li>Bạn có thể quay lại danh sách bài viết và làm mới sau vài phút</li>
                </ul>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Kết quả thực thi Scraper</h2>
                <p class="mb-4 text-gray-600">Dưới đây là kết quả chi tiết của quá trình thu thập bài viết từ scraper.</p>
                
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">Trạng thái:</span>
                        <span id="scraper-status-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
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
                                <p>Quá trình thu thập bài viết diễn ra trong nền và có thể mất 5-10 phút để hoàn thành hoàn toàn, tùy thuộc vào số lượng bài viết và nguồn.</p>
                                <p class="mt-1">Các bài viết mới sẽ được tự động import vào hệ thống và sẽ xuất hiện trong danh sách bài viết đã duyệt.</p>
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
                
                <div class="mt-4 flex justify-between items-center bg-green-50 p-4 rounded-lg border border-green-200">
                    <div>
                        <h4 class="text-sm font-medium text-green-700 mb-1">Chạy lại Scraper</h4>
                        <p class="text-xs text-green-500">Thu thập thêm bài viết mới từ các nguồn</p>
                    </div>
                    <form action="{{ route('admin.approved-articles.run-scraper') }}" method="POST" class="inline" id="run-scraper-form">
                        @csrf
                        <button type="submit" id="run-scraper-btn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span id="btn-text">Chạy lại Scraper</span>
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
<div id="completion-toast" class="fixed bottom-5 right-5 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg transform transition-transform duration-300 scale-0 flex items-center">
    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    <span>Scraper đã hoàn thành!</span>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phần tử chứa log output
    const logContainer = document.querySelector('.font-mono.text-sm.text-gray-800.whitespace-pre-wrap');
    const statusBadge = document.getElementById('scraper-status-badge');
    const lastUpdatedEl = document.getElementById('last-updated');
    const runScraperForm = document.getElementById('run-scraper-form');
    const runScraperBtn = document.getElementById('run-scraper-btn');
    const btnText = document.getElementById('btn-text');
    const loadingIcon = document.getElementById('loading-icon');
    const completionToast = document.getElementById('completion-toast');
    
    let isFirstLoad = true;
    let wasRunning = false;
    let hasShownSuccess = false;
    
    // Sự kiện submit form
    if (runScraperForm) {
        runScraperForm.addEventListener('submit', function(e) {
            // Hiển thị icon loading
            if (loadingIcon) loadingIcon.classList.remove('hidden');
            if (btnText) btnText.textContent = 'Đang kích hoạt...';
            if (runScraperBtn) runScraperBtn.disabled = true;
            // Reset trạng thái hiển thị thông báo
            hasShownSuccess = false;
        });
    }
    
    // Hàm kiểm tra nếu output chứa thông tin thành công
    function checkOutputForSuccess(logOutput) {
        if (!logOutput || !Array.isArray(logOutput)) return false;
        
        // Các mẫu văn bản cho biết quá trình đã hoàn thành
        const successPatterns = [
            'import thành công',
            'đã lưu vào database',
            'hoàn thành',
            'thành công',
            'import:',
            'imported successfully'
        ];
        
        // Kiểm tra output có chứa bất kỳ mẫu thành công nào
        const combinedText = logOutput.join(' ').toLowerCase();
        return successPatterns.some(pattern => combinedText.includes(pattern));
    }
    
    // Hàm cập nhật trạng thái
    function updateStatus() {
        fetch('{{ route("admin.approved-articles.check-scraper-status") }}')
            .then(response => response.json())
            .then(data => {
                // Cập nhật trạng thái đang chạy
                if (statusBadge) {
                    if (data.is_running) {
                        statusBadge.textContent = 'Đang chạy';
                        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                        wasRunning = true;
                        
                        // Đảm bảo nút vẫn hiển thị đang loading
                        if (loadingIcon) loadingIcon.classList.remove('hidden');
                        if (btnText) btnText.textContent = 'Đang chạy...';
                        if (runScraperBtn) runScraperBtn.disabled = true;
                        
                        // Kiểm tra output có thông báo hoàn thành hay không
                        if (!hasShownSuccess && data.log_output && checkOutputForSuccess(data.log_output)) {
                            showCompletionToast("Import dữ liệu đã hoàn thành!");
                            hasShownSuccess = true;
                            
                            // Cho phép người dùng click lại nút sau khi đã có thông báo thành công
                            if (loadingIcon) loadingIcon.classList.add('hidden');
                            if (btnText) btnText.textContent = 'Chạy lại Scraper';
                            if (runScraperBtn) runScraperBtn.disabled = false;
                            
                            // Cập nhật trạng thái
                            if (statusBadge) {
                                statusBadge.textContent = 'Đã hoàn thành';
                                statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
                            }
                        }
                    } else {
                        statusBadge.textContent = 'Đã hoàn thành';
                        statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
                        
                        // Nếu trước đó đang chạy và bây giờ đã hoàn thành, hiển thị thông báo
                        if (wasRunning && !hasShownSuccess) {
                            showCompletionToast();
                            hasShownSuccess = true;
                        }
                        
                        // Khôi phục trạng thái nút
                        if (loadingIcon) loadingIcon.classList.add('hidden');
                        if (btnText) btnText.textContent = 'Chạy lại Scraper';
                        if (runScraperBtn) runScraperBtn.disabled = false;
                    }
                }
                
                // Cập nhật log output nếu có
                if (logContainer && data.log_output && data.log_output.length > 0) {
                    // Chỉ thay thế nội dung nếu không phải lần đầu tiên
                    if (!isFirstLoad) {
                        logContainer.innerHTML = '';
                        data.log_output.forEach(line => {
                            const logLine = document.createElement('div');
                            logLine.textContent = line;
                            logContainer.appendChild(logLine);
                        });
                    }
                    isFirstLoad = false;
                }
                
                // Cập nhật thời gian
                if (lastUpdatedEl) {
                    lastUpdatedEl.textContent = 'Cập nhật lần cuối: ' + data.last_updated;
                }
                
                // Lập lịch cập nhật tiếp theo
                const updateInterval = data.is_running && !hasShownSuccess ? 5000 : 30000; // 5 giây nếu đang chạy, 30 giây nếu đã hoàn thành
                setTimeout(updateStatus, updateInterval);
            })
            .catch(error => {
                console.error('Lỗi khi kiểm tra trạng thái:', error);
                setTimeout(updateStatus, 30000); // Thử lại sau 30 giây nếu có lỗi
            });
    }
    
    // Hiển thị thông báo hoàn thành
    function showCompletionToast(message = "Scraper đã hoàn thành!") {
        if (completionToast) {
            // Cập nhật thông báo
            const messageElement = completionToast.querySelector('span');
            if (messageElement) {
                messageElement.textContent = message;
            }
            
            // Hiển thị toast
            completionToast.classList.remove('scale-0');
            completionToast.classList.add('scale-100');
            
            // Tự động ẩn sau 5 giây
            setTimeout(() => {
                completionToast.classList.remove('scale-100');
                completionToast.classList.add('scale-0');
            }, 5000);
        }
    }
    
    // Bắt đầu kiểm tra trạng thái
    updateStatus();
});
</script>
@endpush 