@extends('layouts.admin')

@section('title', 'Tạo bài viết mới - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Tạo bài viết mới</h2>
                    <a href="{{ route('admin.rewritten-articles.index') }}" class="text-gray-600 hover:text-gray-900">
                        Quay lại danh sách bài viết AI
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
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
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('admin.approved-articles.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    
                    <input type="hidden" name="original_article_id" value="{{ $rewrittenArticle->id }}">
                    <input type="hidden" name="ai_generated" value="{{ $rewrittenArticle->ai_generated ? '1' : '0' }}">
                    <input type="hidden" name="content_media_ids" id="content_media_ids" value="">
                    <input type="hidden" name="featured_image_id" id="featured_image_id" value="">
                    
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin bài viết</h3>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label for="title" class="block text-sm font-medium text-gray-700">Tiêu đề</label>
                                <div class="mt-1">
                                    <input type="text" name="title" id="title" value="{{ old('title', $rewrittenArticle->title) }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
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
                                            <option value="{{ $category->id }}" {{ old('category_id', $rewrittenArticle->category_id) == $category->id ? 'selected' : '' }}>
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
                                <label for="subcategory_id" class="block text-sm font-medium text-gray-700">Danh mục con</label>
                                <div class="mt-1">
                                    <select id="subcategory_id" name="subcategory_id" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="">-- Chọn danh mục con --</option>
                                        @if($rewrittenArticle->category && $rewrittenArticle->category->subcategories)
                                            @foreach($rewrittenArticle->category->subcategories as $subcategory)
                                                <option value="{{ $subcategory->id }}" {{ old('subcategory_id', $rewrittenArticle->subcategory_id) == $subcategory->id ? 'selected' : '' }}>
                                                    {{ $subcategory->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @error('subcategory_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái xuất bản</label>
                                <div class="mt-1">
                                    <select id="status" name="status" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                                        <option value="unpublished" {{ old('status', 'unpublished') == 'unpublished' ? 'selected' : '' }}>Chưa xuất bản</option>
                                    </select>
                                </div>
                                @error('status')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-6">
                                <div class="mb-4 relative" x-data="{ showOptions: false }">
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
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                            </button>
                                            <span class="border-r border-gray-300 mx-1"></span>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="superscript" title="Chỉ số trên">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9l8 10V9"></path><path d="M21 9h-4c0-1 1-2 2.5-2s2.5 1 2.5 2c0 1-1 2-2.5 2"></path></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="subscript" title="Chỉ số dưới">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9l8 10V9"></path><path d="M21 19h-4c0-1 1-2 2.5-2s2.5 1 2.5 2c0 1-1 2-2.5 2"></path></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="paragraph" title="Thêm đoạn văn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>
                                            </button>
                                            <button type="button" class="format-btn p-1 rounded hover:bg-gray-200" data-format="link" title="Thêm liên kết">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                            </button>
                                        </div>
                                        <textarea id="content" name="content" rows="20" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md editor-content">{{ old('content', $rewrittenArticle->content) }}</textarea>
                                    </div>
                                    @error('content')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
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
                    </div>

                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Ảnh đại diện</h3>
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                @if($rewrittenArticle->featured_image)
                                    <div class="mb-4">
                                        <p class="mb-2 text-sm text-gray-500">Ảnh hiện tại:</p>
                                        <img src="{{ Storage::url($rewrittenArticle->featured_image) }}" alt="{{ $rewrittenArticle->title }}" class="max-w-xs h-auto rounded-lg shadow featured-image">
                                        <input type="hidden" name="current_featured_image" value="{{ $rewrittenArticle->featured_image }}">
                                    </div>
                                @endif
                                
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
                                
                                <!-- Hidden input for featured image ID -->
                                <input type="hidden" name="featured_image_id" id="featured_image_id" value="">
                                
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
                                    <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $rewrittenArticle->meta_title) }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Để trống để sử dụng tiêu đề bài viết</p>
                                @error('meta_title')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-6">
                                <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                                <div class="mt-1">
                                    <textarea id="meta_description" name="meta_description" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('meta_description', $rewrittenArticle->meta_description) }}</textarea>
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
                            <a href="{{ route('admin.rewritten-articles.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Hủy
                            </a>
                            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Lưu bài viết
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
        // Flag to track media operations
        let lastOperation = null;
        let contentMediaIds = [];

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
                console.log('Media được chọn cho nội dung:', media);
                
                // Đảm bảo rằng media có thuộc tính url
                if (!media.url) {
                    console.error('Media không có URL:', media);
                    alert('Lỗi: Media không có URL. Vui lòng thử lại.');
                    return;
                }
                
                // Create a properly formatted HTML for the image - thêm class "content-image" để phân biệt
                const mediaHtml = `<img src="${media.url}" alt="${media.name}" class="img-fluid content-image" data-media-id="${media.id}">`;
                
                // Log HTML sẽ được chèn
                console.log('HTML sẽ được chèn vào nội dung:', mediaHtml);
                
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
                    contentMediaIds.push(media.id.toString());
                    // Update the hidden input immediately to ensure it's saved
                    const contentMediaIdsInput = document.getElementById('content_media_ids');
                    if (contentMediaIdsInput) {
                        contentMediaIdsInput.value = contentMediaIds.join(',');
                    }
                }
                
                // Log ra danh sách media IDs sau khi cập nhật
                console.log('Content media IDs sau khi chèn:', contentMediaIds);
            }
        });
        
        // Initialize media selector for featured image
        const featuredImageSelector = new MediaSelector({
            type: 'image',
            isFeaturedImage: true,
            insertCallback: function(media) {
                lastOperation = 'featured';
                console.log('Featured image callback with media:', media);
                // Set the ID in the hidden input - ONLY for featured image
                document.getElementById('featured_image_id').value = media.id;
                
                // Update the UI to show the selected image
                const imageContainer = document.getElementById('select-featured-image-btn').parentNode.parentNode;
                
                // Create or update the image display
                let existingImageInfo = imageContainer.querySelector('div.mb-4');
                
                if (existingImageInfo) {
                    // Update existing image display
                    existingImageInfo.innerHTML = `
                        <p class="mb-2 text-sm text-gray-500">Ảnh đã chọn:</p>
                        <img src="${media.url}" alt="${media.name}" class="max-w-xs h-auto rounded-lg shadow featured-image">
                        <p class="mt-1 text-sm text-gray-500">${media.name}</p>
                    `;
                } else {
                    // Create new image display
                    const newImageInfo = document.createElement('div');
                    newImageInfo.className = 'mb-4';
                    newImageInfo.innerHTML = `
                        <p class="mb-2 text-sm text-gray-500">Ảnh đã chọn:</p>
                        <img src="${media.url}" alt="${media.name}" class="max-w-xs h-auto rounded-lg shadow featured-image">
                        <p class="mt-1 text-sm text-gray-500">${media.name}</p>
                    `;
                    
                    // Insert before the buttons container
                    imageContainer.insertBefore(newImageInfo, document.getElementById('select-featured-image-btn').parentNode);
                }
                
                // Không thêm ID của ảnh đại diện vào danh sách contentMediaIds
                // Để đảm bảo xử lý độc lập với ảnh trong nội dung
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
                // Đảm bảo content_media_ids được cập nhật đúng trước khi gửi form
                if (contentMediaIds.length > 0) {
                    const mediaIdsInput = document.getElementById('content_media_ids');
                    if (mediaIdsInput) {
                        mediaIdsInput.value = contentMediaIds.join(',');
                        console.log('Content media IDs for submission:', contentMediaIds);
                    }
                }
                
                // Kiểm tra xem đã có featured_image_id chưa
                const featuredImageInput = document.getElementById('featured_image_id');
                if (featuredImageInput && featuredImageInput.value) {
                    console.log('Featured image ID for submission:', featuredImageInput.value);
                }
                
                // Continue with normal form submission
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

        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');
        
        if (categorySelect && subcategorySelect) {
            // Store initial values
            const initialCategoryId = categorySelect.value;
            const initialSubcategoryId = subcategorySelect.value;
            
            categorySelect.addEventListener('change', function() {
                const categoryId = this.value;
                
                // Clear current options
                subcategorySelect.innerHTML = '<option value="">-- Chọn danh mục con --</option>';
                
                // Very important: Clear the selected subcategory value when category changes
                subcategorySelect.value = '';
                
                if (categoryId) {
                    // Fetch subcategories for the selected category
                    fetch(`/admin/categories/${categoryId}/subcategories`)
                        .then(response => response.json())
                        .then(subcategories => {
                            if (subcategories.length > 0) {
                                subcategories.forEach(subcategory => {
                                    const option = document.createElement('option');
                                    option.value = subcategory.id;
                                    option.textContent = subcategory.name;
                                    subcategorySelect.appendChild(option);
                                });
                                
                                // If this is the initial page load and we have both initial values, try to select the initial subcategory
                                if (categoryId === initialCategoryId && initialSubcategoryId) {
                                    // Check if the initial subcategory is in the list (exists in the new category)
                                    const initialOption = Array.from(subcategorySelect.options)
                                        .find(option => option.value === initialSubcategoryId);
                                    
                                    if (initialOption) {
                                        subcategorySelect.value = initialSubcategoryId;
                                    }
                                }
                            }
                        })
                        .catch(error => console.error('Error fetching subcategories:', error));
                }
            });
            
            // Trigger change event on page load if category is selected
            if (initialCategoryId) {
                categorySelect.dispatchEvent(new Event('change'));
            }
        }
    });
</script>
@endpush
@endsection 