<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For each category, create at least one subcategory
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->info('No categories found. Please run CategorySeeder first.');
            return;
        }
        
        foreach ($categories as $category) {
            // Create two subcategories for each category
            for ($i = 1; $i <= 2; $i++) {
                $name = $category->name . ' Subcategory ' . $i;
                $slug = Str::slug($name);
                
                Subcategory::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => 'This is a test subcategory for ' . $category->name,
                    'parent_category_id' => $category->id,
                ]);
            }
        }
        
        $this->command->info('Subcategories seeded successfully!');
    }
} 