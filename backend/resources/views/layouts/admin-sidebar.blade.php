<!-- Sidebar component - optimized for new layout -->
<div class="h-full flex flex-col bg-gradient-to-b from-primary-700 to-primary-900 shadow-lg overflow-hidden" x-data="{ mobileMenuOpen: false }">
    <!-- Desktop sidebar content -->
    <div class="h-full flex flex-col">
        <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
            <div class="flex items-center flex-shrink-0 px-4 mb-5">
                <div class="bg-white bg-opacity-10 p-2 rounded-lg flex items-center">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <h1 class="ml-2 text-xl font-bold text-white">Magazine AI</h1>
                </div>
            </div>
            
            <nav class="mt-2 flex-1 px-3 space-y-1">
                <a href="{{ route('admin.dashboard') }}" 
                    class="{{ request()->routeIs('admin.dashboard') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.dashboard') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Tổng quan
                </a>

                <a href="{{ route('admin.users.index') }}" 
                    class="{{ request()->routeIs('admin.users.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.users.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Quản lý người dùng
                </a>

                <!-- Articles Management -->
                <div x-data="{ open: {{ request()->routeIs('admin.articles.*') || request()->routeIs('admin.rewritten-articles.*') || request()->routeIs('admin.approved-articles.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg text-white text-opacity-80 hover:bg-primary-600 hover:text-white focus:outline-none transition-all duration-200 group">
                        <svg class="mr-3 h-5 w-5 text-white opacity-75 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        <span>Bài viết</span>
                        <svg class="ml-auto h-4 w-4 text-white opacity-75 transform transition-transform duration-200"
                            :class="{'rotate-90': open, 'rotate-0': !open}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform -translate-y-2 opacity-0"
                        x-transition:enter-end="transform translate-y-0 opacity-100" 
                        class="pl-7 pr-2 mt-1 space-y-1">
                        <a href="{{ route('admin.rewritten-articles.index') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.rewritten-articles.index') ? 'bg-primary-500 text-opacity-100' : '' }}">
                            <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.rewritten-articles.index') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                            Bài viết AI
                        </a>
                        <a href="{{ route('admin.approved-articles.index') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.approved-articles.index') ? 'bg-primary-500 text-opacity-100' : '' }}">
                            <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.approved-articles.index') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Bài viết đã duyệt
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.media.index') }}" 
                    class="{{ request()->routeIs('admin.media.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.media.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Quản lý Media
                </a>

                <a href="{{ route('admin.keyword-rewrites.index') }}" 
                    class="{{ request()->routeIs('admin.keyword-rewrites.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.keyword-rewrites.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Tạo bài từ từ khóa
                </a>

                <a href="{{ route('admin.facebook-posts.index') }}" 
                    class="{{ request()->routeIs('admin.facebook-posts.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.facebook-posts.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                    Bài viết Facebook
                </a>

                <a href="{{ route('admin.categories.index') }}" 
                    class="{{ request()->routeIs('admin.categories.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.categories.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Danh mục
                </a>

                <a href="{{ route('admin.ai-settings.index') }}" 
                    class="{{ request()->routeIs('admin.ai-settings.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.ai-settings.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Cài đặt AI
                </a>
                
                <!-- Website Configuration Dropdown -->
                <div x-data="{ open: {{ request()->routeIs('admin.website-config.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-lg text-white text-opacity-80 hover:bg-primary-600 hover:text-white focus:outline-none transition-all duration-200 group">
                        <svg class="mr-3 h-5 w-5 text-white opacity-75 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Cấu hình website</span>
                        <svg class="ml-auto h-4 w-4 text-white opacity-75 transform transition-transform duration-200"
                            :class="{'rotate-90': open, 'rotate-0': !open}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform -translate-y-2 opacity-0"
                        x-transition:enter-end="transform translate-y-0 opacity-100" 
                        class="pl-7 pr-2 mt-1 space-y-1">
                        <a href="{{ route('admin.website-config.general') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.website-config.general') ? 'bg-primary-500 text-opacity-100' : '' }}">
                            <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.website-config.general') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Thông tin chung
                        </a>
                        <a href="{{ route('admin.website-config.seo') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.website-config.seo') ? 'bg-primary-500 text-opacity-100' : '' }}">
                            <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.website-config.seo') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Cấu hình SEO
                        </a>
                        <a href="{{ route('admin.website-config.social') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.website-config.social') ? 'bg-primary-500 text-opacity-100' : '' }}">
                            <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.website-config.social') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                            </svg>
                            Mạng xã hội
                        </a>
                        <a href="{{ route('admin.website-config.metadata') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.website-config.metadata') ? 'bg-primary-500 text-opacity-100' : '' }}">
                            <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.website-config.metadata') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Metadata
                        </a>
                    </div>
                </div>
            </nav>
        </div>
        <div class="flex-shrink-0 flex border-t border-primary-800 p-4">
            <div class="flex-shrink-0 w-full group block">
                <div class="flex items-center">
                    <div class="inline-flex h-9 w-9 rounded-full bg-primary-800 text-white items-center justify-center">
                        <span class="font-medium text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white group-hover:text-white">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="text-xs font-medium text-primary-200 group-hover:text-primary-100">
                            {{ ucfirst(auth()->user()->role) }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                        @csrf
                        <button type="submit" class="text-primary-200 hover:text-white" title="Đăng xuất">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu trigger - always visible on mobile -->
    <div class="lg:hidden fixed top-0 right-0 p-4 z-50">
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-md bg-primary-600 text-white focus:outline-none focus:ring-2 focus:ring-white" aria-label="Main menu">
            <svg class="h-6 w-6" :class="{'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg class="h-6 w-6" :class="{'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    
    <!-- Mobile menu overlay -->
    <div x-show="mobileMenuOpen" 
        x-transition:enter="transition-opacity ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in-out duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden">
    </div>
    
    <!-- Mobile sidebar - slides in from left -->
    <div x-show="mobileMenuOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="transform -translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform -translate-x-full"
        class="fixed inset-y-0 left-0 flex flex-col w-64 max-w-[80%] bg-gradient-to-b from-primary-700 to-primary-900 z-30 lg:hidden">
    
        <!-- Mobile sidebar content - use same content as desktop -->
        <div class="h-full flex flex-col pt-5 overflow-y-auto">
            <!-- Logo and header -->
            <div class="flex items-center flex-shrink-0 px-4 mb-5">
                <div class="bg-white bg-opacity-10 p-2 rounded-lg flex items-center">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <h1 class="ml-2 text-xl font-bold text-white">Magazine AI</h1>
                </div>
            </div>
            
            <!-- Mobile navigation links - same structure as desktop -->
            <nav class="mt-2 flex-1 px-3 space-y-1">
                <!-- Menu items are the same as desktop -->
                <a href="{{ route('admin.dashboard') }}" 
                    class="{{ request()->routeIs('admin.dashboard') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                    <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.dashboard') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Tổng quan
                </a>
                
                <!-- ... existing menu items ... -->
            </nav>
            
            <!-- Mobile user info -->
            <div class="flex-shrink-0 flex border-t border-primary-800 p-4">
                <div class="flex-shrink-0 w-full group block">
                    <div class="flex items-center">
                        <div class="inline-flex h-9 w-9 rounded-full bg-primary-800 text-white items-center justify-center">
                            <span class="font-medium text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white group-hover:text-white">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs font-medium text-primary-200 group-hover:text-primary-100">
                                {{ ucfirst(auth()->user()->role) }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                            @csrf
                            <button type="submit" class="text-primary-200 hover:text-white" title="Đăng xuất">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 