<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="{{ $metadataConfig['charset'] ?? 'utf-8' }}">
    <meta name="viewport" content="{{ $metadataConfig['viewport'] ?? 'width=device-width, initial-scale=1' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if(isset($metadataConfig['author']) && !empty($metadataConfig['author']))
    <meta name="author" content="{{ $metadataConfig['author'] }}">
    @endif

    @if(isset($metadataConfig['copyright']) && !empty($metadataConfig['copyright']))
    <meta name="copyright" content="{{ $metadataConfig['copyright'] }}">
    @endif

    @php
        $pageTitle = $__env->yieldContent('title', $seoConfig['meta_title'] ?? config('app.name', 'Laravel'));
        $siteName = $generalConfig['site_name'] ?? config('app.name', 'Laravel');
        $separator = $metadataConfig['head_separator'] ?? '-';
        $titleFormat = $metadataConfig['head_title_format'] ?? '%page_title% %separator% %site_name%';

        $title = str_replace(
            ['%page_title%', '%separator%', '%site_name%'],
            [$pageTitle, $separator, $siteName],
            $titleFormat
        );
    @endphp

    <title>{{ $title }}</title>

    <!-- Meta Tags -->
    <meta name="description" content="{{ $__env->yieldContent('meta_description', $seoConfig['meta_description'] ?? $generalConfig['site_description'] ?? '') }}">
    <meta name="keywords" content="{{ $__env->yieldContent('meta_keywords', $seoConfig['meta_keywords'] ?? '') }}">

    @if(isset($seoConfig['disable_indexing']) && $seoConfig['disable_indexing'])
    <meta name="robots" content="noindex, nofollow">
    @endif

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $__env->yieldContent('og_type', 'website') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $__env->yieldContent('meta_description', $seoConfig['meta_description'] ?? $generalConfig['site_description'] ?? '') }}">
    <meta property="og:image" content="{{ $__env->yieldContent('og_image', $metadataConfig['default_og_image'] ?? asset($generalConfig['logo'] ?? '')) }}">
    
    @if(isset($seoConfig['facebook_app_id']) && !empty($seoConfig['facebook_app_id']))
    <meta property="fb:app_id" content="{{ $seoConfig['facebook_app_id'] }}">
    @endif

    <!-- Twitter -->
    <meta name="twitter:card" content="{{ $seoConfig['twitter_card_type'] ?? 'summary_large_image' }}">
    @if(isset($seoConfig['twitter_username']) && !empty($seoConfig['twitter_username']))
    <meta name="twitter:site" content="{{ $seoConfig['twitter_username'] }}">
    @endif
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $__env->yieldContent('meta_description', $seoConfig['meta_description'] ?? $generalConfig['site_description'] ?? '') }}">
    <meta name="twitter:image" content="{{ $__env->yieldContent('og_image', $metadataConfig['default_og_image'] ?? asset($generalConfig['logo'] ?? '')) }}">

    <!-- Favicon -->
    @if(isset($generalConfig['favicon']) && !empty($generalConfig['favicon']))
    @php
        $faviconUrl = $generalConfig['favicon'];
        // Nếu URL không bắt đầu với http hoặc / thì thêm APP_URL vào đầu
        if (!str_starts_with($faviconUrl, 'http') && !str_starts_with($faviconUrl, '/')) {
            $faviconUrl = '/' . $faviconUrl;
        }
        
        // Nếu URL bắt đầu với /storage nhưng không có file thì thử format khác
        if (str_starts_with($faviconUrl, '/storage/') && !file_exists(public_path(substr($faviconUrl, 1)))) {
            // Thử loại bỏ public/ hoặc storage/ từ đường dẫn
            $alternateUrl = str_replace('/storage/public/', '/storage/', $faviconUrl);
            $faviconUrl = $alternateUrl;
        }
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @endif

    <!-- Thẻ meta tùy chỉnh -->
    @if(isset($metadataConfig['extra_meta_tags']) && !empty($metadataConfig['extra_meta_tags']))
    {!! $metadataConfig['extra_meta_tags'] !!}
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // Configure Axios
        window.axios = axios;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        
        document.addEventListener('alpine:init', () => {
            // Alpine.js global configuration
            Alpine.store('sidebar', {
                open: false,
                toggle() {
                    this.open = !this.open;
                }
            });
        });

        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: [{{ isset($uiConfig['body_font']) ? $uiConfig['body_font'] : "\"Plus Jakarta Sans\", 'sans-serif'" }}],
                        heading: [{{ isset($uiConfig['heading_font']) ? $uiConfig['heading_font'] : "\"Plus Jakarta Sans\", 'sans-serif'" }}],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: {{ isset($uiConfig['primary_color']) ? "'" . $uiConfig['primary_color'] . "'" : "'#0ea5e9'" }},
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: {{ isset($uiConfig['secondary_color']) ? "'" . $uiConfig['secondary_color'] . "'" : "'#64748b'" }},
                        accent: {{ isset($uiConfig['accent_color']) ? "'" . $uiConfig['accent_color'] . "'" : "'#f97316'" }},
                        text: {{ isset($uiConfig['text_color']) ? "'" . $uiConfig['text_color'] . "'" : "'#1e293b'" }},
                    }
                }
            }
        };
    </script>

    <!-- CSS tùy chỉnh -->
    @if(file_exists(public_path('css/custom-variables.css')))
    <link rel="stylesheet" href="{{ asset('css/custom-variables.css') }}">
    @endif

    <style>
        [x-cloak] { display: none !important; }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Animation utilities */
        .transition-all {
            transition: all 0.3s ease;
        }
        
        .hover-lift {
            transition: transform 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-5px);
        }

        /* Apply custom fonts */
        h1, h2, h3, h4, h5, h6 {
            font-family: {{ isset($uiConfig['heading_font']) ? $uiConfig['heading_font'] : "\"Plus Jakarta Sans\", sans-serif" }};
        }
        
        body {
            font-family: {{ isset($uiConfig['body_font']) ? $uiConfig['body_font'] : "\"Plus Jakarta Sans\", sans-serif" }};
            color: {{ isset($uiConfig['text_color']) ? $uiConfig['text_color'] : "#1e293b" }};
        }
    </style>

    <!-- Mã tùy chỉnh trong head -->
    @if(isset($metadataConfig['custom_head_code']) && !empty($metadataConfig['custom_head_code']))
    {!! $metadataConfig['custom_head_code'] !!}
    @endif

    <!-- Google Analytics -->
    @if(isset($seoConfig['google_analytics_id']) && !empty($seoConfig['google_analytics_id']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seoConfig['google_analytics_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $seoConfig['google_analytics_id'] }}');
    </script>
    @endif
</head>
<body class="font-sans antialiased bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    @if(auth()->check() && auth()->user()->role === 'admin' && request()->is('admin*'))
        @include('layouts.admin-sidebar')
    @else
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow-sm">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="pt-4">
                @if(session('success'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="rounded-lg bg-green-50 p-4 border-l-4 border-green-500 shadow-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ session('success') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="rounded-lg bg-red-50 p-4 border-l-4 border-red-500 shadow-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">
                                        {{ session('error') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-md mb-4" role="alert">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white shadow-lg mt-12 border-t border-gray-200">
                <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                @if(isset($generalConfig['site_name']))
                                    {{ $generalConfig['site_name'] }}
                                @else
                                    Magazine AI System
                                @endif
                            </h2>
                            <p class="text-gray-600 text-sm">
                                @if(isset($generalConfig['site_description']))
                                    {{ $generalConfig['site_description'] }}
                                @else
                                    Hệ thống quản lý nội dung thông minh được hỗ trợ bởi công nghệ trí tuệ nhân tạo.
                                @endif
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Thông tin liên hệ</h3>
                            <ul class="space-y-2">
                                @if(isset($generalConfig['site_address']))
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-gray-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-gray-600 text-sm">{{ $generalConfig['site_address'] }}</span>
                                    </li>
                                @endif
                                @if(isset($generalConfig['site_email']))
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-gray-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                        <a href="mailto:{{ $generalConfig['site_email'] }}" class="text-gray-600 hover:text-primary-600 text-sm">{{ $generalConfig['site_email'] }}</a>
                                    </li>
                                @endif
                                @if(isset($generalConfig['site_phone']))
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-gray-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                        </svg>
                                        <a href="tel:{{ $generalConfig['site_phone'] }}" class="text-gray-600 hover:text-primary-600 text-sm">{{ $generalConfig['site_phone'] }}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Mạng xã hội</h3>
                            <div class="flex space-x-4">
                                @if(isset($socialConfig['facebook_url']))
                                    <a href="{{ $socialConfig['facebook_url'] }}" target="_blank" class="text-gray-500 hover:text-blue-600">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"></path>
                                        </svg>
                                    </a>
                                @endif
                                @if(isset($socialConfig['instagram_url']))
                                    <a href="{{ $socialConfig['instagram_url'] }}" target="_blank" class="text-gray-500 hover:text-pink-600">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </a>
                                @endif
                                @if(isset($socialConfig['twitter_url']))
                                    <a href="{{ $socialConfig['twitter_url'] }}" target="_blank" class="text-gray-500 hover:text-blue-400">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                                        </svg>
                                    </a>
                                @endif
                                @if(isset($socialConfig['linkedin_url']))
                                    <a href="{{ $socialConfig['linkedin_url'] }}" target="_blank" class="text-gray-500 hover:text-blue-700">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd"></path>
                                        </svg>
                                    </a>
                                @endif
                                @if(isset($socialConfig['youtube_url']))
                                    <a href="{{ $socialConfig['youtube_url'] }}" target="_blank" class="text-gray-500 hover:text-red-600">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418ZM15.194 12 10 15V9l5.194 3Z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                @endif
                                @if(isset($socialConfig['tiktok_url']))
                                    <a href="{{ $socialConfig['tiktok_url'] }}" target="_blank" class="text-gray-500 hover:text-black">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M21.543 7.104c-.815-.073-1.62-.235-2.395-.54-1.33-.526-2.43-1.5-3.146-2.723-.718-1.225-1.01-2.664-.84-4.088H10.73v13.77c0 .703-.119 1.282-.356 1.738-.238.456-.583.76-1.035.909-.452.149-.932.128-1.44-.062-.509-.19-.934-.559-1.276-1.107-.448-.724-.638-1.516-.572-2.375.066-.86.399-1.604.995-2.231A4.011 4.011 0 0 1 9.1 9.487c.395-.125.778-.184 1.15-.176v-4.67c-1.233.076-2.4.334-3.5.776a9.318 9.318 0 0 0-2.937 1.888 8.844 8.844 0 0 0-1.994 2.788 8.511 8.511 0 0 0-.749 3.474c.013 1.247.244 2.426.692 3.537.448 1.11 1.084 2.084 1.909 2.92a8.943 8.943 0 0 0 2.939 1.966c1.13.467 2.324.701 3.584.701 1.26 0 2.454-.234 3.584-.701a8.943 8.943 0 0 0 2.939-1.966c.825-.836 1.461-1.81 1.909-2.92.448-1.111.679-2.29.692-3.537V10.13c.913.568 1.86.93 2.84 1.089 1.379.223 2.757.146 4.134-.233V7.05c-.585.101-1.18.111-1.783.033a7.076 7.076 0 0 1-1.875-.421v.442Z"/>
                                        </svg>
                                    </a>
                                @endif
                                @if(isset($socialConfig['pinterest_url']))
                                    <a href="{{ $socialConfig['pinterest_url'] }}" target="_blank" class="text-gray-500 hover:text-red-700">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 0c-6.627 0-12 5.373-12 12 0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.535 3.554.535 6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                            <div class="mt-6">
                                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Liên kết</h3>
                                <ul class="space-y-2">
                                    <li><a href="{{ route('home') }}" class="text-gray-600 hover:text-primary-600 text-sm">Trang chủ</a></li>
                                    <li><a href="{{ route('articles.all') }}" class="text-gray-600 hover:text-primary-600 text-sm">Bài viết</a></li>
                                    <li><a href="#" class="text-gray-600 hover:text-primary-600 text-sm">Điều khoản sử dụng</a></li>
                                    <li><a href="#" class="text-gray-600 hover:text-primary-600 text-sm">Chính sách bảo mật</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 pt-8 mt-8 text-center">
                        <p class="text-gray-500 text-sm">
                            &copy; {{ date('Y') }} 
                            @if(isset($generalConfig['site_name']))
                                {{ $generalConfig['site_name'] }}
                            @else
                                Magazine AI System
                            @endif
                            . All rights reserved.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    @endif
</body>
</html> 