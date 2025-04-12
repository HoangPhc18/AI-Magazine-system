@extends('layouts.admin')

@section('title', $approvedArticle->title . ' - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $approvedArticle->title }}</h1>
            <div class="flex space-x-4">
                <a href="{{ route('admin.approved-articles.edit', $approvedArticle) }}" class="text-blue-600 hover:text-blue-900">
                    Chỉnh sửa
                </a>
                <a href="{{ route('admin.approved-articles.index') }}" class="text-gray-600 hover:text-gray-900">
                    Quay lại danh sách
                </a>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <div class="grid grid-cols-1 gap-y-2 sm:grid-cols-3 gap-x-4">
                        <div>
                            <span class="text-sm font-medium text-gray-500">Trạng thái</span>
                            <div class="mt-1">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($approvedArticle->status === 'published') bg-green-100 text-green-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    @if($approvedArticle->status === 'published') Đã xuất bản
                                    @else Chưa xuất bản @endif
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Danh mục</span>
                            <div class="mt-1 text-sm text-gray-900">{{ $approvedArticle->category->name }}</div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Tạo bởi</span>
                            <div class="mt-1 text-sm text-gray-900">{{ $approvedArticle->user->name }}</div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Ngày xuất bản</span>
                            <div class="mt-1 text-sm text-gray-900">{{ $approvedArticle->published_at ? $approvedArticle->published_at->format('d/m/Y') : 'Chưa xuất bản' }}</div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Ngày tạo</span>
                            <div class="mt-1 text-sm text-gray-900">{{ $approvedArticle->created_at->format('d/m/Y') }}</div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Nguồn gốc</span>
                            <div class="mt-1">
                                @if($approvedArticle->ai_generated)
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Tạo bởi AI</span>
                                @else
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Thủ công</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-span-3">
                            <span class="text-sm font-medium text-gray-500">Đường dẫn</span>
                            <div class="mt-1 text-sm text-gray-900">{{ $approvedArticle->slug }}</div>
                        </div>
                    </div>
                </div>

                @if($approvedArticle->featured_image)
                    <div class="mb-6">
                        <img src="{{ $approvedArticle->featured_image_url }}" alt="{{ $approvedArticle->title }}" class="max-w-full h-auto rounded-lg shadow">
                    </div>
                @endif

                <div class="prose max-w-none mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nội dung bài viết</h3>
                    <div class="bg-white p-4 border border-gray-200 rounded-md">
                        {!! $approvedArticle->formatted_content !!}
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-md mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Thông tin SEO</h3>
                    <div class="grid grid-cols-1 gap-y-2 sm:grid-cols-2 gap-x-4">
                        <div>
                            <span class="text-sm font-medium text-gray-500">Meta Title</span>
                            <div class="mt-1 text-sm text-gray-900">{{ $approvedArticle->meta_title ?: $approvedArticle->title }}</div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Meta Description</span>
                            <div class="mt-1 text-sm text-gray-900">{{ $approvedArticle->meta_description ?: 'Chưa thiết lập' }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-5 border-t border-gray-200">
                    <div>
                        @if($approvedArticle->status === 'published')
                            <form action="{{ route('admin.approved-articles.unpublish', $approvedArticle) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg-yellow-100 text-yellow-700 hover:bg-yellow-200 px-4 py-2 rounded-lg inline-flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Hủy xuất bản
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.approved-articles.publish', $approvedArticle) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg-green-100 text-green-700 hover:bg-green-200 px-4 py-2 rounded-lg inline-flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Xuất bản
                                </button>
                            </form>
                        @endif
                    </div>
                    <div>
                        <form action="{{ route('admin.approved-articles.destroy', $approvedArticle) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này? Hành động này không thể hoàn tác.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-100 text-red-700 hover:bg-red-200 px-4 py-2 rounded-lg inline-flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Xóa bài viết
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 