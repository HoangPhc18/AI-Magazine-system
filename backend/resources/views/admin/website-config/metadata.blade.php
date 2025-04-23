@extends('layouts.admin')

@section('title', 'Cấu hình metadata của website')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Cấu hình metadata của website</h1>
            <p class="mt-2 text-sm text-gray-700">
                Quản lý các thông tin về metadata và thẻ head của website.
            </p>
        </div>
    </div>

    <div class="mt-6 bg-white shadow-sm rounded-lg">
        @if (session('success'))
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            {{ session('success') }}
        </div>
        @endif

        <div class="p-6">
            <form method="POST" action="{{ route('admin.website-config.metadata.update') }}" enctype="multipart/form-data">
                @csrf

                <div class="space-y-6">
                    <!-- Cấu hình tiêu đề -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Cấu hình tiêu đề</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Thiết lập định dạng tiêu đề cho website.
                        </p>
                        
                        <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="head_title_format" class="block text-sm font-medium text-gray-700">Định dạng tiêu đề</label>
                                <input type="text" name="head_title_format" id="head_title_format" 
                                       value="{{ old('head_title_format', $settings['head_title_format'] ?? '%page_title% %separator% %site_name%') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <p class="mt-1 text-xs text-gray-500">Ví dụ: %page_title% %separator% %site_name%</p>
                                @error('head_title_format')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="head_separator" class="block text-sm font-medium text-gray-700">Ký tự phân cách</label>
                                <input type="text" name="head_separator" id="head_separator" 
                                       value="{{ old('head_separator', $settings['head_separator'] ?? '-') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <p class="mt-1 text-xs text-gray-500">Ví dụ: -, |, »</p>
                                @error('head_separator')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Cấu hình cơ bản -->
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Cấu hình cơ bản</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Thiết lập các thẻ meta cơ bản.
                        </p>
                        
                        <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="charset" class="block text-sm font-medium text-gray-700">Charset</label>
                                <input type="text" name="charset" id="charset" 
                                       value="{{ old('charset', $settings['charset'] ?? 'UTF-8') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @error('charset')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="viewport" class="block text-sm font-medium text-gray-700">Viewport</label>
                                <input type="text" name="viewport" id="viewport" 
                                       value="{{ old('viewport', $settings['viewport'] ?? 'width=device-width, initial-scale=1') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @error('viewport')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="author" class="block text-sm font-medium text-gray-700">Tác giả</label>
                                <input type="text" name="author" id="author" 
                                       value="{{ old('author', $settings['author'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @error('author')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="copyright" class="block text-sm font-medium text-gray-700">Bản quyền</label>
                                <input type="text" name="copyright" id="copyright" 
                                       value="{{ old('copyright', $settings['copyright'] ?? '© ' . date('Y') . ' Magazine AI System') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @error('copyright')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Open Graph / Chia sẻ mạng xã hội -->
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Open Graph / Chia sẻ mạng xã hội</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Thiết lập hình ảnh mặc định khi chia sẻ trang web lên mạng xã hội.
                        </p>
                        
                        <div class="mt-4">
                            <label for="default_og_image" class="block text-sm font-medium text-gray-700">Hình ảnh mặc định cho Open Graph</label>
                            @if (!empty($settings['default_og_image']))
                                <div class="mt-2 mb-4">
                                    <img src="{{ $settings['default_og_image'] }}" alt="Current OG Image" class="h-40 w-auto object-contain">
                                </div>
                            @endif
                            <input type="file" name="default_og_image" id="default_og_image" 
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                                   file:rounded-md file:border-0 file:text-sm file:font-semibold
                                   file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <p class="mt-1 text-sm text-gray-500">Kích thước tối ưu: 1200x630 pixels. (PNG, JPG, JPEG)</p>
                            @error('default_og_image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Mã tùy chỉnh -->
                    <div class="pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Thẻ meta tùy chỉnh</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Thêm thẻ meta tùy chỉnh vào head của trang web.
                        </p>
                        
                        <div class="mt-4">
                            <label for="extra_meta_tags" class="block text-sm font-medium text-gray-700">Các thẻ meta tùy chỉnh</label>
                            <textarea name="extra_meta_tags" id="extra_meta_tags" rows="4"
                                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono text-sm">{{ old('extra_meta_tags', $settings['extra_meta_tags'] ?? '') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Thêm các thẻ meta tùy chỉnh, mỗi thẻ một dòng. Ví dụ: &lt;meta name="theme-color" content="#ffffff"&gt;</p>
                            @error('extra_meta_tags')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <label for="custom_head_code" class="block text-sm font-medium text-gray-700">Mã tùy chỉnh trong head</label>
                            <textarea name="custom_head_code" id="custom_head_code" rows="6"
                                     class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono text-sm">{{ old('custom_head_code', $settings['custom_head_code'] ?? '') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Thêm mã JavaScript, CSS hoặc các thẻ tùy chỉnh khác vào phần head của trang web.</p>
                            @error('custom_head_code')
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