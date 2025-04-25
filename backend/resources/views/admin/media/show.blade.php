@extends('layouts.admin')

@section('title', 'Chi tiết media - Hệ thống Magazine AI')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Chi tiết media
                </h3>
                <a href="{{ route('admin.media.index') }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="mr-2 -ml-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Quay lại
                </a>
            </div>
            
            <div class="border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                    <div class="col-span-1 flex justify-center">
                        <div class="max-w-full rounded overflow-hidden shadow">
                            @if($media->is_image)
                                <img src="{{ $media->url }}" alt="{{ $media->name }}" class="w-full h-auto">
                            @else
                                <div class="bg-gray-100 rounded p-8 flex items-center justify-center">
                                    <svg class="h-20 w-20 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="col-span-1 md:col-span-2">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Tên</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $media->name }}</dd>
                            </div>
                            
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Tên file</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $media->file_name }}</dd>
                            </div>
                            
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Loại</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $media->is_image ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $media->is_image ? 'Hình ảnh' : 'Tài liệu' }}
                                    </span>
                                </dd>
                            </div>
                            
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Kích thước</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ number_format($media->size / 1024, 2) }} KB</dd>
                            </div>
                            
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Mime Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $media->mime_type }}</dd>
                            </div>
                            
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Người tải lên</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $media->user->name ?? 'N/A' }}</dd>
                            </div>
                            
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Ngày tạo</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $media->created_at ? $media->created_at->format('d/m/Y H:i:s') : 'N/A' }}</dd>
                            </div>
                            
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Cập nhật lần cuối</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $media->updated_at ? $media->updated_at->format('d/m/Y H:i:s') : 'N/A' }}</dd>
                            </div>
                            
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Đường dẫn</dt>
                                <dd class="mt-1 text-sm text-gray-900 break-all">
                                    <a href="{{ $media->url }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        {{ $media->url }}
                                    </a>
                                </dd>
                            </div>
                        </dl>
                        
                        <div class="mt-6 flex space-x-3">
                            <a href="{{ $media->url }}" target="_blank" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Mở trong tab mới
                            </a>
                            
                            <button type="button" onclick="copyToClipboard('{{ $media->url }}')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="mr-2 -ml-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Sao chép đường dẫn
                            </button>
                            
                            <form action="{{ route('admin.media.destroy', $media->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa media này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="notification" class="fixed bottom-4 right-4 px-4 py-2 bg-green-500 text-white rounded shadow-lg transform transition-transform duration-300 translate-y-20 opacity-0">
    Đã sao chép đường dẫn vào clipboard
</div>

@push('scripts')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Show notification
            const notification = document.getElementById('notification');
            notification.classList.remove('translate-y-20', 'opacity-0');
            notification.classList.add('translate-y-0', 'opacity-100');
            
            // Hide notification after 2 seconds
            setTimeout(() => {
                notification.classList.remove('translate-y-0', 'opacity-100');
                notification.classList.add('translate-y-20', 'opacity-0');
            }, 2000);
        });
    }
</script>
@endpush
@endsection 