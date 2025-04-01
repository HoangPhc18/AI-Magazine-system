<?php

namespace App\Console\Commands;

use App\Models\AISetting;
use Illuminate\Console\Command;

class InitializeAISettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:init {--force : Force re-initialization of settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize default AI settings if they don\'t exist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $existingSettings = AISetting::first();
        
        if ($existingSettings && !$this->option('force')) {
            $this->info('AI settings already exist. Use --force to reinitialize.');
            return 0;
        }
        
        if ($existingSettings) {
            $this->info('Reinitializing AI settings...');
            $existingSettings->delete();
        } else {
            $this->info('Initializing AI settings...');
        }
        
        $defaultSettings = [
            'provider' => 'openai',
            'api_key' => '',
            'api_url' => '',
            'model_name' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'rewrite_prompt_template' => 'Rewrite the following article to make it more engaging and informative, while maintaining the original meaning. Focus on improving readability, SEO optimization, and overall quality. Keep the same headings and key points, but enhance the language and flow. Article content: {article}',
            'auto_approval' => false,
            'max_daily_rewrites' => 5
        ];
        
        AISetting::create($defaultSettings);
        
        $this->info('Default AI settings have been initialized successfully.');
        return 0;
    }
} 