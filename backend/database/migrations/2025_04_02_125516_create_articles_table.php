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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('source_name');
            $table->string('source_url');
            $table->string('source_icon')->nullable();
            $table->timestamp('published_at');
            $table->string('category')->nullable();
            $table->json('meta_data')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->boolean('is_ai_rewritten')->default(false);
            $table->text('ai_rewritten_content')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
