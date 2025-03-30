<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->string('api_key')->after('id');
        });
    }

    public function down()
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn('api_key');
        });
    }
}; 