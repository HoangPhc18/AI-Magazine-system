@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Quản lý hồ sơ cá nhân</h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Thông tin cá nhân</h2>

            @if (session('status') === 'profile-updated')
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline">Hồ sơ cá nhân đã được cập nhật thành công!</span>
                </div>
            @endif
            
            @if (session('status') === 'password-updated')
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <span class="block sm:inline">Mật khẩu đã được cập nhật thành công!</span>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Họ và tên</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    @error('name')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    @error('email')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cập nhật
                    </button>
                    <a href="{{ route('profile.password') }}" class="text-blue-500 hover:text-blue-700">
                        Đổi mật khẩu
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Thông tin tài khoản</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-600 font-medium">Vai trò</p>
                    <p class="text-gray-800">{{ ucfirst($user->role) }}</p>
                </div>
                <div>
                    <p class="text-gray-600 font-medium">Trạng thái</p>
                    <p class="text-gray-800">{{ $user->status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}</p>
                </div>
                <div>
                    <p class="text-gray-600 font-medium">Ngày tạo tài khoản</p>
                    <p class="text-gray-800">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-gray-600 font-medium">Lần cập nhật cuối</p>
                    <p class="text-gray-800">{{ $user->updated_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 