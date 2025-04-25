@extends('layouts.app')

@section('title', $category->name . ' - Magazine AI System')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Category Header -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-gray-600">{{ $category->description }}</p>
            @endif
            <p class="mt-2 text-sm text-gray-500">{{ $articles->total() }} bài viết</p>
        </div>
    </div>

    <!-- Article Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($articles as $article)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="h-48 overflow-hidden">
                    @if($article->featuredImage)
                        <img src="{{ $article->featuredImage->url }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                    @elseif($article->featured_image)
                        <img src="{{ asset('storage/' . $article->featured_image) }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($article->published_at)->format('d/m/Y') }}
                        </span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2 hover:text-blue-600">
                        <a href="{{ route('articles.show', $article->slug) }}">
                            {{ $article->title }}
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-4">
                        {{ Str::limit(strip_tags($article->content), 120) }}
                    </p>
                    <div class="flex items-center justify-between">
                        @if($article->user)
                            <span class="text-sm text-gray-600">Bởi: {{ $article->user->name }}</span>
                        @endif
                        <a href="{{ route('articles.show', $article->slug) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Đọc tiếp <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Không tìm thấy bài viết</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Hiện chưa có bài viết nào trong danh mục này.
                </p>
                <div class="mt-6">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Về trang chủ
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $articles->links() }}
    </div>
</div>
@endsection 