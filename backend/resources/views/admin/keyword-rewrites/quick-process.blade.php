@extends('layouts.admin')

@section('title', 'Xử lý nhanh từ khóa: ' . $keyword)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.keyword-rewrites.index') }}" class="text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">Đang xử lý từ khóa: <span class="text-blue-600">{{ $keyword }}</span></h1>
        
        <div class="mb-6">
            <div class="flex items-center">
                <div id="status-badge" class="px-3 py-1 rounded-full text-white bg-yellow-500 text-sm">
                    Đang xử lý
                </div>
                <div id="timer" class="ml-4 text-gray-500">00:00</div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-2">Trạng thái hiện tại</h3>
                <div id="progress-steps" class="relative">
                    <div class="flex items-center mb-4">
                        <div id="step1-icon" class="w-8 h-8 flex items-center justify-center bg-blue-500 text-white rounded-full">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium">Khởi tạo yêu cầu</p>
                            <p id="step1-details" class="text-sm text-gray-500">Đã gửi từ khóa: {{ $keyword }}</p>
                        </div>
                    </div>
                    
                    <div id="line1" class="absolute left-4 top-8 w-0.5 h-10 bg-gray-300"></div>
                    
                    <div class="flex items-center mb-4">
                        <div id="step2-icon" class="w-8 h-8 flex items-center justify-center bg-gray-300 text-white rounded-full">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium">Tìm kiếm bài viết</p>
                            <p id="step2-details" class="text-sm text-gray-500">Đang tìm kiếm bài viết từ từ khóa</p>
                        </div>
                    </div>
                    
                    <div id="line2" class="absolute left-4 top-28 w-0.5 h-10 bg-gray-300"></div>
                    
                    <div class="flex items-center mb-4">
                        <div id="step3-icon" class="w-8 h-8 flex items-center justify-center bg-gray-300 text-white rounded-full">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium">Trích xuất nội dung</p>
                            <p id="step3-details" class="text-sm text-gray-500">Chờ trích xuất nội dung từ URL nguồn</p>
                        </div>
                    </div>
                    
                    <div id="line3" class="absolute left-4 top-48 w-0.5 h-10 bg-gray-300"></div>
                    
                    <div class="flex items-center">
                        <div id="step4-icon" class="w-8 h-8 flex items-center justify-center bg-gray-300 text-white rounded-full">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium">Viết lại nội dung</p>
                            <p id="step4-details" class="text-sm text-gray-500">Chờ AI viết lại nội dung</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="result-container" class="hidden bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-2">Kết quả</h3>
                <div class="space-y-3">
                    <div id="source-url-container" class="hidden">
                        <p class="font-medium">URL nguồn:</p>
                        <a id="source-url" href="#" target="_blank" class="text-blue-500 hover:underline break-all"></a>
                    </div>
                    
                    <div id="source-title-container" class="hidden">
                        <p class="font-medium">Tiêu đề bài viết:</p>
                        <p id="source-title" class="text-gray-700"></p>
                    </div>
                    
                    <div id="preview-container" class="hidden">
                        <p class="font-medium">Xem trước nội dung:</p>
                        <div id="content-preview" class="mt-2 p-3 bg-white rounded border border-gray-200"></div>
                    </div>
                    
                    <div id="error-container" class="hidden">
                        <p class="font-medium text-red-600">Lỗi:</p>
                        <div id="error-message" class="mt-2 p-3 bg-red-50 text-red-700 rounded border border-red-200"></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-center mt-8">
                <a id="view-details-btn" href="#" class="hidden px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Xem chi tiết bài viết
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const keywordRewriteId = {{ $keywordRewrite->id }};
        let intervalId;
        let startTime = new Date();
        let completedOrFailed = false;
        
        // Khởi tạo timer
        intervalId = setInterval(updateTimer, 1000);
        updateTimer();
        
        // Khởi tạo kiểm tra trạng thái
        checkStatus();
        
        function updateTimer() {
            const now = new Date();
            const elapsedTime = Math.floor((now - startTime) / 1000);
            const minutes = Math.floor(elapsedTime / 60).toString().padStart(2, '0');
            const seconds = (elapsedTime % 60).toString().padStart(2, '0');
            document.getElementById('timer').textContent = `${minutes}:${seconds}`;
        }
        
        function checkStatus() {
            if (completedOrFailed) return;
            
            fetch(`/admin/keyword-rewrites/${keywordRewriteId}/status`)
                .then(response => response.json())
                .then(data => {
                    updateUI(data);
                    
                    if (data.status === 'completed' || data.status === 'failed') {
                        completedOrFailed = true;
                        clearInterval(intervalId);
                        
                        // Hiển thị nút xem chi tiết
                        const viewDetailsBtn = document.getElementById('view-details-btn');
                        viewDetailsBtn.href = data.redirect_url;
                        viewDetailsBtn.classList.remove('hidden');
                        
                        // Hiển thị thông báo
                        if (data.status === 'completed') {
                            Swal.fire({
                                title: 'Hoàn thành!',
                                text: 'Bài viết đã được tạo thành công từ từ khóa.',
                                icon: 'success',
                                confirmButtonText: 'Xem chi tiết',
                                showCancelButton: true,
                                cancelButtonText: 'Ở lại trang này'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = data.redirect_url;
                                }
                            });
                        }
                    } else {
                        // Tiếp tục kiểm tra sau 2 giây
                        setTimeout(checkStatus, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                    setTimeout(checkStatus, 5000);  // Thử lại sau 5 giây nếu có lỗi
                });
        }
        
        function updateUI(data) {
            // Cập nhật badge trạng thái
            const statusBadge = document.getElementById('status-badge');
            statusBadge.textContent = getStatusText(data.status);
            statusBadge.className = 'px-3 py-1 rounded-full text-white text-sm ' + getStatusColor(data.status);
            
            // Cập nhật các bước xử lý
            updateProgressSteps(data);
            
            // Hiển thị kết quả nếu có
            updateResultsSection(data);
        }
        
        function getStatusText(status) {
            switch(status) {
                case 'pending': return 'Đang chờ xử lý';
                case 'processing': return 'Đang xử lý';
                case 'completed': return 'Hoàn thành';
                case 'failed': return 'Thất bại';
                default: return 'Không xác định';
            }
        }
        
        function getStatusColor(status) {
            switch(status) {
                case 'pending': return 'bg-gray-500';
                case 'processing': return 'bg-yellow-500';
                case 'completed': return 'bg-green-500';
                case 'failed': return 'bg-red-500';
                default: return 'bg-gray-500';
            }
        }
        
        function updateProgressSteps(data) {
            // Bước 1: Khởi tạo yêu cầu - luôn hoàn thành
            document.getElementById('step1-icon').className = 'w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-full';
            document.getElementById('line1').className = 'absolute left-4 top-8 w-0.5 h-10 bg-green-500';
            
            // Bước 2: Tìm kiếm bài viết
            if (data.source_url) {
                // Đã tìm thấy URL
                document.getElementById('step2-icon').className = 'w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-full';
                document.getElementById('line2').className = 'absolute left-4 top-28 w-0.5 h-10 bg-green-500';
                document.getElementById('step2-details').textContent = 'Đã tìm thấy bài viết nguồn';
            } else if (data.status === 'failed') {
                // Thất bại khi tìm URL
                document.getElementById('step2-icon').className = 'w-8 h-8 flex items-center justify-center bg-red-500 text-white rounded-full';
                document.getElementById('step2-details').textContent = 'Không tìm thấy bài viết nào';
            } else if (data.status === 'processing') {
                // Đang xử lý
                document.getElementById('step2-icon').className = 'w-8 h-8 flex items-center justify-center bg-yellow-500 text-white rounded-full';
                document.getElementById('step2-details').textContent = 'Đang tìm kiếm bài viết từ từ khóa';
            }
            
            // Bước 3: Trích xuất nội dung
            if (data.source_title) {
                // Đã trích xuất nội dung
                document.getElementById('step3-icon').className = 'w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-full';
                document.getElementById('line3').className = 'absolute left-4 top-48 w-0.5 h-10 bg-green-500';
                document.getElementById('step3-details').textContent = 'Đã trích xuất nội dung thành công';
            } else if (data.source_url && (data.status === 'failed' || data.status === 'processing')) {
                // Có URL nhưng chưa có tiêu đề (đang xử lý hoặc lỗi)
                const iconClass = data.status === 'failed' ? 'bg-red-500' : 'bg-yellow-500';
                document.getElementById('step3-icon').className = `w-8 h-8 flex items-center justify-center ${iconClass} text-white rounded-full`;
                document.getElementById('step3-details').textContent = data.status === 'failed' ? 'Không thể trích xuất nội dung' : 'Đang trích xuất nội dung';
            }
            
            // Bước 4: Viết lại nội dung
            if (data.rewritten_content) {
                // Đã có nội dung viết lại
                document.getElementById('step4-icon').className = 'w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-full';
                document.getElementById('step4-details').textContent = 'Đã viết lại nội dung thành công';
            } else if (data.source_title && (data.status === 'failed' || data.status === 'processing')) {
                // Có tiêu đề nhưng chưa có nội dung viết lại
                const iconClass = data.status === 'failed' ? 'bg-red-500' : 'bg-yellow-500';
                document.getElementById('step4-icon').className = `w-8 h-8 flex items-center justify-center ${iconClass} text-white rounded-full`;
                document.getElementById('step4-details').textContent = data.status === 'failed' ? 'Không thể viết lại nội dung' : 'Đang viết lại nội dung';
            }
        }
        
        function updateResultsSection(data) {
            const resultContainer = document.getElementById('result-container');
            
            if (data.source_url || data.source_title || data.rewritten_content || data.error_message) {
                resultContainer.classList.remove('hidden');
                
                // Cập nhật URL nguồn
                if (data.source_url) {
                    document.getElementById('source-url-container').classList.remove('hidden');
                    const sourceUrlElement = document.getElementById('source-url');
                    sourceUrlElement.href = data.source_url;
                    sourceUrlElement.textContent = data.source_url;
                }
                
                // Cập nhật tiêu đề
                if (data.source_title) {
                    document.getElementById('source-title-container').classList.remove('hidden');
                    document.getElementById('source-title').textContent = data.source_title;
                }
                
                // Cập nhật xem trước nội dung
                if (data.rewritten_content) {
                    document.getElementById('preview-container').classList.remove('hidden');
                    document.getElementById('content-preview').textContent = data.rewritten_content;
                }
                
                // Hiển thị lỗi nếu có
                if (data.error_message) {
                    document.getElementById('error-container').classList.remove('hidden');
                    document.getElementById('error-message').textContent = data.error_message;
                }
            }
        }
    });
</script>
@endsection 