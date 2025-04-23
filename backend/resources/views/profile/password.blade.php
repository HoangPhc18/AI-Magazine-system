@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Đổi mật khẩu</h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="current_password" class="block text-gray-700 text-sm font-bold mb-2">Mật khẩu hiện tại</label>
                    <input id="current_password" type="password" name="current_password" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    @error('current_password')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Mật khẩu mới</label>
                    <input id="password" type="password" name="password" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    @error('password')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">Xác nhận mật khẩu mới</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cập nhật mật khẩu
                    </button>
                    <a href="{{ route('profile.edit') }}" class="text-blue-500 hover:text-blue-700">
                        Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 