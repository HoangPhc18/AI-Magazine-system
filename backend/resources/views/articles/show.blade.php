@extends('layouts.app')

@section('title', $article->meta_title ?? $article->title)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow-xl rounded-xl overflow-hidden transition-all duration-500">
        <!-- Featured Image -->
        @if($article->featuredImage)
            <div class="relative aspect-video overflow-hidden">
                <img src="{{ $article->featuredImage->url }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                <div class="absolute bottom-0 left-0 w-full h-1/4 bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
        @elseif($article->featured_image)
            <div class="relative aspect-video overflow-hidden">
                <img src="{{ asset('storage/' . $article->featured_image) }}" alt="{{ $article->title }}" class="w-full h-full object-cover">
                <div class="absolute bottom-0 left-0 w-full h-1/4 bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
        @endif

        <div class="p-6 md:p-10">
            <!-- Category and Date -->
            <div class="flex items-center mb-5 space-x-3">
                <a href="{{ route('articles.category', $article->category->slug) }}" class="inline-block bg-primary-100 text-primary-800 rounded-full px-3 py-1 text-xs font-semibold hover:bg-primary-200 transition-colors">
                    {{ $article->category->name }}
                </a>
                <span class="text-gray-500 text-sm flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ \Carbon\Carbon::parse($article->published_at)->format('d/m/Y') }}
                </span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-5 leading-tight">{{ $article->title }}</h1>
            
            <!-- Author if available -->
            @if($article->user)
            <div class="flex items-center mb-8 p-4 bg-gray-50 rounded-xl">
                <div class="w-12 h-12 bg-gradient-to-r from-primary-600 to-primary-500 rounded-full flex items-center justify-center text-white mr-4">
                    <span class="font-semibold">{{ substr($article->user->name, 0, 1) }}</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $article->user->name }}</p>
                    <p class="text-xs text-gray-500">Tác giả</p>
                </div>
            </div>
            @endif

            <!-- Article Content -->
            <div class="prose prose-lg max-w-none text-gray-700 mb-10 article-content">
                <div class="article-container">
                    {!! $article->processed_content !!}
                </div>
            </div>

            <!-- Social Share -->
            <div class="border-t border-gray-100 pt-8 mt-8">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Chia sẻ bài viết</h3>
                <div class="flex space-x-3">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" class="text-white bg-[#3b5998] hover:bg-[#324b80] transition-colors p-2 rounded-full">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($article->title) }}&url={{ urlencode(request()->url()) }}" target="_blank" class="text-white bg-[#1DA1F2] hover:bg-[#0c85d0] transition-colors p-2 rounded-full">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.531A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                        </svg>
                    </a>
                    <a href="mailto:?subject={{ urlencode($article->title) }}&body={{ urlencode(request()->url()) }}" class="text-white bg-gray-600 hover:bg-gray-700 transition-colors p-2 rounded-full">
                        <span class="sr-only">Email</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Articles -->
    @if($relatedArticles->count() > 0)
    <div class="mt-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-8 relative pl-4 before:content-[''] before:absolute before:left-0 before:top-0 before:h-full before:w-1 before:bg-gradient-to-b before:from-primary-500 before:to-primary-300 before:rounded-full">
            Bài viết liên quan
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($relatedArticles as $related)
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 hover-lift group">
                <div class="h-40 overflow-hidden">
                    @if($related->featuredImage)
                        <img src="{{ $related->featuredImage->url }}" alt="{{ $related->title }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-all duration-500">
                    @elseif($related->featured_image)
                        <img src="{{ asset('storage/' . $related->featured_image) }}" alt="{{ $related->title }}" class="w-full h-full object-cover transform group-hover:scale-105 transition-all duration-500">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center group-hover:from-primary-50 group-hover:to-gray-100 transition-colors duration-500">
                            <svg class="w-10 h-10 text-gray-400 group-hover:text-primary-400 transition-colors duration-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <a href="{{ route('articles.category', $related->category->slug) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                        {{ $related->category->name }}
                    </a>
                    <h3 class="text-md font-semibold text-gray-900 mt-1 mb-1 group-hover:text-primary-600 transition-colors duration-300 line-clamp-2">
                        <a href="{{ route('articles.show', $related->slug) }}">
                            {{ $related->title }}
                        </a>
                    </h3>
                    <span class="text-xs text-gray-500 flex items-center mt-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ \Carbon\Carbon::parse($related->published_at)->format('d/m/Y') }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<style>
    /* Container chính cho nội dung bài viết */
    .article-content .article-container {
        @apply w-full mx-auto overflow-hidden;
    }
    .article-content h2 {
        @apply text-2xl font-bold text-gray-800 my-6;
    }
    .article-content h3 {
        @apply text-xl font-bold text-gray-800 my-5;
    }
    .article-content h4 {
        @apply text-lg font-bold text-gray-800 my-4;
    }
    .article-content p {
        @apply mb-6 leading-relaxed text-gray-700;
    }
    .article-content p + p {
        @apply mt-4;
    }
    .article-content br {
        @apply block content-[''] h-4;
    }
    .article-content a {
        @apply text-primary-600 hover:text-primary-800 transition-colors;
    }
    .article-content ul {
        @apply list-disc pl-5 mb-4 space-y-2;
    }
    .article-content ol {
        @apply list-decimal pl-5 mb-4 space-y-2;
    }
    .article-content .image-container {
        @apply my-6 mx-auto text-center max-w-full overflow-hidden;
    }
    .article-content img {
        @apply rounded-lg my-6 mx-auto max-w-full h-[400px] inline-block object-cover;
    }
    .article-content .img-fluid {
        @apply rounded-lg my-6 mx-auto max-w-full h-[400px] shadow-md hover:shadow-lg transition-shadow duration-300 block object-cover;
    }
    /* Xử lý hình ảnh full-width */
    .article-content .full-width {
        @apply w-full max-w-full;
    }
    .article-content img.full-width {
        @apply w-full object-cover object-center h-[400px];
    }
    .article-content .image-wrapper.full-width {
        @apply w-full my-6 max-w-full;
    }
    .article-content .image-wrapper.full-width img {
        @apply w-full m-0 h-[400px];
    }
    .article-content figure {
        @apply my-8 mx-auto text-center max-w-3xl;
    }
    .article-content figure img {
        @apply inline-block mx-auto rounded-lg shadow-md max-w-full h-[400px] object-cover;
    }
    .article-content figure figcaption {
        @apply text-sm text-gray-600 mt-2 italic;
    }
    .article-content .image-grid {
        @apply grid grid-cols-1 md:grid-cols-2 gap-4 my-6;
    }
    .article-content .image-grid img {
        @apply my-0 w-full h-full object-cover;
    }
    .article-content img.small {
        @apply max-w-xs;
    }
    .article-content img.medium {
        @apply max-w-md;
    }
    .article-content img.float-left {
        @apply float-left mr-4 mb-4;
    }
    .article-content img.float-right {
        @apply float-right ml-4 mb-4;
    }
    .article-content blockquote {
        @apply border-l-4 border-primary-300 pl-4 italic my-6 text-gray-600 bg-gray-50 py-2 pr-2 rounded-r;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .article-content p + p > img {
        @apply mt-2;
    }
    .article-content p:has(img) + p:has(img) {
        @apply mt-1;
    }
    .article-content p:has(img) {
        @apply text-center w-full mx-auto;
    }
    .article-content img[style*="float:left"],
    .article-content img[style*="float: left"] {
        @apply float-left mr-4 mb-4;
    }
    .article-content img[style*="float:right"],
    .article-content img[style*="float: right"] {
        @apply float-right ml-4 mb-4;
    }
    .article-content .image-wrapper {
        @apply block w-full text-center my-6 overflow-hidden;
    }
    @media (max-width: 640px) {
        .article-content img.float-left,
        .article-content img.float-right,
        .article-content img[style*="float:left"],
        .article-content img[style*="float:right"],
        .article-content img[style*="float: left"],
        .article-content img[style*="float: right"] {
            @apply float-none mx-auto my-4 block;
        }
    }
</style>
@endsection 