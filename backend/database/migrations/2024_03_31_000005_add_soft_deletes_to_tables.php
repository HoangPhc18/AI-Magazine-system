<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('rewritten_articles', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('rewritten_articles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}; 