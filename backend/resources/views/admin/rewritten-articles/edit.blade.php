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
            
            <!-- Test hidden input to force subcategory value -->
            <input type="hidden" name="test_subcategory_id" value="1">
            <!-- End test input -->
            
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
                <label for="subcategory_id" class="block text-sm font-medium text-gray-700">Danh mục con</label>
                <select name="subcategory_id" id="subcategory_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Chọn danh mục con</option>
                    @if($rewrittenArticle->category && $rewrittenArticle->category->subcategories)
                        @foreach($rewrittenArticle->category->subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" {{ old('subcategory_id', $rewrittenArticle->subcategory_id) == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            @if($rewrittenArticle->originalArticle)
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Thông tin bài viết gốc</h3>
                <div class="text-sm">
                    <p class="text-gray-900 font-medium mb-1">{{ $rewrittenArticle->originalArticle->title }}</p>
                    
                    <div class="flex flex-col space-y-2 mt-2">
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.approved-articles.show', $rewrittenArticle->originalArticle) }}" 
                               class="text-blue-600 hover:text-blue-900 text-sm inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Xem bài viết gốc
                            </a>
                            
                            @php
                                // Tìm thông tin nguồn gốc từ bài viết gốc (nếu có)
                                $originalArticle = \App\Models\Article::find($rewrittenArticle->originalArticle->original_article_id);
                            @endphp
                            
                            @if($originalArticle && $originalArticle->source_url)
                            <a href="{{ $originalArticle->source_url }}" 
                               class="text-green-600 hover:text-green-900 text-sm inline-flex items-center"
                               target="_blank" rel="noopener noreferrer" title="Xem nguồn gốc bài viết">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </a>
                            @endif
                        </div>
                        
                        @if($originalArticle && $originalArticle->source_name)
                        <div class="text-gray-600">
                            <span class="font-medium">Nguồn:</span> {{ $originalArticle->source_name }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const form = document.querySelector('form');
    
    if (categorySelect && subcategorySelect) {
        // Store initial values
        const initialCategoryId = categorySelect.value;
        const initialSubcategoryId = subcategorySelect.value ? subcategorySelect.value.toString() : '';
        
        // Log initial values for debugging
        console.log('Initial values:', {
            categoryId: initialCategoryId,
            subcategoryId: initialSubcategoryId
        });
        
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
                                option.value = subcategory.id.toString();
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
                                    console.log('Selected initial subcategory:', initialSubcategoryId);
                                }
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching subcategories:', error));
            }
        });
        
        // Add a change event listener to the subcategory select
        subcategorySelect.addEventListener('change', function() {
            console.log('Subcategory changed to:', this.value);
            
            // Store the subcategory selection in a data attribute to ensure it's preserved
            this.setAttribute('data-selected-value', this.value);
            
            // Create or update a hidden input field with the current subcategory value
            let hiddenSubcategoryInput = document.getElementById('hidden_subcategory_id');
            if (!hiddenSubcategoryInput) {
                hiddenSubcategoryInput = document.createElement('input');
                hiddenSubcategoryInput.type = 'hidden';
                hiddenSubcategoryInput.id = 'hidden_subcategory_id'; 
                hiddenSubcategoryInput.name = 'explicit_subcategory_id';
                form.appendChild(hiddenSubcategoryInput);
            }
            hiddenSubcategoryInput.value = this.value;
        });
        
        // Ensure form includes the latest subcategory value when submitting
        if (form) {
            form.addEventListener('submit', function(e) {
                // Get the currently selected subcategory value
                const currentSubcategoryValue = subcategorySelect.value;
                
                // Create or update a hidden input field with the current subcategory value
                let hiddenSubcategoryInput = document.getElementById('hidden_subcategory_id');
                if (!hiddenSubcategoryInput) {
                    hiddenSubcategoryInput = document.createElement('input');
                    hiddenSubcategoryInput.type = 'hidden';
                    hiddenSubcategoryInput.id = 'hidden_subcategory_id'; 
                    hiddenSubcategoryInput.name = 'explicit_subcategory_id';
                    form.appendChild(hiddenSubcategoryInput);
                }
                
                // Set the value to the current selection
                hiddenSubcategoryInput.value = currentSubcategoryValue;
                console.log('Form submitted with subcategory_id:', currentSubcategoryValue);
            });
        }
        
        // Trigger change event on page load if category is selected
        if (initialCategoryId) {
            // Force reload of subcategories to ensure they match the category
            categorySelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endpush
@endsection 