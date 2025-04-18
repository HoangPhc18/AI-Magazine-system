@extends('layouts.admin')

@section('title', 'Thu thập bài viết Facebook - Magazine AI System')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Thu thập bài viết Facebook</h1>
        <p class="mt-1 text-sm text-gray-500">Nhập URL Facebook để thu thập bài viết</p>
    </div>

    <!-- Notifications -->
    @if (session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Job Status Display -->
    <div id="jobStatusContainer" class="mb-6 hidden bg-blue-50 border-l-4 border-blue-500 p-4 rounded shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0" id="jobStatusIcon">
                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700" id="jobStatusMessage">Đang thu thập bài viết Facebook...</p>
            </div>
        </div>
    </div>

    <!-- Create form -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
        <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Thông tin thu thập</h3>
        </div>
        <div class="p-6">
            <form action="{{ route('admin.facebook-posts.store') }}" method="POST" id="scrapeForm">
                @csrf
                
                <div class="mb-6">
                    <label for="url" class="block text-sm font-medium text-gray-700 mb-1">URL Facebook <span class="text-red-600">*</span></label>
                    <input type="url" name="url" id="url" required value="{{ old('url') }}" 
                           class="w-full rounded-md shadow-sm border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 @error('url') border-red-500 @enderror"
                           placeholder="https://www.facebook.com/groups/tên-group hoặc https://www.facebook.com/tên-trang">
                    @error('url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Nhập URL của Facebook page hoặc group muốn thu thập bài viết.</p>
                </div>
                
                <div class="mb-6 flex flex-col md:flex-row md:space-x-4">
                    <div class="w-full md:w-1/2">
                        <div class="flex items-center">
                            <input type="checkbox" name="use_profile" id="use_profile" value="1" 
                                  {{ old('use_profile', '1') == '1' ? 'checked' : '' }}
                                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="use_profile" class="ml-2 block text-sm text-gray-700">Sử dụng Chrome profile đã đăng nhập</label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Khuyên dùng, sẽ giúp truy cập vào các nội dung yêu cầu đăng nhập.</p>
                    </div>
                    
                    <div class="w-full md:w-1/2 mt-4 md:mt-0">
                        <label for="chrome_profile" class="block text-sm font-medium text-gray-700 mb-1">Chrome Profile</label>
                        <select name="chrome_profile" id="chrome_profile" 
                                class="w-full rounded-md shadow-sm border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                            <option value="Default" {{ old('chrome_profile') == 'Default' ? 'selected' : '' }}>Default</option>
                            <option value="Profile 1" {{ old('chrome_profile') == 'Profile 1' ? 'selected' : '' }}>Profile 1</option>
                            <option value="Profile 2" {{ old('chrome_profile') == 'Profile 2' ? 'selected' : '' }}>Profile 2</option>
                            <option value="Profile 3" {{ old('chrome_profile') == 'Profile 3' ? 'selected' : '' }}>Profile 3</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Chọn profile Chrome cần sử dụng (phải đã đăng nhập vào Facebook).</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200">
                    <a href="{{ route('admin.facebook-posts.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all">
                        Hủy
                    </a>
                    <button type="submit" id="submitButton" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span id="buttonText">Bắt đầu thu thập</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Instructions -->
    <div class="mt-8 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-gray-200">
            <h3 class="text-lg font-medium text-blue-900">Hướng dẫn sử dụng</h3>
        </div>
        <div class="p-6 text-sm text-gray-700 space-y-3">
            <p><span class="font-medium">1. Chế độ profile Chrome:</span> Sử dụng profile Chrome sẽ giúp truy cập vào các bài viết yêu cầu đăng nhập. Trước khi sử dụng, bạn cần đăng nhập vào Facebook trên profile Chrome đã chọn.</p>
            <p><span class="font-medium">2. Tự động xử lý:</span> Khi bắt đầu thu thập, hệ thống sẽ tự động xử lý và lưu các bài viết vào cơ sở dữ liệu.</p>
            <p><span class="font-medium">3. Thời gian xử lý:</span> Quá trình thu thập có thể mất từ vài giây đến vài phút tùy thuộc vào số lượng bài viết.</p>
            <p><span class="font-medium">4. Nếu gặp lỗi:</span> Hãy đảm bảo URL là đúng và có thể truy cập được từ trình duyệt của bạn. Nếu Facebook yêu cầu đăng nhập, bạn cần sử dụng chế độ profile Chrome.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Facebook Scraper script loaded');
        
        const form = document.getElementById('scrapeForm');
        const submitButton = document.getElementById('submitButton');
        const buttonText = document.getElementById('buttonText');
        const jobStatusContainer = document.getElementById('jobStatusContainer');
        const jobStatusMessage = document.getElementById('jobStatusMessage');
        const jobStatusIcon = document.getElementById('jobStatusIcon');
        
        let jobId = null;
        let checkJobInterval = null;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            // Ngăn chặn gửi form nhiều lần
            if (submitButton.disabled) {
                console.log('Button already disabled, preventing duplicate submission');
                return;
            }
            
            // Hiển thị trạng thái đang xử lý
            submitButton.disabled = true;
            buttonText.textContent = 'Đang xử lý...';
            jobStatusContainer.classList.remove('hidden');
            
            // Lấy dữ liệu form
            const formData = new FormData(form);
            const url = formData.get('url');
            const useProfile = formData.has('use_profile') ? 1 : 0;
            const chromeProfile = formData.get('chrome_profile');
            
            console.log('Form data:', { url, useProfile, chromeProfile });
            
            // Chuẩn bị dữ liệu và headers
            const requestData = {
                url: url,
                use_profile: useProfile,
                chrome_profile: chromeProfile,
                limit: 10
            };
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            console.log('Calling API with data:', requestData);
            
            // Gọi API scrapeFromApi
            fetch('/api/facebook-posts/scrape-api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('API response status:', response.status);
                if (!response.ok) {
                    throw new Error(`Lỗi HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API response data:', data);
                
                if (data.success) {
                    jobId = data.job_id;
                    console.log('Job started with ID:', jobId);
                    jobStatusMessage.textContent = `Đã bắt đầu thu thập bài viết (Job ID: ${jobId})...`;
                    
                    // Thay vì giả lập, chúng ta sẽ kiểm tra API thực tế
                    checkJobInterval = setInterval(function() {
                        checkRealJobStatus(jobId);
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Có lỗi xảy ra khi bắt đầu thu thập');
                }
            })
            .catch(error => {
                console.error('API error:', error);
                
                jobStatusContainer.classList.remove('bg-blue-50', 'border-blue-500');
                jobStatusContainer.classList.add('bg-red-50', 'border-red-500');
                
                jobStatusIcon.innerHTML = `
                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                `;
                
                jobStatusMessage.textContent = `Lỗi: ${error.message}`;
                jobStatusMessage.classList.remove('text-blue-700');
                jobStatusMessage.classList.add('text-red-700');
                
                // Sau 5 giây, chuyển sang sử dụng phương thức server-side
                setTimeout(function() {
                    jobStatusMessage.textContent = `Đang thử lại bằng phương thức khác...`;
                    // Gửi form đến server
                    form.submit();
                }, 3000);
            });
        });
        
        function checkRealJobStatus(jobId) {
            if (!jobId) return;
            
            console.log('Checking real job status for ID:', jobId);
            
            // Gọi API để kiểm tra trạng thái thực tế
            fetch(`/api/facebook-posts/jobs/${jobId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    // Nếu API trả về 404, có thể API server chưa hoạt động hoặc không tìm thấy job
                    console.error('API error with status:', response.status);
                    throw new Error(`Lỗi HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;
                
                console.log('Job status data:', data);
                
                // Kiểm tra trạng thái job
                if (data.status === 'completed') {
                    showJobCompleted(data.posts_count || 0);
                } else if (data.status === 'failed') {
                    showJobFailed(data.error || 'Không tìm thấy bài viết nào');
                } else {
                    // Đang chạy, cập nhật tiến trình
                    jobStatusMessage.textContent = `Đang thu thập bài viết... ${data.progress || ''}`;
                }
            })
            .catch(error => {
                console.error('Error checking job status:', error);
                
                // Tăng số lần kiểm tra thất bại
                const checkCount = parseInt(jobStatusContainer.dataset.checkCount || '0') + 1;
                jobStatusContainer.dataset.checkCount = checkCount.toString();
                console.log('Failed check count:', checkCount);
                
                if (checkCount >= 3) {
                    // Sau 3 lần thất bại, chuyển sang sử dụng phương thức server-side
                    clearInterval(checkJobInterval);
                    
                    jobStatusMessage.textContent = `Đang chuyển sang phương thức thực thi trực tiếp...`;
                    
                    // Đợi 1 giây rồi gửi form
                    setTimeout(function() {
                        form.submit();
                    }, 1000);
                }
            });
        }
        
        function showJobCompleted(postsCount) {
            clearInterval(checkJobInterval);
            console.log('Job completed successfully');
            
            // Cập nhật UI khi hoàn thành thành công
            jobStatusContainer.classList.remove('bg-blue-50', 'border-blue-500');
            jobStatusContainer.classList.add('bg-green-50', 'border-green-500');
            
            jobStatusIcon.innerHTML = `
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            `;
            
            jobStatusMessage.textContent = postsCount 
                ? `Thu thập hoàn tất! Đã lưu ${postsCount} bài viết vào cơ sở dữ liệu.`
                : `Thu thập hoàn tất! Bài viết đã được lưu vào cơ sở dữ liệu.`;
            jobStatusMessage.classList.remove('text-blue-700');
            jobStatusMessage.classList.add('text-green-700');
            
            // Chuyển hướng sau 3 giây
            setTimeout(function() {
                window.location.href = "{{ route('admin.facebook-posts.index') }}";
            }, 3000);
        }
        
        function showJobFailed(errorMessage) {
            clearInterval(checkJobInterval);
            console.log('Job failed:', errorMessage);
            
            jobStatusContainer.classList.remove('bg-blue-50', 'border-blue-500');
            jobStatusContainer.classList.add('bg-red-50', 'border-red-500');
            
            jobStatusIcon.innerHTML = `
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            `;
            
            jobStatusMessage.textContent = `Lỗi: ${errorMessage}`;
            jobStatusMessage.classList.remove('text-blue-700');
            jobStatusMessage.classList.add('text-red-700');
            
            // Kích hoạt lại nút submit
            submitButton.disabled = false;
            buttonText.textContent = 'Bắt đầu thu thập';
            
            // Sau 5 giây, thử phương thức server-side
            setTimeout(function() {
                jobStatusMessage.textContent = `Đang thử lại bằng phương thức khác...`;
                // Gửi form đến server
                form.submit();
            }, 3000);
        }
    });
</script>
@endpush
@endsection