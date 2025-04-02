@extends('layouts.admin')

@section('title', 'Cài đặt AI - Hệ thống Magazine AI')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Cài đặt cấu hình AI</h1>
            <p class="mt-1 text-gray-500">Cấu hình AI cho việc tạo và viết lại bài viết tự động</p>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 shadow-sm border-l-4 border-green-500 animate-fadeIn" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 shadow-sm border-l-4 border-red-500 animate-fadeIn" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-5 bg-gradient-to-r from-primary-500/10 to-primary-600/10 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 bg-gradient-to-br from-primary-500 to-primary-600 p-2 rounded-lg shadow-sm">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Cấu hình AI</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tùy chỉnh cài đặt cho trí tuệ nhân tạo</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.ai-settings.update') }}" method="POST" class="p-6">
                @csrf
                <div class="space-y-8">
                    <!-- API Settings -->
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                            </svg>
                            Cài đặt API
                        </h3>
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="provider" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nhà cung cấp API</label>
                                <select id="provider" name="provider" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-colors">
                                    <option value="openai" {{ $aiSettings->provider === 'openai' ? 'selected' : '' }}>OpenAI</option>
                                    <option value="anthropic" {{ $aiSettings->provider === 'anthropic' ? 'selected' : '' }}>Anthropic</option>
                                    <option value="mistral" {{ $aiSettings->provider === 'mistral' ? 'selected' : '' }}>Mistral AI</option>
                                    <option value="ollama" {{ $aiSettings->provider === 'ollama' ? 'selected' : '' }}>Ollama (Local)</option>
                                    <option value="custom" {{ $aiSettings->provider === 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
                                </select>
                                @error('provider')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Khóa API</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="password" name="api_key" id="api_key" value="{{ $aiSettings->api_key }}" autocomplete="off"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pr-10 focus:border-primary-500 focus:ring-primary-500 transition-colors">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Khóa API của bạn sẽ được lưu trữ an toàn. Không bắt buộc đối với Ollama.</p>
                                @error('api_key')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="api_url_container" class="sm:col-span-3" style="{{ in_array($aiSettings->provider, ['ollama', 'custom']) ? '' : 'display: none;' }}">
                                <label for="api_url" class="block text-sm font-medium text-gray-700">URL API</label>
                                <input type="text" name="api_url" id="api_url" value="{{ $aiSettings->api_url }}"
                                    placeholder="{{ $aiSettings->provider === 'ollama' ? 'http://localhost:11434' : 'https://' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $aiSettings->provider === 'ollama' ? 'URL đến máy chủ Ollama của bạn. Mặc định: http://localhost:11434' : 'URL đến API tùy chỉnh của bạn.' }}
                                </p>
                                @error('api_url')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="model_name" class="block text-sm font-medium text-gray-700">Tên mô hình</label>
                                <input type="text" name="model_name" id="model_name" value="{{ $aiSettings->model_name }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">
                                    Ví dụ: 
                                    <span class="provider-hint openai {{ $aiSettings->provider === 'openai' ? '' : 'hidden' }}">gpt-3.5-turbo, gpt-4</span>
                                    <span class="provider-hint anthropic {{ $aiSettings->provider === 'anthropic' ? '' : 'hidden' }}">claude-2.1, claude-instant-1.2</span>
                                    <span class="provider-hint mistral {{ $aiSettings->provider === 'mistral' ? '' : 'hidden' }}">mistral-tiny, mistral-small</span>
                                    <span class="provider-hint ollama {{ $aiSettings->provider === 'ollama' ? '' : 'hidden' }}">llama2, mistral, mixtral</span>
                                </p>
                                @error('model_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" id="test_connection" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                </svg>
                                Kiểm tra kết nối
                            </button>
                            <span id="connection_status" class="ml-3 text-sm"></span>
                        </div>
                    </div>

                    <!-- Model Settings -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Cài đặt mô hình</h3>
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="temperature" class="block text-sm font-medium text-gray-700">Nhiệt độ</label>
                                <div class="mt-1 flex items-center">
                                    <input type="range" name="temperature" id="temperature" min="0" max="2" step="0.1" value="{{ $aiSettings->temperature }}"
                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                    <span id="temperature_value" class="ml-3 text-sm text-gray-900">{{ $aiSettings->temperature }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Điều chỉnh tính ngẫu nhiên: 0 = chính xác, 2 = sáng tạo</p>
                                @error('temperature')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label for="max_tokens" class="block text-sm font-medium text-gray-700">Số token tối đa</label>
                                <input type="number" name="max_tokens" id="max_tokens" value="{{ $aiSettings->max_tokens }}" min="1" max="16000"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Độ dài tối đa của nội dung được tạo ra</p>
                                @error('max_tokens')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Rewriting Settings -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Cài đặt viết lại</h3>
                        <div class="space-y-6">
                            <div>
                                <label for="rewrite_prompt_template" class="block text-sm font-medium text-gray-700">Mẫu yêu cầu viết lại</label>
                                <textarea id="rewrite_prompt_template" name="rewrite_prompt_template" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $aiSettings->rewrite_prompt_template }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Sử dụng {article} làm vị trí cho nội dung bài viết gốc</p>
                                @error('rewrite_prompt_template')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="auto_approval" name="auto_approval" type="checkbox" value="1" {{ $aiSettings->auto_approval ? 'checked' : '' }}
                                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="auto_approval" class="font-medium text-gray-700">Tự động duyệt</label>
                                            <p class="text-gray-500">Tự động duyệt các bài viết đã được viết lại</p>
                                        </div>
                                    </div>
                                    @error('auto_approval')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="max_daily_rewrites" class="block text-sm font-medium text-gray-700">Số lượng viết lại tối đa mỗi ngày</label>
                                    <input type="number" name="max_daily_rewrites" id="max_daily_rewrites" value="{{ $aiSettings->max_daily_rewrites }}" min="0"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <p class="mt-1 text-xs text-gray-500">Số lượng viết lại tối đa mỗi ngày (0 = không giới hạn)</p>
                                    @error('max_daily_rewrites')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between pt-4">
                        <a href="{{ route('admin.ai-settings.reset') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200"
                           onclick="return confirm('Bạn có chắc chắn muốn đặt lại tất cả cài đặt AI về giá trị mặc định?');">
                            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                            </svg>
                            Đặt lại về mặc định
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Lưu cài đặt
                        </button>
                    </div>
                </div>
            </form>

            <div id="models_list" class="mt-6 p-6 pt-0 hidden">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Các mô hình khả dụng
                </h3>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-inner">
                    <div id="models_content" class="text-sm text-gray-700 dark:text-gray-300 max-h-40 overflow-y-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const temperatureInput = document.getElementById('temperature');
    const temperatureValue = document.getElementById('temperature_value');
    const providerSelect = document.getElementById('provider');
    const apiUrlContainer = document.getElementById('api_url_container');
    const apiUrlInput = document.getElementById('api_url');
    const providerHints = document.querySelectorAll('.provider-hint');
    const testConnectionBtn = document.getElementById('test_connection');
    const connectionStatus = document.getElementById('connection_status');
    const modelsList = document.getElementById('models_list');
    const modelsContent = document.getElementById('models_content');

    // Update temperature value display
    temperatureInput.addEventListener('input', function() {
        temperatureValue.textContent = this.value;
    });

    // Show/hide API URL based on provider
    providerSelect.addEventListener('change', function() {
        const showApiUrl = this.value === 'ollama' || this.value === 'custom';
        apiUrlContainer.style.display = showApiUrl ? 'block' : 'none';
        
        // Update placeholder based on provider
        if (this.value === 'ollama') {
            apiUrlInput.placeholder = 'http://localhost:11434';
        } else if (this.value === 'custom') {
            apiUrlInput.placeholder = 'https://';
        }

        // Show relevant provider hints
        providerHints.forEach(hint => {
            hint.classList.add('hidden');
        });
        const currentHint = document.querySelector(`.provider-hint.${this.value}`);
        if (currentHint) {
            currentHint.classList.remove('hidden');
        }
    });

    // Test connection
    testConnectionBtn.addEventListener('click', function() {
        connectionStatus.textContent = 'Đang kiểm tra kết nối...';
        connectionStatus.className = 'ml-3 text-sm text-blue-600';
        modelsList.classList.add('hidden');

        const formData = new FormData();
        formData.append('provider', providerSelect.value);
        formData.append('api_key', document.getElementById('api_key').value);
        formData.append('api_url', document.getElementById('api_url').value);
        formData.append('model_name', document.getElementById('model_name').value);

        fetch('{{ route("admin.ai-settings.test-connection") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                connectionStatus.textContent = 'Kết nối thành công!';
                connectionStatus.className = 'ml-3 text-sm text-green-600';
                
                if (data.models && data.models.length > 0) {
                    modelsList.classList.remove('hidden');
                    modelsContent.innerHTML = '';
                    
                    data.models.forEach(model => {
                        const div = document.createElement('div');
                        div.className = 'py-1';
                        div.textContent = model;
                        modelsContent.appendChild(div);
                    });
                }
            } else {
                connectionStatus.textContent = `Lỗi: ${data.message}`;
                connectionStatus.className = 'ml-3 text-sm text-red-600';
            }
        })
        .catch(error => {
            connectionStatus.textContent = `Lỗi: ${error.message}`;
            connectionStatus.className = 'ml-3 text-sm text-red-600';
        });
    });
});
</script>
@endpush
@endsection 