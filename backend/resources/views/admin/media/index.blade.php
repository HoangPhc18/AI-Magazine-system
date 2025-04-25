@extends('layouts.admin')

@section('title', 'Quản lý media - Hệ thống Magazine AI')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Quản lý media
                </h3>
                <a href="{{ route('admin.media.create') }}" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Tải lên media
                </a>
            </div>
            
            <div class="border-t border-gray-200 px-4 py-3">
                <form action="{{ route('admin.media.index') }}" method="GET" class="flex flex-wrap items-end space-x-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Tìm kiếm</label>
                        <div class="mt-1">
                            <input type="text" name="search" id="search" 
                                class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                value="{{ request('search') }}" placeholder="Tìm theo tên...">
                        </div>
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Loại file</label>
                        <div class="mt-1">
                            <select id="type" name="type" 
                                class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="">Tất cả loại</option>
                                <option value="image" {{ request('type') == 'image' ? 'selected' : '' }}>Hình ảnh</option>
                                <option value="document" {{ request('type') == 'document' ? 'selected' : '' }}>Tài liệu</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Lọc
                        </button>
                        <a href="{{ route('admin.media.index') }}" 
                            class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Đặt lại
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="border-t border-gray-200 p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($media as $item)
                        <div class="relative group border rounded overflow-hidden shadow">
                            @if($item->is_image)
                                <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                                    <img src="{{ $item->url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                </div>
                            @else
                                <div class="aspect-w-1 aspect-h-1 bg-gray-100 flex items-center justify-center">
                                    <svg class="h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            @endif
                            
                            <div class="px-2 py-1 text-xs truncate" title="{{ $item->name }}">
                                {{ $item->name }}
                            </div>
                            
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="flex space-x-2">
                                    <a href="{{ $item->url }}" target="_blank" class="p-1 bg-blue-500 rounded text-white" title="Xem">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    
                                    <a href="{{ route('admin.media.show', $item->id) }}" class="p-1 bg-green-500 rounded text-white" title="Chi tiết">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </a>
                                    
                                    <form action="{{ route('admin.media.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa media này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 bg-red-500 rounded text-white" title="Xóa">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if(count($media) === 0)
                        <div class="col-span-full text-center py-8 text-gray-500">
                            Không tìm thấy media nào
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="bg-white px-4 py-3 border-t border-gray-200">
                {{ $media->links() }}
            </div>
        </div>
    </div>
</div>
@endsection 