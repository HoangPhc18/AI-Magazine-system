<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->string('setting_key')->default('openai')->after('id');
            $table->text('setting_value')->nullable()->after('setting_key');
            $table->string('api_key')->nullable()->after('setting_value');
            $table->string('model')->default('gpt-3.5-turbo')->after('api_key');
            $table->float('temperature')->default(0.7)->after('model');
            $table->integer('max_tokens')->default(1000)->after('temperature');
            $table->float('top_p')->default(1)->after('max_tokens');
            $table->float('frequency_penalty')->default(0)->after('top_p');
            $table->float('presence_penalty')->default(0)->after('frequency_penalty');
        });
    }

    public function down()
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn([
                'setting_key',
                'setting_value',
                'api_key',
                'model',
                'temperature',
                'max_tokens',
                'top_p',
                'frequency_penalty',
                'presence_penalty'
            ]);
        });
    }
}; 