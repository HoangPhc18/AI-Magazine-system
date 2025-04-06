@extends('layouts.admin')

@section('title', 'Chi tiết bài viết từ từ khóa - Magazine AI System')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page header -->
    <div class="mb-8">
        <div class="flex items-center">
            <a href="{{ route('admin.keyword-rewrites.index') }}" class="mr-2 text-primary-600 hover:text-primary-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Chi tiết bài viết từ từ khóa</h1>
        </div>
        <p class="mt-1 text-sm text-gray-500">Xem kết quả viết lại từ từ khóa: <span class="font-semibold">{{ $keywordRewrite->keyword }}</span></p>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Status and Actions Bar -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-4 sm:px-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <div class="flex items-center">
                    <h2 class="text-lg font-medium text-gray-900">Trạng thái:</h2>
                    <div class="ml-2">
                        @if($keywordRewrite->status == 'pending')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Đang chờ
                            </span>
                        @elseif($keywordRewrite->status == 'processing')
                            <span id="status-badge" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                Đang xử lý
                            </span>
                        @elseif($keywordRewrite->status == 'completed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Hoàn thành
                            </span>
                        @elseif($keywordRewrite->status == 'failed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Thất bại
                            </span>
                        @endif
                    </div>
                </div>
                <p class="mt-1 text-sm text-gray-500">Tạo bởi: {{ $keywordRewrite->creator->name ?? 'N/A' }} | Thời gian tạo: {{ $keywordRewrite->created_at->format('d/m/Y H:i') }}</p>
            </div>
            
            <div class="mt-4 sm:mt-0 flex space-x-2">
                @if($keywordRewrite->status == 'completed')
                <a href="{{ route('admin.keyword-rewrites.convert', $keywordRewrite->id) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-150">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    Chuyển thành bài viết
                </a>
                @elseif($keywordRewrite->status == 'failed')
                <form action="{{ route('admin.keyword-rewrites.retry', $keywordRewrite->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Thử lại
                    </button>
                </form>
                @endif
                
                <form action="{{ route('admin.keyword-rewrites.destroy', $keywordRewrite->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-150">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Xóa
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Area -->
    <div id="notification-area" class="hidden mb-6"></div>

    @if($keywordRewrite->status == 'processing')
    <!-- Processing Status -->
    <div id="processing-status" class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-5 sm:p-6 text-center">
            <svg class="inline-block animate-spin h-10 w-10 text-primary-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Hệ thống đang xử lý yêu cầu của bạn</h3>
            <p class="mt-1 text-sm text-gray-500">Quá trình này có thể mất từ 30 giây đến vài phút tùy thuộc vào độ dài bài viết. Trang sẽ tự động cập nhật khi hoàn thành.</p>
            <button onClick="window.location.reload();" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-150">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Tải lại trang
            </button>
        </div>
    </div>
    @elseif($keywordRewrite->status == 'failed')
    <!-- Error Message -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-red-800">Đã xảy ra lỗi</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ $keywordRewrite->error_message ?? 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @elseif($keywordRewrite->status == 'completed')
    <!-- Main Content -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-4 py-5 sm:p-6">
            <!-- Source Information -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-2">Thông tin nguồn</h2>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Nguồn bài viết</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ $keywordRewrite->source_url }}" target="_blank" class="text-primary-600 hover:text-primary-900 hover:underline">
                                {{ $keywordRewrite->source_url }}
                                <svg class="inline-block ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Tiêu đề gốc</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $keywordRewrite->source_title }}</dd>
                    </div>
                </dl>
            </div>
            
            <!-- Source Content -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-medium text-gray-900">Nội dung gốc</h2>
                    <button type="button" id="toggleSourceBtn" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="mr-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        Hiện/Ẩn
                    </button>
                </div>
                <div id="sourceContent" class="mt-2 p-4 bg-gray-50 rounded-lg border border-gray-200 text-gray-700 text-sm whitespace-pre-line prose prose-sm max-w-none overflow-auto max-h-96 hidden">
                    {{ $keywordRewrite->source_content }}
                </div>
            </div>
            
            <!-- Rewritten Content -->
            <div>
                <h2 class="text-lg font-medium text-gray-900 mb-2">Nội dung viết lại</h2>
                <div class="mt-2 p-4 bg-green-50 rounded-lg border border-green-200 text-gray-700 text-sm whitespace-pre-line prose prose-sm max-w-none">
                    <h3 class="text-xl font-bold mb-4">{{ $keywordRewrite->source_title }}</h3>
                    {{ $keywordRewrite->rewritten_content }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($keywordRewrite->status == 'completed')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleSourceBtn = document.getElementById('toggleSourceBtn');
        const sourceContent = document.getElementById('sourceContent');
        
        toggleSourceBtn.addEventListener('click', function() {
            sourceContent.classList.toggle('hidden');
        });
    });
</script>
@endif

@if($keywordRewrite->status == 'processing')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Kiểm tra trạng thái bài viết mỗi 5 giây
        const rewriteId = {{ $keywordRewrite->id }};
        const checkStatusInterval = setInterval(checkStatus, 5000);
        
        function checkStatus() {
            fetch(`/admin/keyword-rewrites/${rewriteId}/check-status`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'processing') {
                    // Nếu trạng thái đã thay đổi, hiển thị thông báo và tải lại trang sau 2 giây
                    clearInterval(checkStatusInterval);
                    
                    // Hiển thị thông báo
                    const notificationArea = document.getElementById('notification-area');
                    notificationArea.classList.remove('hidden');
                    
                    // Thay đổi màu sắc tùy thuộc vào trạng thái
                    const bgColor = data.status === 'completed' ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500';
                    const textColor = data.status === 'completed' ? 'text-green-800' : 'text-red-800';
                    const iconColor = data.status === 'completed' ? 'text-green-500' : 'text-red-500';
                    const iconPath = data.status === 'completed' 
                        ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />'
                        : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />';
                    
                    // Tạo nội dung thông báo
                    notificationArea.innerHTML = `
                        <div class="mb-6 ${bgColor} border-l-4 p-4 rounded shadow-sm">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 ${iconColor}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        ${iconPath}
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm ${textColor}">${data.message}</p>
                                    <div class="mt-2">
                                        <button id="view-result-btn" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-150">
                                            Xem kết quả
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Hiển thị kết quả khi nhấn nút
                    document.getElementById('view-result-btn').addEventListener('click', function() {
                        window.location.reload();
                    });
                    
                    // Ẩn phần hiển thị trạng thái đang xử lý
                    document.getElementById('processing-status').classList.add('hidden');
                    
                    // Cập nhật badge trạng thái
                    const statusBadge = document.getElementById('status-badge');
                    if (statusBadge) {
                        statusBadge.className = data.status === 'completed' 
                            ? 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800'
                            : 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
                        statusBadge.textContent = data.status === 'completed' ? 'Hoàn thành' : 'Thất bại';
                    }
                }
            })
            .catch(error => {
                console.error('Error checking status:', error);
            });
        }
    });
</script>
@endif
@endsection 