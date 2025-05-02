@extends('layouts.admin')

@section('title', 'Cấu hình giao diện website')

@section('content')
<x-admin.page-header 
    title="Cấu hình giao diện website"
    description="Quản lý các thiết lập về giao diện người dùng như màu sắc, font chữ, bố cục."
/>

<x-admin.card>
    <form method="POST" action="{{ route('admin.website-config.ui.update') }}">
        @csrf

        <div class="space-y-6">
            <!-- Màu sắc -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Màu sắc</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Thiết lập màu sắc chính cho website.
                </p>
                
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700">Màu chính</label>
                        <div class="mt-1 flex items-center">
                            <input type="color" name="primary_color" id="primary_color" 
                                   value="{{ old('primary_color', $settings['primary_color'] ?? '#0ea5e9') }}"
                                   class="h-10 w-10 rounded-md border border-gray-300 p-1">
                            <input type="text" name="primary_color_text" id="primary_color_text" 
                                   value="{{ old('primary_color', $settings['primary_color'] ?? '#0ea5e9') }}"
                                   class="ml-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                   oninput="document.getElementById('primary_color').value = this.value">
                        </div>
                        @error('primary_color')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="secondary_color" class="block text-sm font-medium text-gray-700">Màu phụ</label>
                        <div class="mt-1 flex items-center">
                            <input type="color" name="secondary_color" id="secondary_color" 
                                   value="{{ old('secondary_color', $settings['secondary_color'] ?? '#64748b') }}"
                                   class="h-10 w-10 rounded-md border border-gray-300 p-1">
                            <input type="text" name="secondary_color_text" id="secondary_color_text" 
                                   value="{{ old('secondary_color', $settings['secondary_color'] ?? '#64748b') }}"
                                   class="ml-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                   oninput="document.getElementById('secondary_color').value = this.value">
                        </div>
                        @error('secondary_color')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="accent_color" class="block text-sm font-medium text-gray-700">Màu nhấn</label>
                        <div class="mt-1 flex items-center">
                            <input type="color" name="accent_color" id="accent_color" 
                                   value="{{ old('accent_color', $settings['accent_color'] ?? '#f97316') }}"
                                   class="h-10 w-10 rounded-md border border-gray-300 p-1">
                            <input type="text" name="accent_color_text" id="accent_color_text" 
                                   value="{{ old('accent_color', $settings['accent_color'] ?? '#f97316') }}"
                                   class="ml-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                   oninput="document.getElementById('accent_color').value = this.value">
                        </div>
                        @error('accent_color')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="text_color" class="block text-sm font-medium text-gray-700">Màu chữ</label>
                    <div class="mt-1 flex items-center">
                        <input type="color" name="text_color" id="text_color" 
                               value="{{ old('text_color', $settings['text_color'] ?? '#1e293b') }}"
                               class="h-10 w-10 rounded-md border border-gray-300 p-1">
                        <input type="text" name="text_color_text" id="text_color_text" 
                               value="{{ old('text_color', $settings['text_color'] ?? '#1e293b') }}"
                               class="ml-2 block w-40 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               oninput="document.getElementById('text_color').value = this.value">
                    </div>
                    @error('text_color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Font chữ -->
            <div class="pt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Font chữ</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Thiết lập font chữ cho website.
                </p>
                
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="heading_font" class="block text-sm font-medium text-gray-700">Font cho tiêu đề</label>
                        <input type="text" name="heading_font" id="heading_font" 
                               value="{{ old('heading_font', $settings['heading_font'] ?? "'Plus Jakarta Sans', sans-serif") }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Ví dụ: 'Roboto', sans-serif</p>
                        @error('heading_font')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="body_font" class="block text-sm font-medium text-gray-700">Font cho nội dung</label>
                        <input type="text" name="body_font" id="body_font" 
                               value="{{ old('body_font', $settings['body_font'] ?? "'Plus Jakarta Sans', sans-serif") }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Ví dụ: 'Open Sans', sans-serif</p>
                        @error('body_font')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Cấu hình hiển thị -->
            <div class="pt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Cấu hình hiển thị</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Thiết lập các tùy chọn hiển thị cho website.
                </p>
                
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="articles_per_page" class="block text-sm font-medium text-gray-700">Số bài viết mỗi trang</label>
                        <input type="number" name="articles_per_page" id="articles_per_page" min="4" max="60"
                               value="{{ old('articles_per_page', $settings['articles_per_page'] ?? 12) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @error('articles_per_page')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="featured_articles_count" class="block text-sm font-medium text-gray-700">Số bài viết nổi bật</label>
                        <input type="number" name="featured_articles_count" id="featured_articles_count" min="1" max="12"
                               value="{{ old('featured_articles_count', $settings['featured_articles_count'] ?? 6) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @error('featured_articles_count')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-4 space-y-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="show_search_bar" id="show_search_bar" 
                                   {{ old('show_search_bar', $settings['show_search_bar'] ?? true) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="show_search_bar" class="font-medium text-gray-700">Hiển thị thanh tìm kiếm</label>
                            <p class="text-gray-500">Hiển thị thanh tìm kiếm trên thanh điều hướng.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="show_back_to_top" id="show_back_to_top" 
                                   {{ old('show_back_to_top', $settings['show_back_to_top'] ?? true) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="show_back_to_top" class="font-medium text-gray-700">Hiển thị nút "Về đầu trang"</label>
                            <p class="text-gray-500">Hiển thị nút "Về đầu trang" khi cuộn xuống dưới.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tạo file CSS tùy chỉnh -->
            <div class="pt-6 border-t border-gray-200">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="generate_css" id="generate_css" 
                               class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="generate_css" class="font-medium text-gray-700">Tạo file CSS tùy chỉnh</label>
                        <p class="text-gray-500">Tạo file CSS với các biến tùy chỉnh dựa trên cấu hình trên.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-5">
                <x-admin.button color="light" href="{{ route('admin.dashboard') }}">Hủy</x-admin.button>
                <x-admin.button type="submit" color="primary" class="ml-3">Lưu thay đổi</x-admin.button>
            </div>
        </div>
    </form>
</x-admin.card>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Liên kết color picker với input text
    const colorPairs = [
        ['primary_color', 'primary_color_text'],
        ['secondary_color', 'secondary_color_text'],
        ['accent_color', 'accent_color_text'],
        ['text_color', 'text_color_text']
    ];
    
    colorPairs.forEach(pair => {
        const colorInput = document.getElementById(pair[0]);
        const textInput = document.getElementById(pair[1]);
        
        colorInput.addEventListener('input', function() {
            textInput.value = this.value;
        });
    });
});
</script>
@endsection 