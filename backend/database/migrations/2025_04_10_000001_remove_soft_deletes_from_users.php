<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Xóa trường deleted_at khỏi bảng users
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Thêm lại trường deleted_at vào bảng users
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
}; 