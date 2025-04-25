<nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center transition-all hover:opacity-80">
                        @if(isset($generalConfig['logo']) && !empty($generalConfig['logo']))
                            @php
                                $logoUrl = $generalConfig['logo'];
                                // Nếu URL không bắt đầu với http hoặc / thì thêm APP_URL vào đầu
                                if (!str_starts_with($logoUrl, 'http') && !str_starts_with($logoUrl, '/')) {
                                    $logoUrl = '/' . $logoUrl;
                                }
                                
                                // Nếu URL bắt đầu với /storage nhưng không có file thì thử format khác
                                if (str_starts_with($logoUrl, '/storage/') && !file_exists(public_path(substr($logoUrl, 1)))) {
                                    // Thử loại bỏ public/ hoặc storage/ từ đường dẫn
                                    $alternateUrl = str_replace('/storage/public/', '/storage/', $logoUrl);
                                    $logoUrl = $alternateUrl;
                                }
                            @endphp
                            <img src="{{ $logoUrl }}" alt="{{ $generalConfig['site_name'] ?? 'Magazine AI' }}" class="h-8 w-auto object-contain mr-2">
                            <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">
                                {{ $generalConfig['site_name'] ?? 'Magazine AI' }}
                            </span>
                        @else
                            <div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white font-bold rounded-lg p-2 mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                            </div>
                            <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">Magazine AI</span>
                        @endif
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition-all duration-150">
                        Trang chủ
                    </a>
                    
                    <a href="{{ route('articles.all') }}" class="{{ request()->routeIs('articles.all') ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition-all duration-150">
                        Bài viết
                    </a>

                    @auth
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="border-transparent text-gray-500 hover:text-primary-600 hover:border-primary-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition-all duration-150">
                                Quản trị
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <!-- Search Box -->
                <form action="{{ route('articles.search') }}" method="GET" class="mr-4">
                    <div class="relative">
                        <input type="text" name="q" placeholder="Tìm kiếm..." class="w-64 rounded-full pl-10 pr-4 py-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm transition-all">
                        <div class="absolute left-3 top-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </form>
                
                <!-- Authentication Links -->
                @guest
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-primary-600 font-medium transition-all">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="text-sm text-white bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 px-4 py-2 rounded-full font-medium transition-all duration-300 shadow-sm hover:shadow">Đăng ký</a>
                    </div>
                @else
                    <div class="ml-3 relative" x-data="{ open: false }" x-cloak>
                        <div>
                            <button @click="open = !open" type="button" class="max-w-xs bg-white rounded-full flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open user menu</span>
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 transition-all">
                                    <span class="text-xs font-medium leading-none text-white">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </span>
                                </span>
                            </button>
                        </div>

                        <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-lg shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50 transform transition-all duration-300" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                            <!-- User Profile -->
                            <div class="block px-4 py-2 text-xs text-gray-400 border-b border-gray-100">
                                {{ auth()->user()->name }}
                            </div>

                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-all" role="menuitem" tabindex="-1">Hồ sơ cá nhân</a>
                            
                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-all" role="menuitem" tabindex="-1">Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                @endguest
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden" x-data="{ open: false }" x-cloak>
                <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-primary-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                
                <!-- Mobile menu dropdown -->
                <div x-show="open" class="fixed inset-0 z-40 bg-black bg-opacity-25" @click="open = false"></div>
                
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute top-12 right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                    <a href="{{ route('home') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Trang chủ</a>
                    <a href="{{ route('articles.all') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Bài viết</a>
                    
                    @auth
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Quản trị</a>
                        @endif
                        
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Hồ sơ cá nhân</a>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Đăng xuất</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Đăng ký</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</nav>

@if(auth()->check() && auth()->user()->role === 'admin')
    <div class="bg-yellow-100 p-2 text-xs text-yellow-800 hidden">
        <strong>Debug Logo:</strong>
        @if(isset($generalConfig['logo']) && !empty($generalConfig['logo']))
            Logo URL: {{ $generalConfig['logo'] }}
        @else
            No logo URL set in configuration
        @endif
        
        @if(isset($generalConfig['logo_media_id']) && !empty($generalConfig['logo_media_id']))
            <br>Logo Media ID: {{ $generalConfig['logo_media_id'] }}
        @else
            <br>No logo_media_id set in configuration
        @endif
        
        <button class="bg-yellow-200 hover:bg-yellow-300 px-2 py-1 rounded ml-2" onclick="this.parentElement.classList.toggle('hidden')">Toggle Debug</button>
    </div>
@endif 