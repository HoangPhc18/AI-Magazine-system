@extends('layouts.admin')

@section('title', 'Viết lại bài viết bằng AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Viết lại bài viết bằng AI</h1>
            <p class="mt-1 text-sm text-gray-500">Tạo bài viết được viết lại từ bài gốc bằng AI.</p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('admin.rewritten-articles.rewrite-process') }}" method="POST" class="space-y-8" enctype="multipart/form-data">
            @csrf
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Cài đặt viết lại</h3>
                
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="original_article_id" class="block text-sm font-medium text-gray-700">
                            Chọn bài viết gốc để viết lại
                        </label>
                        <div class="mt-1">
                            <select id="original_article_id" name="original_article_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled {{ !$selectedArticle ? 'selected' : '' }}>-- Chọn bài viết --</option>
                                @foreach($originalArticles as $article)
                                    <option value="{{ $article->id }}" {{ $selectedArticle && $selectedArticle->id == $article->id ? 'selected' : '' }}>
                                        {{ $article->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('original_article_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if($selectedArticle && ($selectedArticle->source_url || $selectedArticle->source_name))
                    <div class="sm:col-span-6">
                        <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-1">Thông tin nguồn bài viết gốc</h4>
                            @if($selectedArticle->source_name)
                            <p class="text-sm text-gray-600">
                                <span class="font-medium">Nguồn:</span> {{ $selectedArticle->source_name }}
                            </p>
                            @endif
                            
                            @if($selectedArticle->source_url)
                            <div class="flex items-center mt-2">
                                <span class="text-sm text-gray-600 mr-2">Nguồn gốc:</span>
                                <a href="{{ $selectedArticle->source_url }}" 
                                   class="text-green-600 hover:text-green-900"
                                   target="_blank" rel="noopener noreferrer" title="Xem nguồn gốc bài viết">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    @php
                        // Tìm thông tin nguồn gốc từ bài viết gốc (nếu có)
                        $originalArticle = null;
                        if ($selectedArticle && $selectedArticle->original_article_id) {
                            $originalArticle = \App\Models\Article::find($selectedArticle->original_article_id);
                        }
                    @endphp
                    
                    @if($originalArticle && ($originalArticle->source_url || $originalArticle->source_name))
                    <div class="sm:col-span-6">
                        <div class="mt-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-1">Thông tin nguồn bài viết gốc</h4>
                            @if($originalArticle->source_name)
                            <p class="text-sm text-gray-600">
                                <span class="font-medium">Nguồn:</span> {{ $originalArticle->source_name }}
                            </p>
                            @endif
                            
                            @if($originalArticle->source_url)
                            <div class="flex items-center mt-2">
                                <span class="text-sm text-gray-600 mr-2">Nguồn gốc:</span>
                                <a href="{{ $originalArticle->source_url }}" 
                                   class="text-green-600 hover:text-green-900"
                                   target="_blank" rel="noopener noreferrer" title="Xem nguồn gốc bài viết">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endif

                    <div class="sm:col-span-3">
                        <label for="category_id" class="block text-sm font-medium text-gray-700">
                            Danh mục
                        </label>
                        <div class="mt-1">
                            <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled selected>-- Chọn danh mục --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $selectedArticle && $selectedArticle->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <label for="featured_image" class="block text-sm font-medium text-gray-700">
                            Ảnh đại diện
                        </label>
                        <div class="mt-1">
                            <input type="file" id="featured_image" name="featured_image" accept="image/*" class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100">
                            @error('featured_image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @if($selectedArticle && $selectedArticle->featured_image)
                            <div class="mt-2">
                                <img src="{{ $selectedArticle->featured_image_url }}" alt="Current featured image" class="h-20 w-20 object-cover rounded">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Cấu hình AI</h3>
                
                <div class="bg-gray-50 px-4 py-3 rounded-md">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Nhà cung cấp AI</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($aiSettings->provider) }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Mô hình</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $aiSettings->model_name }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Tự động duyệt</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $aiSettings->auto_approval ? 'Đã bật' : 'Đã tắt' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Giới hạn hàng ngày</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $aiSettings->max_daily_rewrites == 0 ? 'Không giới hạn' : $aiSettings->max_daily_rewrites . ' bài viết' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4">
                        <a href="{{ route('admin.ai-settings.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                            Thay đổi cài đặt AI →
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('admin.rewritten-articles.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    Hủy
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                    Tạo bản viết lại
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const originalArticleSelect = document.getElementById('original_article_id');
    
    originalArticleSelect.addEventListener('change', function() {
        const articleId = this.value;
        if (articleId) {
            // Redirect to the same page with article_id parameter
            window.location.href = "{{ route('admin.rewritten-articles.rewrite-form') }}/" + articleId;
        }
    });
});
</script>
@endpush
@endsection 