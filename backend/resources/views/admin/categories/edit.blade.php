@extends('layouts.admin')

@section('title', 'Chỉnh sửa danh mục - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Chỉnh sửa danh mục</h2>
                <a href="{{ route('admin.categories.index') }}" class="text-gray-600 hover:text-gray-900">
                    Quay lại danh sách
                </a>
            </div>

            <form action="{{ route('admin.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Tên danh mục</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Đường dẫn sẽ được cập nhật tự động nếu bạn thay đổi tên.</p>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="current_slug" class="block text-sm font-medium text-gray-700">Đường dẫn hiện tại</label>
                        <div class="mt-1 block w-full p-2 bg-gray-100 border border-gray-300 rounded-md text-gray-500 text-sm">
                            {{ $category->slug }}
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Mô tả</label>
                        <textarea name="description" id="description" rows="3"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('description', $category->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Subcategories Section -->
                    <div>
                        <div class="flex justify-between items-center">
                            <label class="block text-sm font-medium text-gray-700">Danh mục con</label>
                            <button type="button" id="add-subcategory" 
                                class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Thêm danh mục con
                            </button>
                        </div>
                        
                        <div id="subcategories-container" class="mt-3 space-y-3">
                            @forelse($category->subcategories as $index => $subcategory)
                                <div class="subcategory-item bg-gray-50 p-3 rounded-md">
                                    <div class="flex items-center justify-between">
                                        <div class="w-full">
                                            <input type="hidden" name="subcategories[{{ $index }}][id]" value="{{ $subcategory->id }}">
                                            <input type="text" name="subcategories[{{ $index }}][name]" value="{{ $subcategory->name }}" required
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <button type="button" class="remove-subcategory ml-2 text-red-500 hover:text-red-700">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <textarea name="subcategories[{{ $index }}][description]" rows="2"
                                        class="mt-2 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ $subcategory->description }}</textarea>
                                </div>
                            @empty
                                <div class="subcategory-item bg-gray-50 p-3 rounded-md">
                                    <div class="flex items-center justify-between">
                                        <div class="w-full">
                                            <input type="text" name="subcategories[0][name]" placeholder="Tên danh mục con" required
                                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <button type="button" class="remove-subcategory ml-2 text-red-500 hover:text-red-700">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <textarea name="subcategories[0][description]" placeholder="Mô tả danh mục con (không bắt buộc)" rows="2"
                                        class="mt-2 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Hủy
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cập nhật danh mục
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add subcategory functionality
        const addButton = document.getElementById('add-subcategory');
        const container = document.getElementById('subcategories-container');
        let subcategoryIndex = {{ $category->subcategories->count() > 0 ? $category->subcategories->count() : 1 }};
        
        addButton.addEventListener('click', function() {
            const subcategoryTemplate = `
                <div class="subcategory-item bg-gray-50 p-3 rounded-md">
                    <div class="flex items-center justify-between">
                        <div class="w-full">
                            <input type="text" name="subcategories[${subcategoryIndex}][name]" placeholder="Tên danh mục con" required
                                class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <button type="button" class="remove-subcategory ml-2 text-red-500 hover:text-red-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    <textarea name="subcategories[${subcategoryIndex}][description]" placeholder="Mô tả danh mục con (không bắt buộc)" rows="2"
                        class="mt-2 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>
            `;
            
            // Insert template
            container.insertAdjacentHTML('beforeend', subcategoryTemplate);
            subcategoryIndex++;
            
            // Reassign event listeners
            attachRemoveListeners();
        });
        
        // Initial removal functionality
        function attachRemoveListeners() {
            const removeButtons = document.querySelectorAll('.remove-subcategory');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const subcategoryItem = this.closest('.subcategory-item');
                    
                    // If this is an existing subcategory, mark it for deletion
                    const subcategoryIdInput = subcategoryItem.querySelector('input[name*="[id]"]');
                    if (subcategoryIdInput) {
                        const subcategoryId = subcategoryIdInput.value;
                        const deletionInput = document.createElement('input');
                        deletionInput.type = 'hidden';
                        deletionInput.name = 'delete_subcategory_ids[]';
                        deletionInput.value = subcategoryId;
                        container.appendChild(deletionInput);
                    }
                    
                    subcategoryItem.remove();
                });
            });
        }
        
        attachRemoveListeners();
    });
</script>
@endpush
@endsection 