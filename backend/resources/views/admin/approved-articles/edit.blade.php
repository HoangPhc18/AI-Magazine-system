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
                                <div class="mb-4 relative" x-data="{ showOptions: false }">
                                    <input type="hidden" name="content_media_ids" id="content_media_ids" 
                                        value="{{ $approvedArticle->media->pluck('id')->implode(',') }}">
                                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                                        Nội dung bài viết
                                    </label>
                                    <div class="mt-1">
                                        <div class="editor-toolbar mb-2 flex flex-wrap gap-1 border border-gray-300 rounded-md p-1.5 bg-gray-50">
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="bold" title="In đậm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="italic" title="In nghiêng">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="4" x2="10" y2="4"></line><line x1="14" y1="20" x2="5" y2="20"></line><line x1="15" y1="4" x2="9" y2="20"></line></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="underline" title="Gạch dưới">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"></path><line x1="4" y1="21" x2="20" y2="21"></line></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="strikethrough" title="Gạch ngang">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><path d="M16 6c-.5-1.2-1.8-2-3.5-2-2.2 0-4 1.3-4 3 0 1.8 1.2 2.6 3.5 3.5"></path><path d="M8.5 15c.5 1.2 1.8 2 3.5 2 2.2 0 4-1.3 4-3 0-1.8-1.2-2.6-3.5-3.5"></path></svg>
                                            </button>
                                            <span class="border-r border-gray-300 mx-1"></span>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="h2" title="Tiêu đề H2">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><path d="M17 12a2 2 0 1 0 4 0 2 2 0 1 0-4 0z"></path></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="h3" title="Tiêu đề H3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h8"></path><path d="M4 18V6"></path><path d="M12 18V6"></path><path d="M17 9v6"></path><path d="M21 9v6"></path><path d="M17 12h4"></path></svg>
                                            </button>
                                            <span class="border-r border-gray-300 mx-1"></span>
                                            <div class="relative">
                                                <button type="button" class="format-btn p-1 rounded hover:bg-gray-200 text-color-btn" title="Màu chữ">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 7h6l2 10H7l2-10Z"></path><path d="M4 17h16"></path></svg>
                                                </button>
                                                <div class="color-picker absolute z-10 left-0 top-full mt-1 p-2 bg-white shadow-lg rounded-md border border-gray-200 grid grid-cols-5 gap-1 hidden">
                                                    <button type="button" class="w-6 h-6 bg-red-500 rounded" data-color="#ef4444" title="Đỏ"></button>
                                                    <button type="button" class="w-6 h-6 bg-blue-500 rounded" data-color="#3b82f6" title="Xanh dương"></button>
                                                    <button type="button" class="w-6 h-6 bg-green-500 rounded" data-color="#22c55e" title="Xanh lá"></button>
                                                    <button type="button" class="w-6 h-6 bg-yellow-500 rounded" data-color="#eab308" title="Vàng"></button>
                                                    <button type="button" class="w-6 h-6 bg-purple-500 rounded" data-color="#a855f7" title="Tím"></button>
                                                    <button type="button" class="w-6 h-6 bg-pink-500 rounded" data-color="#ec4899" title="Hồng"></button>
                                                    <button type="button" class="w-6 h-6 bg-indigo-500 rounded" data-color="#6366f1" title="Chàm"></button>
                                                    <button type="button" class="w-6 h-6 bg-gray-700 rounded" data-color="#374151" title="Đen"></button>
                                                    <button type="button" class="w-6 h-6 bg-gray-500 rounded" data-color="#6b7280" title="Xám"></button>
                                                    <button type="button" class="w-6 h-6 bg-white rounded border border-gray-200" data-color="#ffffff" title="Trắng"></button>
                                                </div>
                                            </div>
                                            <div class="relative">
                                                <button type="button" class="format-btn p-1 rounded hover:bg-gray-200 font-size-btn" title="Cỡ chữ">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                                </button>
                                                <div class="font-size-picker absolute z-10 left-0 top-full mt-1 p-2 bg-white shadow-lg rounded-md border border-gray-200 w-32 hidden">
                                                    <button type="button" class="block w-full text-left px-2 py-1 hover:bg-gray-100 rounded text-xs" data-size="small">Nhỏ</button>
                                                    <button type="button" class="block w-full text-left px-2 py-1 hover:bg-gray-100 rounded text-sm" data-size="normal">Thường</button>
                                                    <button type="button" class="block w-full text-left px-2 py-1 hover:bg-gray-100 rounded text-base" data-size="large">Lớn</button>
                                                    <button type="button" class="block w-full text-left px-2 py-1 hover:bg-gray-100 rounded text-lg" data-size="xlarge">Rất lớn</button>
                                                </div>
                                            </div>
                                            <span class="border-r border-gray-300 mx-1"></span>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="alignLeft" title="Căn trái">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="6" x2="3" y2="6"></line><line x1="15" y1="12" x2="3" y2="12"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="alignCenter" title="Căn giữa">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="6" x2="3" y2="6"></line><line x1="18" y1="12" x2="6" y2="12"></line><line x1="21" y1="18" x2="3" y2="18"></line></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="alignRight" title="Căn phải">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="12" x2="9" y2="12"></line><line x1="21" y1="18" x2="7" y2="18"></line></svg>
                                            </button>
                                            <span class="border-r border-gray-300 mx-1"></span>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="ul" title="Danh sách dấu đầu dòng">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="ol" title="Danh sách có thứ tự">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="blockquote" title="Trích dẫn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"></path><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"></path></svg>
                                            </button>
                                            <span class="border-r border-gray-300 mx-1"></span>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="link" title="Chèn liên kết">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                            </button>
                                            <button type="button" id="insert-media-btn" class="p-1 rounded hover:bg-gray-200" title="Chèn hình ảnh">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                            </button>
                                        </div>
                                        <textarea id="content" name="content" rows="20" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md">{{ old('content', $approvedArticle->content) }}</textarea>
                                    </div>
                                    @error('content')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
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
                                
                                <input type="hidden" name="featured_image_id" id="featured_image_id" value="{{ $approvedArticle->featured_image_id }}">
                                
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
        // Initialize article ID for AJAX operations
        const articleId = {{ $approvedArticle->id }};
        
        // Flag to track media operations
        let lastOperation = null;
        let contentMediaIds = [];
        
        // Get existing content media IDs
        const contentMediaIdsInput = document.getElementById('content_media_ids');
        if (contentMediaIdsInput.value) {
            contentMediaIds = contentMediaIdsInput.value.split(',');
        }

        // Initialize format buttons
        const formatButtons = document.querySelectorAll('.format-btn');
        const editor = document.getElementById('content');
        
        formatButtons.forEach(button => {
            button.addEventListener('click', function() {
                const format = this.getAttribute('data-format');
                const selection = {
                    start: editor.selectionStart,
                    end: editor.selectionEnd,
                    text: editor.value.substring(editor.selectionStart, editor.selectionEnd)
                };
                
                let formattedText = '';
                let cursorOffset = 0;
                
                switch(format) {
                    case 'bold':
                        formattedText = `<strong>${selection.text}</strong>`;
                        cursorOffset = 9; // Length of "<strong></strong>"
                        break;
                    case 'italic':
                        formattedText = `<em>${selection.text}</em>`;
                        cursorOffset = 5; // Length of "<em></em>"
                        break;
                    case 'underline':
                        formattedText = `<u>${selection.text}</u>`;
                        cursorOffset = 3; // Length of "<u></u>"
                        break;
                    case 'strikethrough':
                        formattedText = `<s>${selection.text}</s>`;
                        cursorOffset = 3; // Length of "<s></s>"
                        break;
                    case 'h2':
                        formattedText = `\n<h2>${selection.text}</h2>\n`;
                        cursorOffset = 6; // Length of "<h2></h2>"
                        break;
                    case 'h3':
                        formattedText = `\n<h3>${selection.text}</h3>\n`;
                        cursorOffset = 6; // Length of "<h3></h3>"
                        break;
                    case 'alignLeft':
                        formattedText = `<div style="text-align:left">${selection.text}</div>`;
                        cursorOffset = 31; // Length of div tags with style
                        break;
                    case 'alignCenter':
                        formattedText = `<div style="text-align:center">${selection.text}</div>`;
                        cursorOffset = 33; // Length of div tags with style
                        break;
                    case 'alignRight':
                        formattedText = `<div style="text-align:right">${selection.text}</div>`;
                        cursorOffset = 32; // Length of div tags with style
                        break;
                    case 'superscript':
                        formattedText = `<sup>${selection.text}</sup>`;
                        cursorOffset = 6; // Length of "<sup></sup>"
                        break;
                    case 'subscript':
                        formattedText = `<sub>${selection.text}</sub>`;
                        cursorOffset = 6; // Length of "<sub></sub>"
                        break;
                    case 'ul':
                        if (selection.text) {
                            const lines = selection.text.split('\n');
                            formattedText = '\n<ul>\n' + lines.map(line => `    <li>${line.trim()}</li>`).join('\n') + '\n</ul>\n';
                        } else {
                            formattedText = '\n<ul>\n    <li></li>\n</ul>\n';
                        }
                        cursorOffset = 0;
                        break;
                    case 'ol':
                        if (selection.text) {
                            const lines = selection.text.split('\n');
                            formattedText = '\n<ol>\n' + lines.map(line => `    <li>${line.trim()}</li>`).join('\n') + '\n</ol>\n';
                        } else {
                            formattedText = '\n<ol>\n    <li></li>\n</ol>\n';
                        }
                        cursorOffset = 0;
                        break;
                    case 'blockquote':
                        formattedText = `\n<blockquote>${selection.text}</blockquote>\n`;
                        cursorOffset = 13; // Length of "<blockquote></blockquote>"
                        break;
                    case 'paragraph':
                        formattedText = `\n<p>${selection.text}</p>\n`;
                        cursorOffset = 4; // Length of "<p></p>"
                        break;
                    case 'link':
                        const url = prompt('Nhập địa chỉ URL:', 'https://');
                        if (url) {
                            formattedText = `<a href="${url}" target="_blank">${selection.text || url}</a>`;
                            cursorOffset = 0;
                        } else {
                            return; // Don't proceed if no URL provided
                        }
                        break;
                }
                
                if (formattedText) {
                    // Insert the formatted text
                    editor.setRangeText(formattedText, selection.start, selection.end, 'end');
                    
                    // Set focus back to the textarea
                    editor.focus();
                    
                    // If nothing was selected, place cursor inside the tags
                    if (selection.start === selection.end && cursorOffset > 0) {
                        const cursorPos = selection.start + formattedText.length - cursorOffset;
                        editor.setSelectionRange(cursorPos, cursorPos);
                    }
                }
            });
        });

        // Initialize media selector for content
        const contentMediaSelector = new MediaSelector({
            type: 'image',
            insertCallback: function(media) {
                lastOperation = 'content';
                const editor = document.getElementById('content');
                
                // Kiểm tra và log thông tin media
                console.log('Media được chọn:', media);
                
                // Đảm bảo rằng media có thuộc tính url
                if (!media.url) {
                    console.error('Media không có URL:', media);
                    alert('Lỗi: Media không có URL. Vui lòng thử lại.');
                    return;
                }
                
                // Create a properly formatted HTML for the image
                const mediaHtml = `<img src="${media.url}" alt="${media.name}" class="img-fluid" data-media-id="${media.id}">`;
                
                // Log HTML sẽ được chèn
                console.log('HTML sẽ được chèn:', mediaHtml);
                
                // Cải thiện cách chèn ảnh
                if (typeof editor.setRangeText === 'function') {
                    // Sử dụng setRangeText nếu được hỗ trợ
                    editor.setRangeText(mediaHtml);
                    
                    // Trigger sự kiện input để cập nhật bất kỳ listeners nào
                    editor.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                    // Fallback cho các trình duyệt không hỗ trợ setRangeText
                    // Lưu vị trí con trỏ hiện tại
                    const startPos = editor.selectionStart || 0;
                    const endPos = editor.selectionEnd || 0;
                    
                    // Lấy nội dung trước và sau vị trí con trỏ
                    const before = editor.value.substring(0, startPos);
                    const after = editor.value.substring(endPos);
                    
                    // Cập nhật nội dung
                    editor.value = before + mediaHtml + after;
                    
                    // Di chuyển con trỏ đến sau media vừa chèn
                    const newCursorPos = startPos + mediaHtml.length;
                    editor.setSelectionRange(newCursorPos, newCursorPos);
                    
                    // Trigger sự kiện input để cập nhật bất kỳ listeners nào
                    editor.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                // Add media ID to the list of used media
                if (!contentMediaIds.includes(media.id.toString())) {
                    contentMediaIds.push(media.id);
                }
                document.getElementById('content_media_ids').value = contentMediaIds.join(',');
                
                // Log ra danh sách media IDs sau khi cập nhật
                console.log('Content media IDs sau khi chèn:', contentMediaIds);
                
                // For existing articles, send an AJAX request to update media IDs
                updateContentMediaViaAjax();
            }
        });
        
        /**
         * Update content media IDs via AJAX
         */
        function updateContentMediaViaAjax() {
            // Only send AJAX if we have an article ID
            if (typeof articleId === 'undefined' || !articleId) {
                console.log('No article ID available for AJAX update');
                return;
            }
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();
            
            // Add content media IDs to the form
            formData.append('content_media_ids', contentMediaIds.join(','));
            
            // Send the AJAX request
            fetch(`/admin/approved-articles/${articleId}/update-media`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Media IDs updated via AJAX:', data);
                } else {
                    console.error('Error updating media IDs:', data.message);
                }
            })
            .catch(error => {
                console.error('AJAX error updating media IDs:', error);
            });
        }

        // Initialize media selector for featured image
        const featuredImageSelector = new MediaSelector({
            type: 'image',
            isFeaturedImage: true,
            insertCallback: function(media) {
                lastOperation = 'featured';
                console.log('Featured image callback with media:', media);
                // Set the ID in the hidden input
                document.getElementById('featured_image_id').value = media.id;
                
                // Update the UI to show the selected image
                const imageContainer = document.getElementById('select-featured-image-btn').parentNode.parentNode;
                
                // Create or update the image display
                let existingImageInfo = imageContainer.querySelector('div.mb-4');
                
                if (existingImageInfo) {
                    // Update existing image display
                    existingImageInfo.innerHTML = `
                        <p class="mb-2 text-sm text-gray-500">Ảnh đã chọn:</p>
                        <img src="${media.url}" alt="${media.name}" class="max-w-xs h-auto rounded-lg shadow">
                        <p class="mt-1 text-sm text-gray-500">${media.name}</p>
                    `;
                } else {
                    // Create new image display
                    const newImageInfo = document.createElement('div');
                    newImageInfo.className = 'mb-4';
                    newImageInfo.innerHTML = `
                        <p class="mb-2 text-sm text-gray-500">Ảnh đã chọn:</p>
                        <img src="${media.url}" alt="${media.name}" class="max-w-xs h-auto rounded-lg shadow">
                        <p class="mt-1 text-sm text-gray-500">${media.name}</p>
                    `;
                    
                    // Insert before the buttons container
                    imageContainer.insertBefore(newImageInfo, document.getElementById('select-featured-image-btn').parentNode);
                }
            }
        });
        
        // Close MediaSelector modal when any modal is closed
        const modalCloseHandler = function(e) {
            if (e.target.closest('.media-cancel')) {
                // After closing the modal, reset the operation flag
                setTimeout(() => {
                    lastOperation = null;
                }, 100);
            }
        };
        document.addEventListener('click', modalCloseHandler);
        
        // Bind buttons
        document.getElementById('insert-media-btn').addEventListener('click', function() {
            contentMediaSelector.open();
        });
        
        document.getElementById('select-featured-image-btn').addEventListener('click', function() {
            featuredImageSelector.open();
        });
        
        // Add form submit handler to ensure media IDs are saved
        const formSubmitBtn = document.querySelector('button[type="submit"]');
        if (formSubmitBtn) {
            formSubmitBtn.addEventListener('click', function(e) {
                // Force sync media IDs before form submission
                updateContentMediaViaAjax();
                // Continue with normal form submission (no need to prevent default)
            });
        }
        
        // Initialize color picker functionality
        const colorBtn = document.querySelector('.text-color-btn');
        const colorPicker = document.querySelector('.color-picker');
        
        colorBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            colorPicker.classList.toggle('hidden');
            fontSizePicker.classList.add('hidden'); // Hide other dropdown
        });
        
        // Color buttons
        colorPicker.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const color = this.getAttribute('data-color');
                const selection = {
                    start: editor.selectionStart,
                    end: editor.selectionEnd,
                    text: editor.value.substring(editor.selectionStart, editor.selectionEnd)
                };
                
                const formattedText = `<span style="color:${color}">${selection.text}</span>`;
                editor.setRangeText(formattedText, selection.start, selection.end, 'end');
                editor.focus();
                colorPicker.classList.add('hidden');
            });
        });
        
        // Initialize font size picker
        const fontSizeBtn = document.querySelector('.font-size-btn');
        const fontSizePicker = document.querySelector('.font-size-picker');
        
        fontSizeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fontSizePicker.classList.toggle('hidden');
            colorPicker.classList.add('hidden'); // Hide other dropdown
        });
        
        // Font size buttons
        fontSizePicker.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const size = this.getAttribute('data-size');
                const selection = {
                    start: editor.selectionStart,
                    end: editor.selectionEnd,
                    text: editor.value.substring(editor.selectionStart, editor.selectionEnd)
                };
                
                let fontSize;
                switch(size) {
                    case 'small': fontSize = '0.875rem'; break; // 14px
                    case 'normal': fontSize = '1rem'; break;    // 16px
                    case 'large': fontSize = '1.25rem'; break;  // 20px
                    case 'xlarge': fontSize = '1.5rem'; break;  // 24px
                    default: fontSize = '1rem';
                }
                
                const formattedText = `<span style="font-size:${fontSize}">${selection.text}</span>`;
                editor.setRangeText(formattedText, selection.start, selection.end, 'end');
                editor.focus();
                fontSizePicker.classList.add('hidden');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            colorPicker.classList.add('hidden');
            fontSizePicker.classList.add('hidden');
        });
    });
</script>
@endpush
@endsection 