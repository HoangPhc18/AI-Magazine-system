@extends('layouts.admin')

@section('title', 'Quản lý bài viết - Hệ thống Magazine AI')

@section('content')
<div class="py-12">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Quản lý bài viết
            </h3>
            <a href="{{ route('admin.articles.create') }}" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Thêm bài viết mới
            </a>
        </div>
        
        <div class="border-t border-gray-200 px-4 py-3">
            <form action="{{ route('admin.articles.index') }}" method="GET" class="flex flex-wrap items-end space-x-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Tìm kiếm</label>
                    <div class="mt-1">
                        <input type="text" name="search" id="search" 
                            class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md"
                            value="{{ request('search') }}" placeholder="Tìm theo tiêu đề...">
                    </div>
                </div>
                
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Danh mục</label>
                    <div class="mt-1">
                        <select id="category" name="category" 
                            class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <option value="">Tất cả danh mục</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                    <div class="mt-1">
                        <select id="status" name="status" 
                            class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <option value="">Tất cả trạng thái</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Lọc
                    </button>
                    <a href="{{ route('admin.articles.index') }}" 
                        class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Đặt lại
                    </a>
                </div>
            </form>
        </div>
        
        <div class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tiêu đề
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Danh mục
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Trạng thái
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ngày tạo
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($articles as $article)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 truncate max-w-xs">
                                    {{ $article->title }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    @if($article->category)
                                        {{ $article->category->name }}
                                    @else
                                        <span class="text-red-500">Chưa phân loại</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $article->status === 'approved' ? 'bg-green-100 text-green-800' : 
                                       ($article->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $article->status === 'approved' ? 'Đã duyệt' : ($article->status === 'pending' ? 'Chờ duyệt' : 'Nháp') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $article->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.articles.edit', $article->id) }}" class="text-green-600 hover:text-green-900 mr-4">Sửa</a>
                                
                                <a href="{{ route('admin.articles.show', $article->id) }}" class="text-blue-600 hover:text-blue-900 mr-4">Xem</a>
                                
                                <form action="{{ route('admin.articles.destroy', $article->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" 
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                        Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach

                    @if(count($articles) === 0)
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                Không tìm thấy bài viết nào
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        @if(isset($meta))
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    @if($meta['current_page'] > 1)
                        <a href="?page={{ $meta['current_page'] - 1 }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Trước
                        </a>
                    @endif
                    @if($meta['current_page'] < $meta['last_page'])
                        <a href="?page={{ $meta['current_page'] + 1 }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Sau
                        </a>
                    @endif
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Hiển thị
                            <span class="font-medium">{{ $meta['from'] ?? 0 }}</span>
                            đến
                            <span class="font-medium">{{ $meta['to'] ?? 0 }}</span>
                            trong số
                            <span class="font-medium">{{ $meta['total'] }}</span>
                            kết quả
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            @if($meta['current_page'] > 1)
                                <a href="?page={{ $meta['current_page'] - 1 }}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Trước
                                </a>
                            @endif
                            @for($i = 1; $i <= $meta['last_page']; $i++)
                                <a href="?page={{ $i }}" 
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                                        {{ $i === $meta['current_page'] ? 'z-10 bg-green-50 border-green-500 text-green-600' : 'text-gray-700 hover:bg-gray-50' }}">
                                    {{ $i }}
                                </a>
                            @endfor
                            @if($meta['current_page'] < $meta['last_page'])
                                <a href="?page={{ $meta['current_page'] + 1 }}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Sau
                                </a>
                            @endif
                        </nav>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection 