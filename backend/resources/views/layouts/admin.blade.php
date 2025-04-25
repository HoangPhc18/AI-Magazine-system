<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Quản trị - Magazine AI System')</title>

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
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        window.axios.defaults.withCredentials = true;
        
        // Store session info
        window.SESSION_USER = @json(Auth::user());
        
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
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                    }
                }
            }
        };
    </script>
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
            transform: translateY(-3px);
        }
        
        /* Dashboard card effects */
        .dashboard-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Admin content area */
        .admin-content {
            padding: 1.5rem;
            background-color: #f9fafb;
            min-height: calc(100vh - 4rem);
        }
        
        /* Media selector modal styling */
        .aspect-w-1,
        .aspect-w-2,
        .aspect-w-3,
        .aspect-w-4,
        .aspect-w-5,
        .aspect-w-6,
        .aspect-w-7,
        .aspect-w-8,
        .aspect-w-9,
        .aspect-w-10,
        .aspect-w-11,
        .aspect-w-12,
        .aspect-w-13,
        .aspect-w-14,
        .aspect-w-15,
        .aspect-w-16 {
            position: relative;
        }

        .aspect-w-1 > *,
        .aspect-w-2 > *,
        .aspect-w-3 > *,
        .aspect-w-4 > *,
        .aspect-w-5 > *,
        .aspect-w-6 > *,
        .aspect-w-7 > *,
        .aspect-w-8 > *,
        .aspect-w-9 > *,
        .aspect-w-10 > *,
        .aspect-w-11 > *,
        .aspect-w-12 > *,
        .aspect-w-13 > *,
        .aspect-w-14 > *,
        .aspect-w-15 > *,
        .aspect-w-16 > * {
            position: absolute;
            height: 100%;
            width: 100%;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }

        .aspect-w-1 {
            padding-bottom: calc(1 / 1 * 100%);
        }
        .aspect-h-1 {
            padding-bottom: calc(1 / 1 * 100%);
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        @include('layouts.admin-sidebar')

        <div class="lg:pl-64 min-h-screen">
            <main class="min-h-screen pb-20">
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Base scripts -->
    <script src="{{ asset('js/media-selector.js') }}"></script>
    
    <!-- Scripts stacked from views -->
    @stack('scripts')
</body>
</html> 