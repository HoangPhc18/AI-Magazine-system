@extends('layouts.admin')

@section('title', 'Tải lên media - Hệ thống Magazine AI')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Tải lên media
                </h3>
                <a href="{{ route('admin.media.index') }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="mr-2 -ml-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Quay lại
                </a>
            </div>
            
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Tên (tùy chọn)</label>
                        <div class="mt-1">
                            <input type="text" name="name" id="name" 
                                class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                placeholder="Nếu không nhập, tên file sẽ được sử dụng">
                            <p class="mt-1 text-sm text-gray-500">
                                Nếu không nhập tên, tên file sẽ được sử dụng làm tên mặc định.
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <label for="files" class="block text-sm font-medium text-gray-700">Chọn file</label>
                        <div class="mt-1" id="upload-container">
                            <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center" id="dropzone">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="files" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                            <span>Tải lên file</span>
                                            <input id="files" name="files[]" type="file" class="sr-only" multiple onchange="showPreview(this)">
                                        </label>
                                        <p class="pl-1">hoặc kéo thả vào đây</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        Hỗ trợ PNG, JPG, GIF, PDF, DOC, DOCX, XLS, XLSX tối đa 10MB
                                    </p>
                                </div>
                            </div>
                            
                            <div id="preview-container" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                <!-- Preview files will be displayed here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Tải lên
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showPreview(input) {
        const previewContainer = document.getElementById('preview-container');
        previewContainer.innerHTML = '';
        
        if (input.files) {
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const fileType = file.type.split('/')[0];
                const reader = new FileReader();
                
                const previewItem = document.createElement('div');
                previewItem.className = 'relative border rounded overflow-hidden';
                
                if (fileType === 'image') {
                    reader.onload = function(e) {
                        previewItem.innerHTML = `
                            <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                                <img src="${e.target.result}" class="w-full h-full object-cover">
                            </div>
                            <div class="px-2 py-1 text-xs truncate" title="${file.name}">${file.name}</div>
                        `;
                    }
                    reader.readAsDataURL(file);
                } else {
                    previewItem.innerHTML = `
                        <div class="aspect-w-1 aspect-h-1 bg-gray-100 flex items-center justify-center">
                            <svg class="h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="px-2 py-1 text-xs truncate" title="${file.name}">${file.name}</div>
                    `;
                }
                
                previewContainer.appendChild(previewItem);
            }
        }
    }

    // Drag and drop functionality
    const dropzone = document.getElementById('dropzone');
    const uploadContainer = document.getElementById('upload-container');
    const fileInput = document.getElementById('files');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadContainer.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadContainer.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadContainer.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadContainer.querySelector('.border-dashed').classList.add('border-green-500');
        uploadContainer.querySelector('.border-dashed').classList.add('bg-green-50');
    }

    function unhighlight() {
        uploadContainer.querySelector('.border-dashed').classList.remove('border-green-500');
        uploadContainer.querySelector('.border-dashed').classList.remove('bg-green-50');
    }

    uploadContainer.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        showPreview(fileInput);
    }
</script>
@endpush
@endsection 