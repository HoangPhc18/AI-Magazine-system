@extends('layouts.admin')

@section('title', 'Cấu hình SEO')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Cấu hình SEO</h1>
            <p class="mt-2 text-sm text-gray-700">
                Quản lý các thiết lập liên quan đến tối ưu hóa cho công cụ tìm kiếm (SEO).
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
            <form method="POST" action="{{ route('admin.website-config.seo.update') }}">
                @csrf

                <div class="space-y-6">
                    <!-- Meta Information -->
                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-gray-700">Tiêu đề meta mặc định</label>
                        <input type="text" name="meta_title" id="meta_title" 
                               value="{{ old('meta_title', $settings['meta_title'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Tiêu đề hiển thị trên trình duyệt và kết quả tìm kiếm (tối đa 60 ký tự)</p>
                        @error('meta_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-gray-700">Mô tả meta mặc định</label>
                        <textarea name="meta_description" id="meta_description" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Mô tả hiển thị trong kết quả tìm kiếm (tối đa 160 ký tự)</p>
                        @error('meta_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="meta_keywords" class="block text-sm font-medium text-gray-700">Từ khóa meta mặc định</label>
                        <textarea name="meta_keywords" id="meta_keywords" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('meta_keywords', $settings['meta_keywords'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Các từ khóa ngăn cách bởi dấu phẩy. Ví dụ: tin tức, magazine, giải trí</p>
                        @error('meta_keywords')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Google Analytics -->
                    <div>
                        <label for="google_analytics_id" class="block text-sm font-medium text-gray-700">ID Google Analytics</label>
                        <input type="text" name="google_analytics_id" id="google_analytics_id" 
                               value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}"
                               placeholder="G-XXXXXXXXXX hoặc UA-XXXXXXXXX-X"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Mã ID theo dõi của Google Analytics</p>
                        @error('google_analytics_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Robots.txt -->
                    <div>
                        <label for="robots_txt" class="block text-sm font-medium text-gray-700">Nội dung robots.txt</label>
                        <textarea name="robots_txt" id="robots_txt" rows="6"
                                  class="mt-1 block w-full font-mono text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('robots_txt', $settings['robots_txt'] ?? "User-agent: *\nAllow: /") }}</textarea>
                        @error('robots_txt')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-2">
                            <div class="relative flex items-start">
                                <div class="flex h-5 items-center">
                                    <input id="generate_robots" name="generate_robots" type="checkbox" value="1"
                                           class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="generate_robots" class="font-medium text-gray-700">Tạo file robots.txt</label>
                                    <p class="text-gray-500">Tạo file robots.txt từ nội dung trên</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sitemap -->
                    <div>
                        <div class="relative flex items-start">
                            <div class="flex h-5 items-center">
                                <input id="generate_sitemap" name="generate_sitemap" type="checkbox" value="1"
                                       {{ old('generate_sitemap', $settings['generate_sitemap'] ?? false) ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="generate_sitemap" class="font-medium text-gray-700">Tự động tạo sitemap.xml</label>
                                <p class="text-gray-500">Tự động tạo sitemap.xml khi có nội dung mới</p>
                            </div>
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