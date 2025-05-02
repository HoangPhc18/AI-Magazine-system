<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FacebookPost;
use Illuminate\Support\Facades\DB;

class UpdateFacebookPostsStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update all processed posts to have 'processed' status
        DB::table('facebook_posts')
            ->where('processed', true)
            ->update(['status' => 'processed']);
            
        // Update all unprocessed posts to have 'pending' status
        DB::table('facebook_posts')
            ->where('processed', false)
            ->update(['status' => 'pending']);
            
        $this->command->info('Facebook posts status updated successfully!');
    }
}
