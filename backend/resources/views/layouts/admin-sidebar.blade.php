<!-- Sidebar -->
<div class="h-screen flex overflow-hidden bg-gray-50" x-data="{ mobileMenuOpen: false }">
    <div class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-64">
            <div class="flex flex-col h-0 flex-1 bg-gradient-to-b from-primary-700 to-primary-900 shadow-lg">
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
                                <!-- <a href="{{ route('admin.articles.index') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.articles.index') ? 'bg-primary-500 text-opacity-100' : '' }}">
                                    <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.articles.index') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                    Tất cả bài viết
                                </a> -->
                                <!-- <a href="{{ route('admin.articles.create') }}" class="flex items-center pl-3 pr-2 py-2 text-sm font-medium rounded-md text-white text-opacity-70 hover:text-opacity-100 hover:bg-primary-500 transition-all duration-150 {{ request()->routeIs('admin.articles.create') ? 'bg-primary-500 text-opacity-100' : '' }}">
                                    <svg class="w-4 h-4 mr-2 {{ request()->routeIs('admin.articles.create') ? 'text-white' : 'text-white text-opacity-70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Tạo bài viết mới
                                </a> -->
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

                        <a href="{{ route('admin.keyword-rewrites.index') }}" 
                            class="{{ request()->routeIs('admin.keyword-rewrites.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                            <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.keyword-rewrites.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Tạo bài từ từ khóa
                        </a>

                        <a href="{{ route('admin.categories.index') }}" 
                            class="{{ request()->routeIs('admin.categories.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                            <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.categories.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Danh mục
                        </a>

                        <!-- <a href="{{ route('admin.ai-settings.index') }}" 
                            class="{{ request()->routeIs('admin.ai-settings.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200">
                            <svg class="mr-3 h-5 w-5 text-white {{ request()->routeIs('admin.ai-settings.*') ? 'opacity-100' : 'opacity-75 group-hover:opacity-100' }} transition-opacity" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Cài đặt AI
                        </a> -->
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
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden">
        <div class="bg-gradient-to-b from-primary-700 to-primary-900 pb-32">
            <nav class="bg-primary-800 border-b border-primary-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="flex-shrink-0 flex items-center">
                                <h1 class="text-white text-lg font-bold">Magazine AI</h1>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="-mr-2">
                                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-primary-200 hover:text-white hover:bg-primary-600 focus:outline-none transition-all duration-200">
                                    <span class="sr-only">Mở menu chính</span>
                                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Mobile menu dropdown -->
            <div x-show="mobileMenuOpen" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
                class="border-b border-primary-700 md:hidden">
                <div class="px-2 py-3 space-y-1 sm:px-3">
                    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} transition-all duration-150">Trang chủ</a>
                    
                    <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.users.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} transition-all duration-150">Người dùng</a>
                    
                    <!-- Mobile Articles Dropdown -->
                    <div x-data="{ mobileArticlesOpen: {{ request()->routeIs('admin.articles.*') || request()->routeIs('admin.rewritten-articles.*') || request()->routeIs('admin.approved-articles.*') ? 'true' : 'false' }} }">
                        <button @click="mobileArticlesOpen = !mobileArticlesOpen" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-white text-opacity-80 hover:bg-primary-600 hover:text-white transition-all duration-150 flex justify-between items-center">
                            <span>Bài viết</span>
                            <svg class="h-5 w-5 transform transition-transform duration-200" 
                                :class="{'rotate-180': mobileArticlesOpen}" 
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        
                        <div x-show="mobileArticlesOpen" 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform -translate-y-2 opacity-0"
                            x-transition:enter-end="transform translate-y-0 opacity-100"
                            class="pl-6 mt-1 space-y-1">
                            <a href="{{ route('admin.articles.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.articles.index') ? 'bg-primary-500 text-white' : 'text-white text-opacity-70 hover:bg-primary-500 hover:text-opacity-100' }} transition-all duration-150">Tất cả bài viết</a>
                            
                            <a href="{{ route('admin.articles.create') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.articles.create') ? 'bg-primary-500 text-white' : 'text-white text-opacity-70 hover:bg-primary-500 hover:text-opacity-100' }} transition-all duration-150">Tạo bài viết mới</a>
                            
                            <a href="{{ route('admin.rewritten-articles.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.rewritten-articles.index') ? 'bg-primary-500 text-white' : 'text-white text-opacity-70 hover:bg-primary-500 hover:text-opacity-100' }} transition-all duration-150">Bài viết AI</a>
                            
                            <a href="{{ route('admin.approved-articles.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.approved-articles.index') ? 'bg-primary-500 text-white' : 'text-white text-opacity-70 hover:bg-primary-500 hover:text-opacity-100' }} transition-all duration-150">Bài viết đã duyệt</a>
                        </div>
                    </div>
                    
                    <a href="{{ route('admin.keyword-rewrites.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.keyword-rewrites.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} transition-all duration-150">Tạo bài từ từ khóa</a>
                    
                    <a href="{{ route('admin.categories.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.categories.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} transition-all duration-150">Danh mục</a>
                    
                    <a href="{{ route('admin.ai-settings.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('admin.ai-settings.*') ? 'bg-primary-600 text-white' : 'text-white text-opacity-80 hover:bg-primary-600 hover:text-white' }} transition-all duration-150">Cài đặt AI</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex flex-col w-0 flex-1 overflow-hidden">
        <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow-sm">
            <div class="flex-1 px-4 flex justify-between">
                <div class="flex-1 flex">
                    <form class="w-full flex md:ml-0" action="#" method="GET">
                        <label for="search-field" class="sr-only">Tìm kiếm</label>
                        <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                            <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input id="search-field" class="block w-full h-full pl-8 pr-3 py-2 border-transparent text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent rounded-md sm:text-sm transition-all" placeholder="Tìm kiếm" type="search" name="search">
                        </div>
                    </form>
                </div>
                
                <div class="ml-4 flex items-center md:ml-6">
                    <!-- Notifications -->
                    <button class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="sr-only">Thông báo</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>

                    <!-- Profile dropdown -->
                    <div class="ml-3 relative" x-data="{ open: false }">
                        <div>
                            <button @click="open = !open" type="button" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Mở menu người dùng</span>
                                <div class="h-8 w-8 rounded-full flex items-center justify-center bg-gradient-to-r from-primary-600 to-primary-500 text-white">
                                    <span class="font-medium">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                </div>
                            </button>
                        </div>
                        
                        <div x-show="open" 
                            @click.away="open = false" 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                            <div class="block px-4 py-2 text-xs text-gray-400 border-b">{{ auth()->user()->email }}</div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors" role="menuitem" tabindex="-1">Hồ sơ của bạn</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors" role="menuitem" tabindex="-1">Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 relative overflow-y-auto focus:outline-none bg-gray-50">
            <div class="py-6 px-4 sm:px-6 lg:px-8 min-h-screen">
                @yield('content')
            </div>
        </main>
    </div>
</div> 