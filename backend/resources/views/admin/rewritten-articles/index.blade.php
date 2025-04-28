@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Quản lý bài viết AI</h1>
            <div class="flex space-x-3">
                <a href="{{ route('admin.rewritten-articles.rewrite-form') }}" class="flex items-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Viết lại bằng AI
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-md" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-md" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filter Section -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-700">Bộ lọc bài viết AI</h2>
                <span class="text-sm text-gray-500">Lọc theo các tiêu chí bên dưới</span>
            </div>
            <form action="{{ route('admin.rewritten-articles.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                        <option value="">Tất cả</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                    </select>
                </div>

                <div>
                    <label for="created_from" class="block text-sm font-medium text-gray-700 mb-1">Ngày tạo</label>
                    <div class="flex space-x-2">
                        <div class="flex-1">
                            <input type="date" id="created_from" name="created_from" value="{{ request('created_from') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Từ ngày">
                        </div>
                        <div class="flex-1">
                            <input type="date" id="created_to" name="created_to" value="{{ request('created_to') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Đến ngày">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Danh mục</label>
                    <select id="category" name="category_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                        <option value="">Tất cả danh mục</option>
                        @foreach(\App\Models\Category::all() as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3 flex justify-end space-x-3 mt-4">
                    <a href="{{ route('admin.rewritten-articles.index') }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Đặt lại
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                        </svg>
                        Áp dụng
                    </button>
                </div>
            </form>
        </div>

        @if($rewrittenArticles->count() > 0)
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-1/4">Tiêu đề</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-1/8">Danh mục</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-1/8">Trạng thái</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-1/8">Nguồn</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-1/8">Tạo bởi</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-1/6">Ngày tạo</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-1/4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rewrittenArticles as $article)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900 mb-1">{{ $article->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $article->slug }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $article->category->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                            @if($article->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($article->status === 'approved') bg-green-100 text-green-800
                                            @else bg-red-100 text-red-800 @endif">
                                            <span class="w-2 h-2 mr-1 rounded-full 
                                                @if($article->status === 'pending') bg-yellow-500
                                                @elseif($article->status === 'approved') bg-green-500
                                                @else bg-red-500 @endif"></span>
                                            @if($article->status === 'pending') Chờ duyệt
                                            @elseif($article->status === 'approved') Đã duyệt
                                            @else Từ chối @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $originalArticle = null;
                                            if ($article->originalArticle && $article->originalArticle->original_article_id) {
                                                $originalArticle = \App\Models\Article::find($article->originalArticle->original_article_id);
                                            }
                                        @endphp

                                        @if($originalArticle && $originalArticle->source_url)
                                            <div class="flex items-center">
                                                <a href="{{ $originalArticle->source_url }}" 
                                                   target="_blank" rel="noopener noreferrer"
                                                   class="text-green-600 hover:text-green-800 mr-2"
                                                   title="{{ $originalArticle->source_name ? $originalArticle->source_name : 'Xem nguồn gốc bài viết' }}">
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
                                                <span class="text-sm text-gray-600">{{ $originalArticle->source_name ?: '' }}</span>
                                            </div>
                                        @elseif($originalArticle && $originalArticle->source_name)
                                            <span class="text-sm text-gray-500">
                                                {{ $originalArticle->source_name }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-500">Không có nguồn</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center text-sm text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ $article->user->name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <span class="inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ $article->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <div class="flex flex-col space-y-3">
                                            <a href="{{ route('admin.rewritten-articles.show', $article) }}" class="block text-center text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 py-2 px-3 rounded-md transition-colors">
                                                <span class="inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Xem
                                                </span>
                                            </a>
                                            
                                            <a href="{{ route('admin.rewritten-articles.edit', $article) }}" class="block text-center text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 py-2 px-3 rounded-md transition-colors">
                                                <span class="inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Sửa
                                                </span>
                                            </a>
                                            
                                            <a href="{{ route('admin.rewritten-articles.ai-rewrite-form', $article) }}" class="block text-center text-purple-600 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 py-2 px-3 rounded-md transition-colors">
                                                <span class="inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                    </svg>
                                                    AI Viết lại
                                                </span>
                                            </a>
                                            
                                            @if($article->status === 'pending')
                                            <a href="{{ route('admin.rewritten-articles.approve', $article) }}" class="block text-center text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 py-2 px-3 rounded-md transition-colors">
                                                <span class="inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Duyệt
                                                </span>
                                            </a>
                                            
                                            <form action="{{ route('admin.rewritten-articles.reject', $article) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="w-full text-center text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 py-2 px-3 rounded-md transition-colors" onclick="return confirm('Bạn có chắc chắn muốn từ chối bài viết này?')">
                                                    <span class="inline-flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Từ chối
                                                    </span>
                                                </button>
                                            </form>
                                            @endif
                                            
                                            <form action="{{ route('admin.rewritten-articles.destroy', $article) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full text-center text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 py-2 px-3 rounded-md transition-colors" onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                                    <span class="inline-flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Xóa
                                                    </span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 bg-gray-50">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-lg font-medium">Không tìm thấy bài viết nào</span>
                                            <p class="text-sm mt-1">Chưa có bài viết AI nào hoặc không có bài viết nào phù hợp với bộ lọc.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white shadow-md rounded-lg p-8 mb-8 text-center">
                <div class="flex flex-col items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Không có bài viết AI nào</h3>
                    <p class="text-gray-500 mb-6">Hãy bắt đầu tạo bài viết AI mới bằng cách sử dụng tính năng viết lại bằng AI</p>
                    <a href="{{ route('admin.rewritten-articles.rewrite-form') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Tạo bài viết AI
                    </a>
                </div>
            </div>
        @endif

        <div class="mt-4">
            {{ $rewrittenArticles->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection 