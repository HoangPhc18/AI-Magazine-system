<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('openai_api_key')->nullable();
            $table->string('ollama_api_url')->default('http://localhost:11434');
            $table->string('model_name')->default('gemma:2b');
            $table->integer('max_tokens')->default(2000);
            $table->float('temperature')->default(0.7);
            $table->float('top_p')->default(0.9);
            $table->float('frequency_penalty')->default(0.0);
            $table->float('presence_penalty')->default(0.0);
            $table->text('system_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_settings');
    }
}; 