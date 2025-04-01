<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('a_i_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('openai'); // openai, anthropic, mistral, ollama, custom
            $table->string('api_key')->nullable();
            $table->string('api_url')->nullable();
            $table->string('model_name')->default('gpt-3.5-turbo');
            $table->float('temperature', 8, 2)->default(0.7);
            $table->integer('max_tokens')->default(1000);
            $table->text('rewrite_prompt_template')->nullable();
            $table->boolean('auto_approval')->default(false);
            $table->integer('max_daily_rewrites')->default(5);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('a_i_settings');
    }
}; 