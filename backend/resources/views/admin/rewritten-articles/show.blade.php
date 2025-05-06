@extends('layouts.admin')

@section('title', 'Chi tiết bài viết AI - Hệ thống Magazine AI')

@section('content')
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
                <img src="{{ $rewrittenArticle->featured_image_url }}" 
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
                <button type="button" id="approveButton"
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Duyệt bài viết
                </button>
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
                
                <!-- Hidden form for approval with subcategory data -->
                <form id="approveForm" action="{{ route('admin.rewritten-articles.approve', $rewrittenArticle) }}" method="POST" class="hidden">
                    @csrf
                    @method('POST')
                    <input type="hidden" name="subcategory_id" value="{{ $rewrittenArticle->subcategory_id }}">
                    <input type="hidden" name="explicit_subcategory_id" value="{{ $rewrittenArticle->subcategory_id }}">
                </form>
            </div>
            @endif

            <div class="flex items-center text-sm text-gray-500 mb-6">
                <span class="mr-4">Danh mục: {{ $rewrittenArticle->category->name }}</span>
                @if($rewrittenArticle->subcategory)
                <span class="mr-4">Danh mục con: {{ $rewrittenArticle->subcategory->name }}</span>
                @endif
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
                    <div class="flex items-center space-x-4 mt-2">
                        <a href="{{ route('admin.approved-articles.show', $rewrittenArticle->originalArticle) }}" 
                           class="text-blue-600 hover:text-blue-900 text-sm inline-flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Xem bài viết gốc
                        </a>
                        
                        @php
                            // Tìm thông tin nguồn gốc từ bài viết gốc (nếu có)
                            $originalArticle = \App\Models\Article::find($rewrittenArticle->originalArticle->original_article_id);
                        @endphp

                        @if($originalArticle && $originalArticle->source_url)
                        <a href="{{ $originalArticle->source_url }}" 
                           class="text-green-600 hover:text-green-900 text-sm inline-flex items-center"
                           target="_blank" rel="noopener noreferrer" title="{{ $originalArticle->source_name ? $originalArticle->source_name : 'Xem nguồn gốc bài viết' }}">
                            @if(strpos($originalArticle->source_url, 'facebook.com') !== false)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="#1877F2">
                                <path d="M12.001 2.002c-5.522 0-9.999 4.477-9.999 9.999 0 4.99 3.656 9.126 8.437 9.879v-6.988h-2.54v-2.891h2.54V9.798c0-2.508 1.493-3.891 3.776-3.891 1.094 0 2.24.195 2.24.195v2.459h-1.264c-1.24 0-1.628.772-1.628 1.563v1.875h2.771l-.443 2.891h-2.328v6.988C18.344 21.129 22 16.992 22 12.001c0-5.522-4.477-9.999-9.999-9.999z"/>
                            </svg>
                            @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            @endif
                        </a>
                        @endif
                    </div>
                    
                    @if($originalArticle && $originalArticle->source_name)
                    <div class="mt-2 text-sm text-gray-600">
                        <span class="font-medium">Nguồn:</span> 
                        @if($originalArticle->source_url && strpos($originalArticle->source_url, 'facebook.com') !== false)
                            Facebook
                        @else
                            {{ $originalArticle->source_name }}
                        @endif
                    </div>
                    @endif
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const approveButton = document.getElementById('approveButton');
    const approveForm = document.getElementById('approveForm');
    
    if (approveButton && approveForm) {
        approveButton.addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn duyệt bài viết này?')) {
                // Log subcategory data before submitting form
                console.log('Submitting approval with subcategory_id:', 
                    document.querySelector('input[name="subcategory_id"]').value);
                console.log('Explicit subcategory_id:', 
                    document.querySelector('input[name="explicit_subcategory_id"]').value);
                
                // Submit the form
                approveForm.submit();
            }
        });
    }
});
</script>
@endpush 