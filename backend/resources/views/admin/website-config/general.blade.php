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
        @if (session('status'))
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            {{ session('status') }}
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
                            <label for="logo" class="block text-sm font-medium text-gray-700">Logo</label>
                            @if (!empty($settings['logo']))
                                <div class="mt-2 mb-4">
                                    <img src="{{ $settings['logo'] }}" alt="Current Logo" class="h-20 w-auto object-contain">
                                </div>
                            @endif
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
                            <label for="favicon" class="block text-sm font-medium text-gray-700">Favicon</label>
                            @if (!empty($settings['favicon']))
                                <div class="mt-2 mb-4">
                                    <img src="{{ $settings['favicon'] }}" alt="Current Favicon" class="h-10 w-auto object-contain">
                                </div>
                            @endif
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
@endsection 