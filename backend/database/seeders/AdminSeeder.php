<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Kiểm tra nếu admin đã tồn tại
        if (User::where('email', 'admin@example.com')->exists()) {
            $this->command->info('Admin đã tồn tại. Bỏ qua seed.');
            return;
        }
        
        // Tạo admin nếu chưa tồn tại
        User::create([
            'name' => 'Quản trị viên',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $this->command->info('Đã tạo tài khoản admin: admin@example.com / password');
    }
} 