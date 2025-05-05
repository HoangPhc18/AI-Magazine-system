@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Tạo bài viết mới</h1>
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

        <form action="{{ route('admin.rewritten-articles.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Tiêu đề</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700">Danh mục</label>
                <select name="category_id" id="category_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Chọn danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="subcategory_id" class="block text-sm font-medium text-gray-700">Danh mục con</label>
                <select name="subcategory_id" id="subcategory_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Chọn danh mục con</option>
                </select>
            </div>

            <div>
                <label for="original_article_id" class="block text-sm font-medium text-gray-700">Bài viết gốc (tùy chọn)</label>
                <select name="original_article_id" id="original_article_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Chọn bài viết gốc</option>
                    @foreach($originalArticles as $article)
                        <option value="{{ $article->id }}" {{ old('original_article_id') == $article->id ? 'selected' : '' }}>
                            {{ $article->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="content" class="block text-sm font-medium text-gray-700">Nội dung</label>
                <textarea name="content" id="content" rows="10" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content') }}</textarea>
            </div>

            <div>
                <label for="meta_title" class="block text-sm font-medium text-gray-700">Meta Title</label>
                <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                <textarea name="meta_description" id="meta_description" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('meta_description') }}</textarea>
            </div>

            <div>
                <label for="featured_image" class="block text-sm font-medium text-gray-700">Hình ảnh đại diện</label>
                <input type="file" name="featured_image" id="featured_image" accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Tạo bài viết
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    
    if (categorySelect && subcategorySelect) {
        // Store initial values
        const initialCategoryId = categorySelect.value;
        const initialSubcategoryId = subcategorySelect.value;
        
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            
            // Clear current options and reset value
            subcategorySelect.innerHTML = '<option value="">Chọn danh mục con</option>';
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