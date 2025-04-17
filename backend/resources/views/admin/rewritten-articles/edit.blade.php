@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Chỉnh sửa bài viết</h1>
            <a href="{{ route('admin.rewritten-articles.index') }}" class="text-blue-600 hover:text-blue-900">
                Quay lại danh sách
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.rewritten-articles.update', $rewrittenArticle) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Tiêu đề</label>
                <input type="text" name="title" id="title" value="{{ old('title', $rewrittenArticle->title) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700">Danh mục</label>
                <select name="category_id" id="category_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Chọn danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $rewrittenArticle->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="content" class="block text-sm font-medium text-gray-700">Nội dung</label>
                <textarea name="content" id="content" rows="10" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content', $rewrittenArticle->content) }}</textarea>
            </div>

            <div>
                <label for="meta_title" class="block text-sm font-medium text-gray-700">Meta Title</label>
                <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $rewrittenArticle->meta_title) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                <textarea name="meta_description" id="meta_description" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('meta_description', $rewrittenArticle->meta_description) }}</textarea>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                <select name="status" id="status" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="pending" {{ old('status', $rewrittenArticle->status) === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                    <option value="approved" {{ old('status', $rewrittenArticle->status) === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="rejected" {{ old('status', $rewrittenArticle->status) === 'rejected' ? 'selected' : '' }}>Từ chối</option>
                </select>
            </div>

            <div>
                <label for="featured_image" class="block text-sm font-medium text-gray-700">Hình ảnh đại diện</label>
                @if($rewrittenArticle->featured_image)
                    <div class="mt-2 mb-4">
                        <img src="{{ $rewrittenArticle->featured_image_url }}" alt="Current featured image" class="h-32 w-auto">
                    </div>
                @endif
                <input type="file" name="featured_image" id="featured_image" accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Cập nhật bài viết
                </button>
            </div>
        </form>
    </div>
</div>
@endsection 