<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
                },
                close() {
                    this.open = false;
                }
            });
            
            // Add responsive feature detection
            Alpine.store('responsive', {
                isMobile: window.innerWidth < 768,
                isTablet: window.innerWidth >= 768 && window.innerWidth < 1024,
                isDesktop: window.innerWidth >= 1024,
                init() {
                    window.addEventListener('resize', () => {
                        this.isMobile = window.innerWidth < 768;
                        this.isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
                        this.isDesktop = window.innerWidth >= 1024;
                        
                        // Auto-close sidebar on mobile when resizing
                        if (this.isMobile && Alpine.store('sidebar').open) {
                            Alpine.store('sidebar').close();
                        }
                    });
                }
            });
            
            Alpine.store('responsive').init();
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
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <style>
        [x-cloak] { display: none !important; }
        
        html {
            scroll-behavior: smooth;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
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
        
        .transition-all {
            transition: all 0.25s ease;
        }
        
        .dashboard-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .admin-content {
            padding: 1.5rem;
            background-color: #f9fafb;
            min-height: calc(100vh - 4rem);
        }
        
        .aspect-ratio {
            position: relative;
        }
        
        .aspect-ratio > * {
            position: absolute;
            height: 100%;
            width: 100%;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }
        
        .aspect-square {
            padding-bottom: calc(1 / 1 * 100%);
        }
        
        .admin-sidebar {
            width: 280px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 40;
            transition: transform 0.3s ease-in-out;
        }
        
        .admin-main {
            margin-left: 280px;
            transition: margin-left 0.3s ease-in-out;
        }
        
        .admin-header {
            height: 64px;
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
        }
        
        @media (max-width: 1023px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-menu-trigger {
                display: block;
            }
        }
        
        .admin-notification {
            @apply mb-4 rounded-md p-4;
        }
        
        .admin-notification.success {
            @apply bg-green-50 text-green-800 border-l-4 border-green-500;
        }
        
        .admin-notification.error {
            @apply bg-red-50 text-red-800 border-l-4 border-red-500;
        }
        
        .admin-notification.warning {
            @apply bg-yellow-50 text-yellow-800 border-l-4 border-yellow-500;
        }
        
        .admin-notification.info {
            @apply bg-blue-50 text-blue-800 border-l-4 border-blue-500;
        }
        
        /* Mobile menu trigger */
        .mobile-menu-trigger {
            display: none;
        }
        
        /* Sidebar overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 35;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50" x-data="{ menuOpen: false }">
    <div class="min-h-screen flex">
        <!-- Sidebar overlay (only visible on mobile when menu is open) -->
        <div 
            class="sidebar-overlay" 
            :class="{ 'active': $store.sidebar.open }"
            @click="$store.sidebar.open = false"
        ></div>
        
        <!-- Sidebar -->
        <div class="admin-sidebar" :class="{ 'open': $store.sidebar.open }">
            @include('layouts.admin-sidebar')
        </div>

        <!-- Main content -->
        <div class="admin-main flex-1 min-h-screen">
            <!-- Header with search and notifications -->
            <header class="admin-header sticky top-0 z-10">
                <div class="flex items-center justify-between h-full px-6">
                    <!-- Mobile menu trigger -->
                    <button 
                        class="block lg:hidden p-2 mr-3 text-gray-600 hover:text-gray-900 focus:outline-none" 
                        @click="$store.sidebar.toggle()"
                    >
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <!-- Search form -->
                    <div class="relative flex-grow max-w-md">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" placeholder="Tìm kiếm..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5">
                    </div>
                    
                    <!-- User dropdown -->
                    <div class="flex items-center ml-4">
                        <div class="flex items-center" x-data="{ open: false }">
                            <!-- Notifications -->
                            <button class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 mr-4">
                                <span class="sr-only">Thông báo</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </button>
                            
                            <!-- User menu -->
                            <div class="relative">
                                <button @click="open = !open" class="flex items-center focus:outline-none">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-r from-primary-600 to-primary-500 flex items-center justify-center text-white">
                                        <span class="font-medium text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                    </div>
                                    <span class="ml-2 text-gray-700 text-sm hidden sm:inline-block">{{ auth()->user()->name }}</span>
                                    <svg class="ml-1 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                
                                <div x-show="open" 
                                    @click.away="open = false"
                                    x-transition:enter="transition ease-out duration-100" 
                                    x-transition:enter-start="transform opacity-0 scale-95" 
                                    x-transition:enter-end="transform opacity-100 scale-100" 
                                    x-transition:leave="transition ease-in duration-75" 
                                    x-transition:leave-start="transform opacity-100 scale-100" 
                                    x-transition:leave-end="transform opacity-0 scale-95" 
                                    class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-50">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Hồ sơ</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Cài đặt</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Đăng xuất
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    @if(session('success'))
                        <x-alert type="success">{{ session('success') }}</x-alert>
                    @endif

                    @if(session('error'))
                        <x-alert type="error">{{ session('error') }}</x-alert>
                    @endif

                    @if(session('warning'))
                        <x-alert type="warning">{{ session('warning') }}</x-alert>
                    @endif

                    @if(session('info'))
                        <x-alert type="info">{{ session('info') }}</x-alert>
                    @endif
                    
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    
    <!-- Base scripts -->
    <script src="{{ asset('js/media-selector.js') }}"></script>
    
    <!-- Scripts stacked from views -->
    @stack('scripts')
</body>
</html> 