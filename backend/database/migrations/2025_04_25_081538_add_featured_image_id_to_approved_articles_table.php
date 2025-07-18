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
        Schema::table('approved_articles', function (Blueprint $table) {
            $table->foreignId('featured_image_id')->nullable()->after('featured_image')->constrained('media')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approved_articles', function (Blueprint $table) {
            $table->dropForeign(['featured_image_id']);
            $table->dropColumn('featured_image_id');
        });
    }
};
