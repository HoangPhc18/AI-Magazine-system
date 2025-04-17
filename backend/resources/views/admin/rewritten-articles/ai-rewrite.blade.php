@extends('layouts.admin')

@section('title', 'Viết lại bài viết bằng AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Viết lại bài viết bằng AI</h1>
            <p class="mt-1 text-sm text-gray-500">Sử dụng AI để viết lại bài viết hiện tại.</p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('admin.rewritten-articles.ai-rewrite', $rewrittenArticle) }}" method="POST" class="space-y-8" enctype="multipart/form-data">
            @csrf
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin bài viết</h3>
                
                <div class="grid grid-cols-1 gap-y-4">
                    <div>
                        <p class="block text-sm font-medium text-gray-700 mb-1">
                            Tiêu đề bài viết
                        </p>
                        <p class="text-base text-gray-900">{{ $rewrittenArticle->title }}</p>
                    </div>

                    <div>
                        <p class="block text-sm font-medium text-gray-700 mb-1">
                            Trạng thái hiện tại
                        </p>
                        <p class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $rewrittenArticle->status_badge_class }}">
                            {{ ucfirst($rewrittenArticle->status) }}
                        </p>
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">
                            Danh mục
                        </label>
                        <div class="mt-1">
                            <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>-- Chọn danh mục --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $rewrittenArticle->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Ảnh đại diện</h3>
                
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        @if($rewrittenArticle->featured_image)
                            <div class="mb-4">
                                <p class="mb-2 text-sm text-gray-500">Ảnh hiện tại:</p>
                                <img src="{{ $rewrittenArticle->featured_image_url }}" alt="{{ $rewrittenArticle->title }}" class="max-w-xs h-auto rounded-lg shadow">
                            </div>
                        @endif
                        
                        <label for="featured_image" class="block text-sm font-medium text-gray-700">
                            Tải lên ảnh mới (tùy chọn)
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="featured_image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                        <span>Tải tệp lên</span>
                                        <input id="featured_image" name="featured_image" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">hoặc kéo và thả</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    PNG, JPG, GIF tối đa 2MB
                                </p>
                            </div>
                        </div>
                        @error('featured_image')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Nội dung hiện tại</h3>
                <div class="prose max-w-none mt-2 p-4 bg-gray-50 rounded-md border border-gray-200 text-gray-800">
                    {!! $rewrittenArticle->formatted_content !!}
                </div>
                <p class="mt-3 text-sm text-gray-600">
                    Nội dung này sẽ được gửi tới AI để viết lại. Quá trình này có thể mất vài giây.
                </p>
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
                <a href="{{ route('admin.rewritten-articles.show', $rewrittenArticle) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    Hủy
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                    Viết lại bằng AI
                </button>
            </div>
        </form>
    </div>
</div>
@endsection 