@extends('layouts.admin')

@section('title', 'Chỉnh sửa bài viết Facebook')

@section('content')
<x-admin.page-header 
    title="Chỉnh sửa bài viết Facebook"
    description="Chỉnh sửa thông tin bài viết Facebook"
>
    <x-slot name="actions">
        <x-admin.button 
            color="secondary" 
            href="{{ route('admin.facebook-posts.index') }}"
            icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>'
        >
            Quay lại
        </x-admin.button>
    </x-slot>
</x-admin.page-header>

@if (session('success'))
    <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error" class="mb-6">{{ session('error') }}</x-alert>
@endif

<x-admin.card>
    <form action="{{ route('admin.facebook-posts.update', $facebookPost) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="space-y-6">
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Nội dung bài viết</label>
                <textarea id="content" name="content" rows="10" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('content', $facebookPost->content) }}</textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select id="status" name="status" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    <option value="pending" {{ old('status', $facebookPost->status) === 'pending' ? 'selected' : '' }}>Đang chờ</option>
                    <option value="processed" {{ old('status', $facebookPost->status) === 'processed' ? 'selected' : '' }}>Đã xử lý</option>
                    <option value="failed" {{ old('status', $facebookPost->status) === 'failed' ? 'selected' : '' }}>Thất bại</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex justify-end">
                <x-admin.button type="submit" color="primary">
                    Cập nhật bài viết
                </x-admin.button>
            </div>
        </div>
    </form>
</x-admin.card>
@endsection 