@extends('layouts.admin')

@section('title', 'Cấu hình thông tin chung của website')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Cấu hình thông tin chung của website</h1>
            <p class="mt-2 text-sm text-gray-700">
                Quản lý các thông tin cơ bản của website như tên, mô tả, thông tin liên hệ và hình ảnh.
            </p>
        </div>
    </div>

    <div class="mt-6 bg-white shadow-sm rounded-lg">
        @if (session('success'))
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            {{ session('success') }}
        </div>
        @endif

        @if (session('error'))
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            {{ session('error') }}
        </div>
        @endif

        <div class="p-6">
            <form method="POST" action="{{ route('admin.website-config.general.update') }}" enctype="multipart/form-data">
                @csrf

                <div class="space-y-6">
                    <!-- Site Name -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700">Tên website</label>
                            <input type="text" name="site_name" id="site_name" 
                                   value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('site_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="site_email" class="block text-sm font-medium text-gray-700">Email liên hệ</label>
                            <input type="email" name="site_email" id="site_email" 
                                   value="{{ old('site_email', $settings['site_email'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('site_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="site_description" class="block text-sm font-medium text-gray-700">Mô tả website</label>
                        <textarea name="site_description" id="site_description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
                        @error('site_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="site_phone" class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                            <input type="text" name="site_phone" id="site_phone" 
                                   value="{{ old('site_phone', $settings['site_phone'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('site_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="site_address" class="block text-sm font-medium text-gray-700">Địa chỉ</label>
                            <input type="text" name="site_address" id="site_address" 
                                   value="{{ old('site_address', $settings['site_address'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('site_address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Logo and Favicon -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                            
                            <!-- Logo preview -->
                            @if (!empty($settings['logo']))
                                <div class="mt-2 mb-4">
                                    <img src="{{ $settings['logo'] }}" alt="Current Logo" class="h-20 w-auto object-contain mb-2">
                                </div>
                            @endif
                            
                            <!-- Hidden field for logo media ID -->
                            <input type="hidden" name="logo_media_id" id="logo_media_id" value="{{ old('logo_media_id', $settings['logo_media_id'] ?? '') }}">
                            
                            <div class="flex space-x-4 mb-4">
                                <button type="button" id="select-logo-btn" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Chọn từ thư viện
                                </button>
                                
                                <span class="text-gray-500 self-center">hoặc</span>
                            </div>
                            
                            <label for="logo" class="block text-sm font-medium text-gray-700">
                                Tải lên logo mới
                            </label>
                            <input type="file" name="logo" id="logo" 
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                                   file:rounded-md file:border-0 file:text-sm file:font-semibold
                                   file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <p class="mt-1 text-sm text-gray-500">Kích thước tối ưu: 200x80 pixels. (PNG, JPG, JPEG)</p>
                            @error('logo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                            
                            <!-- Favicon preview -->
                            @if (!empty($settings['favicon']))
                                <div class="mt-2 mb-4">
                                    <img src="{{ $settings['favicon'] }}" alt="Current Favicon" class="h-10 w-auto object-contain mb-2">
                                </div>
                            @endif
                            
                            <!-- Hidden field for favicon media ID -->
                            <input type="hidden" name="favicon_media_id" id="favicon_media_id" value="{{ old('favicon_media_id', $settings['favicon_media_id'] ?? '') }}">
                            
                            <div class="flex space-x-4 mb-4">
                                <button type="button" id="select-favicon-btn" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Chọn từ thư viện
                                </button>
                                
                                <span class="text-gray-500 self-center">hoặc</span>
                            </div>
                            
                            <label for="favicon" class="block text-sm font-medium text-gray-700">
                                Tải lên favicon mới
                            </label>
                            <input type="file" name="favicon" id="favicon" 
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                                   file:rounded-md file:border-0 file:text-sm file:font-semibold
                                   file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <p class="mt-1 text-sm text-gray-500">Kích thước tối ưu: 32x32 pixels. (PNG, ICO)</p>
                            @error('favicon')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-5">
                        <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Hủy</a>
                        <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            Lưu thay đổi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize MediaSelector for logo
        const logoMediaSelector = new MediaSelector({
            type: 'image',
            insertCallback: function(media) {
                // Lưu ID vào input hidden
                document.getElementById('logo_media_id').value = media.id;
                
                // Hiển thị preview
                let logoContainer = document.getElementById('select-logo-btn').parentNode.parentNode;
                let previewContainer = logoContainer.querySelector('div');
                
                if (previewContainer) {
                    // Cập nhật hình ảnh hiện tại
                    previewContainer.innerHTML = `
                        <img src="${media.url}" alt="${media.name}" class="h-20 w-auto object-contain mb-2">
                    `;
                } else {
                    // Tạo mới container preview
                    previewContainer = document.createElement('div');
                    previewContainer.className = 'mt-2 mb-4';
                    previewContainer.innerHTML = `
                        <img src="${media.url}" alt="${media.name}" class="h-20 w-auto object-contain mb-2">
                    `;
                    logoContainer.insertBefore(previewContainer, document.getElementById('logo_media_id').nextSibling);
                }
            }
        });
        
        // Initialize MediaSelector for favicon
        const faviconMediaSelector = new MediaSelector({
            type: 'image',
            insertCallback: function(media) {
                // Lưu ID vào input hidden
                document.getElementById('favicon_media_id').value = media.id;
                
                // Hiển thị preview
                let faviconContainer = document.getElementById('select-favicon-btn').parentNode.parentNode;
                let previewContainer = faviconContainer.querySelector('div');
                
                if (previewContainer) {
                    // Cập nhật hình ảnh hiện tại
                    previewContainer.innerHTML = `
                        <img src="${media.url}" alt="${media.name}" class="h-10 w-auto object-contain mb-2">
                    `;
                } else {
                    // Tạo mới container preview
                    previewContainer = document.createElement('div');
                    previewContainer.className = 'mt-2 mb-4';
                    previewContainer.innerHTML = `
                        <img src="${media.url}" alt="${media.name}" class="h-10 w-auto object-contain mb-2">
                    `;
                    faviconContainer.insertBefore(previewContainer, document.getElementById('favicon_media_id').nextSibling);
                }
            }
        });
        
        // Bind buttons
        document.getElementById('select-logo-btn').addEventListener('click', function() {
            logoMediaSelector.open();
        });
        
        document.getElementById('select-favicon-btn').addEventListener('click', function() {
            faviconMediaSelector.open();
        });
    });
</script>
@endpush
@endsection 