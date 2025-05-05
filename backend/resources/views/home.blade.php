@extends('layouts.app')

@section('title', 'Trang chủ - Magazine AI System')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Hero section với bài viết nổi bật -->
    <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-12 hover-lift transition-all duration-500">
        @if($featuredArticles->count() > 0)
            @php $mainFeature = $featuredArticles->first(); @endphp
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="relative h-80 md:h-auto">
                    <div class="aspect-video overflow-hidden">
                        @if($mainFeature->featuredImage)
                            <img src="{{ $mainFeature->featuredImage->url }}" alt="{{ $mainFeature->title }}" class="w-full h-full object-cover transition-transform duration-700 hover:scale-105">
                        @elseif($mainFeature->featured_image)
                            <img src="{{ asset('storage/' . $mainFeature->featured_image) }}" alt="{{ $mainFeature->title }}" class="w-full h-full object-cover transition-transform duration-700 hover:scale-105">
                        @endif
                    </div>
                    <div class="absolute top-0 left-0 bg-gradient-to-r from-primary-600 to-primary-500 text-white px-4 py-1 uppercase text-xs font-bold tracking-wider">
                        Nổi bật
                    </div>
                </div>
                <div class="p-8">
                    <span class="inline-block bg-primary-100 text-primary-800 text-xs px-2 py-1 rounded-full uppercase font-semibold tracking-wide mb-2">
                        {{ $mainFeature->category->name }}
                    </span>
                    <h1 class="text-3xl font-bold text-gray-900 mb-4 hover:text-primary-600 transition-colors duration-300">
                        <a href="{{ route('articles.show', $mainFeature->slug) }}">
                            {{ $mainFeature->title }}
                        </a>
                    </h1>
                    <p class="text-gray-600 mb-6">
                        {{ Str::limit(strip_tags($mainFeature->content), 200) }}
                    </p>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 text-sm">
                            {{ \Carbon\Carbon::parse($mainFeature->published_at)->format('d/m/Y') }}
                        </span>
                        <a href="{{ route('articles.show', $mainFeature->slug) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-500 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-widest hover:from-primary-700 hover:to-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all">
                            Đọc tiếp
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Bài viết mới nhất -->
    <div class="mb-12">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 relative pl-4 before:content-[''] before:absolute before:left-0 before:top-0 before:h-full before:w-1 before:bg-gradient-to-b before:from-primary-500 before:to-primary-300 before:rounded-full">
                Bài viết mới nhất
            </h2>
            <a href="{{ route('articles.all') }}" class="text-primary-600 hover:text-primary-800 text-sm font-medium group flex items-center transition-all">
                Xem tất cả
                <span aria-hidden="true" class="ml-1 group-hover:translate-x-1 transition-transform">&rarr;</span>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($recentArticles as $article)
                <div class="bg-white shadow-md rounded-lg overflow-hidden group">
                    <div class="h-48 overflow-hidden relative">
                        @if($article->featuredImage)
                            <img src="{{ $article->featuredImage->url }}" alt="{{ $article->title }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                        @elseif($article->featured_image)
                            <img src="{{ asset('storage/' . $article->featured_image) }}" alt="{{ $article->title }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-blue-100 to-indigo-50 flex items-center justify-center">
                                <svg class="w-10 h-10 text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="p-5">
                        <div class="flex items-center mb-2">
                            <span class="text-xs font-medium text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full">
                                {{ $article->category->name }}
                            </span>
                            <span class="ml-auto text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($article->published_at)->format('d/m/Y') }}
                            </span>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors duration-300">
                            <a href="{{ route('articles.show', $article->slug) }}">
                                {{ $article->title }}
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4">
                            {{ Str::limit(strip_tags($article->content), 100) }}
                        </p>
                        <a href="{{ route('articles.show', $article->slug) }}" class="text-primary-600 hover:text-primary-800 text-sm font-medium inline-flex items-center group">
                            Đọc tiếp <span aria-hidden="true" class="ml-1 group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Danh mục bài viết -->
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 relative pl-4 before:content-[''] before:absolute before:left-0 before:top-0 before:h-full before:w-1 before:bg-gradient-to-b before:from-primary-500 before:to-primary-300 before:rounded-full">
            Danh mục
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($categories as $category)
                <a href="{{ route('articles.category', $category->slug) }}" class="group">
                    <div class="bg-white rounded-xl shadow p-5 hover:shadow-md transition-all duration-300 text-center group-hover:bg-gradient-to-br group-hover:from-primary-50 group-hover:to-white transform group-hover:-translate-y-1">
                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-300">{{ $category->name }}</h3>
                        <p class="text-gray-500 text-sm mt-1">{{ $category->articles_count }} bài viết</p>
                        @if($category->subcategories_count > 0)
                            <p class="text-gray-500 text-xs mt-1">{{ $category->subcategories_count }} danh mục con</p>
                        @endif
                        <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <span class="inline-flex items-center text-xs font-medium text-primary-600">
                                Xem danh mục
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection 