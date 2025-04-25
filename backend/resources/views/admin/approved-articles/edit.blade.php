@extends('layouts.admin')

@section('title', 'Chỉnh sửa bài viết - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Chỉnh sửa bài viết</h2>
                    <a href="{{ route('admin.approved-articles.index') }}" class="text-gray-600 hover:text-gray-900">
                        Quay lại danh sách
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('admin.approved-articles.update', $approvedArticle) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    @method('PUT')
                    
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin bài viết</h3>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label for="title" class="block text-sm font-medium text-gray-700">Tiêu đề</label>
                                <div class="mt-1">
                                    <input type="text" name="title" id="title" value="{{ old('title', $approvedArticle->title) }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                                @error('title')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="category_id" class="block text-sm font-medium text-gray-700">Danh mục</label>
                                <div class="mt-1">
                                    <select id="category_id" name="category_id" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $approvedArticle->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('category_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái xuất bản</label>
                                <div class="mt-1">
                                    <select id="status" name="status" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="published" {{ old('status', $approvedArticle->status) == 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                                        <option value="unpublished" {{ old('status', $approvedArticle->status) == 'unpublished' ? 'selected' : '' }}>Chưa xuất bản</option>
                                    </select>
                                </div>
                                @error('status')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-6">
                                <label for="content" class="block text-sm font-medium text-gray-700">Nội dung</label>
                                <div class="mt-1">
                                    <textarea id="content" name="content" rows="20" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md editor-content">{{ old('content', $approvedArticle->content) }}</textarea>
                                </div>
                                @error('content')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <input type="hidden" name="content_media_ids" id="content_media_ids" value="">
                                <div class="flex justify-end mt-2">
                                    <button type="button" id="insert-media-btn" 
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-5 font-medium rounded-md text-white bg-green-600 hover:bg-green-500 focus:outline-none focus:border-green-700 focus:shadow-outline-green active:bg-green-700 transition ease-in-out duration-150">
                                        <svg class="mr-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Chèn hình ảnh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Ảnh đại diện</h3>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                @if($approvedArticle->featuredImage)
                                    <div class="mb-4">
                                        <p class="mb-2 text-sm text-gray-500">Ảnh hiện tại:</p>
                                        <img src="{{ $approvedArticle->featuredImage->url }}" alt="{{ $approvedArticle->title }}" class="max-w-xs h-auto rounded-lg shadow">
                                        <p class="mt-1 text-sm text-gray-500">{{ $approvedArticle->featuredImage->name }}</p>
                                    </div>
                                @elseif($approvedArticle->featured_image)
                                    <div class="mb-4">
                                        <p class="mb-2 text-sm text-gray-500">Ảnh hiện tại:</p>
                                        <img src="{{ $approvedArticle->featured_image_url }}" alt="{{ $approvedArticle->title }}" class="max-w-xs h-auto rounded-lg shadow">
                                    </div>
                                @endif
                                
                                <input type="hidden" name="featured_image_id" id="featured_image_id" value="{{ old('featured_image_id', $approvedArticle->featured_image_id) }}">
                                
                                <div class="flex space-x-4 mb-4">
                                    <button type="button" id="select-featured-image-btn" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Chọn từ thư viện
                                    </button>
                                    
                                    <span class="text-gray-500 self-center">hoặc</span>
                                </div>
                                
                                <label for="featured_image" class="block text-sm font-medium text-gray-700">
                                    Tải lên ảnh mới
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
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin SEO</h3>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label for="meta_title" class="block text-sm font-medium text-gray-700">Meta Title</label>
                                <div class="mt-1">
                                    <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $approvedArticle->meta_title) }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Để trống để sử dụng tiêu đề bài viết</p>
                                @error('meta_title')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-6">
                                <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                                <div class="mt-1">
                                    <textarea id="meta_description" name="meta_description" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('meta_description', $approvedArticle->meta_description) }}</textarea>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Mô tả ngắn gọn cho công cụ tìm kiếm. Khuyến nghị 150-160 ký tự.</p>
                                @error('meta_description')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="pt-5">
                        <div class="flex justify-end">
                            <a href="{{ route('admin.approved-articles.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Hủy
                            </a>
                            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Lưu
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize media selector for content
        const contentMediaSelector = new MediaSelector({
            type: 'image',
            insertCallback: function(media) {
                const editor = document.getElementById('content');
                const mediaHtml = `<img src="${media.url}" alt="${media.name}" class="img-fluid">`;
                
                // Insert at cursor position or append
                if (typeof editor.setRangeText === 'function') {
                    editor.setRangeText(mediaHtml);
                } else {
                    editor.value += mediaHtml;
                }
                
                // Add media ID to the list of used media
                const contentMediaIdsInput = document.getElementById('content_media_ids');
                let mediaIds = contentMediaIdsInput.value ? contentMediaIdsInput.value.split(',') : [];
                if (!mediaIds.includes(media.id.toString())) {
                    mediaIds.push(media.id);
                }
                contentMediaIdsInput.value = mediaIds.join(',');
            }
        });
        
        // Initialize media selector for featured image
        const featuredImageSelector = new MediaSelector({
            type: 'image',
            insertCallback: function(media) {
                console.log('Featured image callback with media:', media);
                // Đặt giá trị ID vào input hidden
                document.getElementById('featured_image_id').value = media.id;
                
                // Update displayed image if it exists
                const imageContainer = document.getElementById('select-featured-image-btn').parentNode.parentNode;
                const existingImageInfo = imageContainer.querySelector('div');
                
                if (existingImageInfo) {
                    // Update existing image
                    const img = existingImageInfo.querySelector('img');
                    
                    if (img) {
                        // Cập nhật hình ảnh hiện tại nếu tồn tại
                        img.src = media.url;
                        img.alt = media.name;
                        
                        const nameEl = existingImageInfo.querySelector('p:last-child');
                        if (nameEl) {
                            nameEl.textContent = media.name;
                        } else {
                            const newNameEl = document.createElement('p');
                            newNameEl.className = 'mt-1 text-sm text-gray-500';
                            newNameEl.textContent = media.name;
                            existingImageInfo.appendChild(newNameEl);
                        }
                    } else {
                        // Nếu img không tồn tại, tạo mới toàn bộ phần tử hiển thị
                        existingImageInfo.innerHTML = `
                            <p class="mb-2 text-sm text-gray-500">Ảnh hiện tại:</p>
                            <img src="${media.url}" alt="${media.name}" class="max-w-xs h-auto rounded-lg shadow">
                            <p class="mt-1 text-sm text-gray-500">${media.name}</p>
                        `;
                    }
                } else {
                    // Create new image display
                    const newImageInfo = document.createElement('div');
                    newImageInfo.className = 'mb-4';
                    newImageInfo.innerHTML = `
                        <p class="mb-2 text-sm text-gray-500">Ảnh hiện tại:</p>
                        <img src="${media.url}" alt="${media.name}" class="max-w-xs h-auto rounded-lg shadow">
                        <p class="mt-1 text-sm text-gray-500">${media.name}</p>
                    `;
                    
                    // Insert before the buttons container
                    imageContainer.insertBefore(newImageInfo, document.getElementById('select-featured-image-btn').parentNode);
                }
            }
        });
        
        // Bind buttons
        document.getElementById('insert-media-btn').addEventListener('click', function() {
            contentMediaSelector.open();
        });
        
        document.getElementById('select-featured-image-btn').addEventListener('click', function() {
            featuredImageSelector.open();
        });
        
        // Extract media IDs from content on form submit
        document.querySelector('form').addEventListener('submit', function() {
            // You could implement a regex to extract all image src attributes that match your media URLs
            // For simplicity, we'll rely on the tracked IDs from the insertCallback
        });
    });
</script>
@endpush
@endsection 