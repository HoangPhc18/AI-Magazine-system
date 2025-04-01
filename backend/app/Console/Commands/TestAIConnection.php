<?php

namespace App\Console\Commands;

use App\Models\AISetting;
use App\Services\AIService;
use Illuminate\Console\Command;

class TestAIConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test-connection {--prompt=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the connection to the configured AI provider';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing AI connection...');
        
        $settings = AISetting::first();
        
        if (!$settings) {
            $this->error('AI settings are not configured. Please configure settings first.');
            return 1;
        }
        
        $this->info('Provider: ' . $settings->provider);
        $this->info('Model: ' . $settings->model_name);
        
        // Test with prompt if provided, otherwise just test connection
        $prompt = $this->option('prompt') ?? 'Write a short test sentence.';
        
        $aiService = new AIService();
        $result = $aiService->generateContent($prompt);
        
        if ($result['success']) {
            $this->info('Connection successful!');
            if (!empty($result['content'])) {
                $this->line('');
                $this->line('AI Response:');
                $this->line('------------');
                $this->line($result['content']);
            }
            return 0;
        } else {
            $this->error('Connection failed: ' . $result['error']);
            return 1;
        }
    }
} 