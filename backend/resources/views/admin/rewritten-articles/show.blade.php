@extends('layouts.admin')

@section('title', 'Chi tiết bài viết AI - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Chi tiết bài viết AI</h1>
            <div class="flex space-x-4">
                <a href="{{ route('admin.rewritten-articles.edit', $rewrittenArticle) }}" class="text-blue-600 hover:text-blue-900">
                    Chỉnh sửa
                </a>
                <a href="{{ route('admin.rewritten-articles.ai-rewrite-form', $rewrittenArticle) }}" class="text-indigo-600 hover:text-indigo-900">
                    Viết lại bằng AI
                </a>
                <a href="{{ route('admin.rewritten-articles.index') }}" class="text-gray-600 hover:text-gray-900">
                    Quay lại danh sách
                </a>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            @if($rewrittenArticle->featured_image)
                <div class="relative h-64">
                    <img src="{{ Storage::url($rewrittenArticle->featured_image) }}" 
                         alt="{{ $rewrittenArticle->title }}" 
                         class="w-full h-full object-cover">
                </div>
            @endif

            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $rewrittenArticle->title }}</h2>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                        @if($rewrittenArticle->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($rewrittenArticle->status === 'approved') bg-green-100 text-green-800
                        @else bg-red-100 text-red-800 @endif">
                        @if($rewrittenArticle->status === 'pending') Chờ duyệt
                        @elseif($rewrittenArticle->status === 'approved') Đã duyệt
                        @else Từ chối @endif
                    </span>
                </div>

                @if($rewrittenArticle->status === 'pending')
                <div class="flex space-x-4 mb-6">
                    <a href="{{ route('admin.rewritten-articles.approve', $rewrittenArticle) }}" 
                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Duyệt bài viết
                    </a>
                    <form action="{{ route('admin.rewritten-articles.reject', $rewrittenArticle) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg inline-flex items-center"
                                onclick="return confirm('Bạn có chắc chắn muốn từ chối bài viết này?')">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Từ chối bài viết
                        </button>
                    </form>
                </div>
                @endif

                <div class="flex items-center text-sm text-gray-500 mb-6">
                    <span class="mr-4">Danh mục: {{ $rewrittenArticle->category->name }}</span>
                    <span class="mr-4">Tạo bởi: {{ $rewrittenArticle->user->name }}</span>
                    <span>Ngày tạo: {{ $rewrittenArticle->created_at->format('d/m/Y H:i') }}</span>
                </div>

                @if($rewrittenArticle->ai_generated)
                    <div class="bg-blue-50 p-4 rounded-lg mb-6">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            <span class="text-sm font-medium text-blue-900">Nội dung được tạo bởi AI</span>
                        </div>
                    </div>
                @endif

                @if($rewrittenArticle->originalArticle)
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Bài viết gốc</h3>
                        <p class="text-gray-900">{{ $rewrittenArticle->originalArticle->title }}</p>
                        <a href="{{ route('admin.approved-articles.show', $rewrittenArticle->originalArticle) }}" 
                           class="text-blue-600 hover:text-blue-900 text-sm mt-2 inline-block">
                            Xem bài viết gốc
                        </a>
                    </div>
                @endif

                <div class="prose max-w-none mb-6">
                    {!! $rewrittenArticle->formatted_content !!}
                </div>

                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin SEO</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rewrittenArticle->meta_title ?: $rewrittenArticle->title }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rewrittenArticle->meta_description ?: 'Chưa thiết lập' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Đường dẫn</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $rewrittenArticle->slug }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-6 mt-6">
                    <div class="flex justify-between">
                        <form action="{{ route('admin.rewritten-articles.destroy', $rewrittenArticle) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg"
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                Xóa bài viết
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 