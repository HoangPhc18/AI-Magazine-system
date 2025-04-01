@extends('layouts.app')

@section('title', 'Home - Magazine AI System')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Latest Articles</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($articles as $article)
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    @if($article['image'])
                        <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" class="w-full h-48 object-cover">
                    @endif
                    <div class="p-4">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">
                            <a href="{{ route('articles.show', $article['id']) }}" class="hover:text-blue-600">
                                {{ $article['title'] }}
                            </a>
                        </h2>
                        <p class="text-gray-600 mb-4">{{ Str::limit($article['content'], 150) }}</p>
                        <div class="flex justify-between items-center text-sm text-gray-500">
                            <span>{{ $article['category']['name'] ?? 'Uncategorized' }}</span>
                            <span>{{ \Carbon\Carbon::parse($article['created_at'])->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection 