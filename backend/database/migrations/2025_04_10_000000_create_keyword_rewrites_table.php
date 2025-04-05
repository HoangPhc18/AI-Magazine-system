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
        Schema::create('keyword_rewrites', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('source_url')->nullable();
            $table->string('source_title')->nullable();
            $table->longText('source_content')->nullable();
            $table->longText('rewritten_content')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_rewrites');
    }
}; 