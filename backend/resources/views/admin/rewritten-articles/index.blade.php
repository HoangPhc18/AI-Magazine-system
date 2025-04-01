@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Quản lý bài viết AI</h1>
        <div class="flex space-x-4">
            <a href="{{ route('admin.rewritten-articles.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                Tạo bài viết mới
            </a>
            <a href="{{ route('admin.rewritten-articles.rewrite-form') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                Viết lại bằng AI
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if($rewrittenArticles->count() > 0)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiêu đề</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Danh mục</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tạo bởi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rewrittenArticles as $article)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $article->title }}</div>
                                <div class="text-sm text-gray-500">{{ $article->slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $article->category->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($article->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($article->status === 'approved') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800 @endif">
                                    @if($article->status === 'pending') Chờ duyệt
                                    @elseif($article->status === 'approved') Đã duyệt
                                    @else Từ chối @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $article->user->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $article->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.rewritten-articles.show', $article) }}" class="text-blue-600 hover:text-blue-900">Xem</a>
                                    <a href="{{ route('admin.rewritten-articles.edit', $article) }}" class="text-indigo-600 hover:text-indigo-900">Sửa</a>
                                    <a href="{{ route('admin.rewritten-articles.ai-rewrite-form', $article) }}" class="text-purple-600 hover:text-purple-900">AI Viết lại</a>
                                    @if($article->status === 'pending')
                                    <a href="{{ route('admin.rewritten-articles.approve', $article) }}" class="text-green-600 hover:text-green-900">Duyệt</a>
                                    <form action="{{ route('admin.rewritten-articles.reject', $article) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Bạn có chắc chắn muốn từ chối bài viết này?')">Từ chối</button>
                                    </form>
                                    @endif
                                    <form action="{{ route('admin.rewritten-articles.destroy', $article) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Không có bài viết nào chờ duyệt
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-info">
            Không có bài viết được viết lại nào.
        </div>
    @endif

    <div class="mt-4">
        {{ $rewrittenArticles->links() }}
    </div>
</div>
@endsection 