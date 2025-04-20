@extends('layouts.admin')

@section('title', 'Chi tiết bài viết Facebook - Magazine AI System')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <div class="flex items-center">
                <a href="{{ route('admin.facebook-posts.index') }}" class="mr-2 text-primary-600 hover:text-primary-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Chi tiết bài viết Facebook</h1>
            </div>
            <p class="mt-1 text-sm text-gray-500">ID: {{ $facebookPost->id }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            @if(!$facebookPost->processed)
                <form action="{{ route('admin.facebook-posts.rewrite', $facebookPost) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        Viết lại bài viết
                    </button>
                </form>
                <form action="{{ route('admin.facebook-posts.mark-processed', $facebookPost) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Đánh dấu đã xử lý
                    </button>
                </form>
            @else
                <form action="{{ route('admin.facebook-posts.mark-unprocessed', $facebookPost) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Đánh dấu chưa xử lý
                    </button>
                </form>
            @endif
            
            <form action="{{ route('admin.facebook-posts.destroy', $facebookPost) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-red-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Xóa bài viết
                </button>
            </form>
        </div>
    </div>

    <!-- Notifications -->
    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif
    
    @if (session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Post details -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Info panel -->
        <div class="col-span-1 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Thông tin bài viết</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <h4 class="text-xs font-medium text-gray-500 uppercase">Trạng thái</h4>
                    @if($facebookPost->processed)
                        <div class="mt-1 flex items-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Đã xử lý
                            </span>
                        </div>
                    @else
                        <div class="mt-1 flex items-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Chưa xử lý
                            </span>
                        </div>
                    @endif
                </div>
                
                <div>
                    <h4 class="text-xs font-medium text-gray-500 uppercase">Nguồn</h4>
                    <div class="mt-1">
                        <p class="text-sm font-medium text-gray-900">{{ $facebookPost->page_or_group_name ?: 'Không xác định' }}</p>
                        <a href="{{ $facebookPost->source_url }}" target="_blank" class="mt-1 inline-flex items-center text-xs text-blue-600 hover:text-blue-900">
                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path>
                                <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"></path>
                            </svg>
                            Mở liên kết
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-xs font-medium text-gray-500 uppercase">Ngày thu thập</h4>
                    <p class="mt-1 text-sm text-gray-900">{{ $facebookPost->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
                
                <div>
                    <h4 class="text-xs font-medium text-gray-500 uppercase">Cập nhật lần cuối</h4>
                    <p class="mt-1 text-sm text-gray-900">{{ $facebookPost->updated_at->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>
        </div>
        
        <!-- Content panel -->
        <div class="col-span-1 md:col-span-3 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Nội dung bài viết</h3>
            </div>
            <div class="p-6">
                <div class="prose max-w-none">
                    {!! nl2br(e($facebookPost->content)) !!}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rewritten content if available -->
    @if(isset($rewrittenTitle) && isset($rewrittenContent))
    <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Nội dung đã viết lại</h2>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6">
                <form action="{{ route('admin.facebook-posts.save-rewritten', $facebookPost) }}" method="POST">
                    @csrf
                    
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề bài viết</label>
                        <input type="text" name="title" id="title" value="{{ $rewrittenTitle }}" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <div class="mb-6">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                        <select name="category_id" id="category_id" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <option value="">-- Chọn danh mục --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Nội dung</label>
                        <textarea name="content" id="content" rows="12" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ $rewrittenContent }}</textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Lưu bài viết đã viết lại
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection 