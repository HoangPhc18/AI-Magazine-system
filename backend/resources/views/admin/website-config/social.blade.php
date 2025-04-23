@extends('layouts.admin')

@section('title', 'Cấu hình mạng xã hội')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Cấu hình mạng xã hội</h1>
            <p class="mt-2 text-sm text-gray-700">
                Quản lý các liên kết đến kênh mạng xã hội của website.
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
            <form method="POST" action="{{ route('admin.website-config.social.update') }}">
                @csrf

                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Facebook -->
                        <div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10"></path>
                                </svg>
                                <label for="facebook_url" class="block text-sm font-medium text-gray-700">Facebook</label>
                            </div>
                            <input type="url" name="facebook_url" id="facebook_url" 
                                   placeholder="https://facebook.com/yourpage"
                                   value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('facebook_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Instagram -->
                        <div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-pink-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465.668.25 1.22.603 1.772 1.153.55.55.902 1.104 1.153 1.772.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.971c0 2.716-.012 3.056-.06 4.123-.049 1.064-.218 1.791-.465 2.427a4.904 4.904 0 01-1.153 1.772c-.55.55-1.104.902-1.772 1.153-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.973c-2.716 0-3.056-.012-4.123-.06-1.064-.049-1.791-.218-2.427-.465a4.904 4.904 0 01-1.772-1.153 4.904 4.904 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-1.315c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427.25-.668.603-1.22 1.153-1.772.55-.55 1.104-.902 1.772-1.153.636-.247 1.363-.416 2.427-.465C9.516 2.013 9.87 2 12.3 2h.015zm-.015 1.802h-.723c-2.136 0-2.39.007-3.233.047-.782.036-1.203.166-1.485.276-.35.128-.663.301-.95.588a2.391 2.391 0 00-.588.95c-.11.282-.24.703-.276 1.485-.04.843-.047 1.096-.047 3.233s.007 2.39.047 3.233c.036.782.166 1.203.276 1.485.145.372.319.638.588.95.287.269.601.442.95.588.282.11.703.24 1.485.276.843.04 1.097.047 3.233.047s2.39-.007 3.233-.047c.782-.036 1.203-.166 1.485-.276.35-.128.663-.301.95-.588.287-.269.443-.601.588-.95.11-.282.24-.703.276-1.485.04-.843.047-1.096.047-3.233s-.007-2.39-.047-3.233c-.036-.782-.166-1.203-.276-1.485a2.391 2.391 0 00-.588-.95 2.391 2.391 0 00-.95-.588c-.282-.11-.703-.24-1.485-.276-.843-.04-1.096-.047-3.233-.047zm2.192 5.6a3.25 3.25 0 11-6.5 0 3.25 3.25 0 016.5 0zm-1.625 0a1.625 1.625 0 10-3.25 0 1.625 1.625 0 003.25 0z"></path>
                                </svg>
                                <label for="instagram_url" class="block text-sm font-medium text-gray-700">Instagram</label>
                            </div>
                            <input type="url" name="instagram_url" id="instagram_url" 
                                   placeholder="https://instagram.com/youraccount"
                                   value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('instagram_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- YouTube -->
                        <div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21.543 6.498C22 8.28 22 12 22 12s0 3.72-.457 5.502c-.254.985-.997 1.76-1.938 2.022C17.896 20 12 20 12 20s-5.893 0-7.605-.476c-.945-.266-1.687-1.04-1.938-2.022C2 15.72 2 12 2 12s0-3.72.457-5.502c.254-.985.997-1.76 1.938-2.022zM10 15.5l6-3.5-6-3.5v7z"></path>
                                </svg>
                                <label for="youtube_url" class="block text-sm font-medium text-gray-700">YouTube</label>
                            </div>
                            <input type="url" name="youtube_url" id="youtube_url" 
                                   placeholder="https://youtube.com/channel/yourchannel"
                                   value="{{ old('youtube_url', $settings['youtube_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('youtube_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Twitter -->
                        <div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-400" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"></path>
                                </svg>
                                <label for="twitter_url" class="block text-sm font-medium text-gray-700">Twitter</label>
                            </div>
                            <input type="url" name="twitter_url" id="twitter_url" 
                                   placeholder="https://twitter.com/youraccount"
                                   value="{{ old('twitter_url', $settings['twitter_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('twitter_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- TikTok -->
                        <div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-black" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"></path>
                                </svg>
                                <label for="tiktok_url" class="block text-sm font-medium text-gray-700">TikTok</label>
                            </div>
                            <input type="url" name="tiktok_url" id="tiktok_url" 
                                   placeholder="https://tiktok.com/@youraccount"
                                   value="{{ old('tiktok_url', $settings['tiktok_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('tiktok_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- LinkedIn -->
                        <div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-700" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"></path>
                                </svg>
                                <label for="linkedin_url" class="block text-sm font-medium text-gray-700">LinkedIn</label>
                            </div>
                            <input type="url" name="linkedin_url" id="linkedin_url" 
                                   placeholder="https://linkedin.com/company/yourcompany"
                                   value="{{ old('linkedin_url', $settings['linkedin_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('linkedin_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Pinterest -->
                        <div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"></path>
                                </svg>
                                <label for="pinterest_url" class="block text-sm font-medium text-gray-700">Pinterest</label>
                            </div>
                            <input type="url" name="pinterest_url" id="pinterest_url" 
                                   placeholder="https://pinterest.com/youraccount"
                                   value="{{ old('pinterest_url', $settings['pinterest_url'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('pinterest_url')
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