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
        // Trước tiên, xóa ràng buộc khóa ngoại hiện tại
        Schema::table('article_media', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
        });

        // Cập nhật ràng buộc khóa ngoại tới bảng approved_articles
        Schema::table('article_media', function (Blueprint $table) {
            $table->foreign('article_id')
                  ->references('id')
                  ->on('approved_articles')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa ràng buộc khóa ngoại đến approved_articles
        Schema::table('article_media', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
        });

        // Khôi phục lại ràng buộc khóa ngoại đến articles
        Schema::table('article_media', function (Blueprint $table) {
            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');
        });
    }
}; 