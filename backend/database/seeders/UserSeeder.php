<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 5 người dùng với vai trò khác nhau (admin, editor, user)
        $users = [
            [
                'name' => 'Người dùng 1',
                'email' => 'user1@example.com',
                'password' => 'password',
                'role' => 'user',
            ],
            [
                'name' => 'Người dùng 2',
                'email' => 'user2@example.com',
                'password' => 'password',
                'role' => 'user',
            ],
            [
                'name' => 'Người dùng 3',
                'email' => 'user3@example.com',
                'password' => 'password',
                'role' => 'user',
            ],
            [
                'name' => 'Biên tập viên 1',
                'email' => 'editor1@example.com',
                'password' => 'password',
                'role' => 'editor',
            ],
            [
                'name' => 'Biên tập viên 2',
                'email' => 'editor2@example.com',
                'password' => 'password',
                'role' => 'editor',
            ],
        ];
        
        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("Đã tạo người dùng: {$userData['name']} ({$userData['email']})");
        }
    }
} 