<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_featured_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('media_id');
            $table->string('position')->default('featured'); // featured, thumbnail, etc.
            $table->boolean('is_main')->default(true);
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('article_id')
                  ->references('id')
                  ->on('approved_articles')
                  ->onDelete('cascade');
                  
            $table->foreign('media_id')
                  ->references('id')
                  ->on('media')
                  ->onDelete('cascade');
                  
            // Unique constraint to ensure an article doesn't have duplicate featured images
            $table->unique(['article_id', 'position', 'is_main']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_featured_images');
    }
}; 