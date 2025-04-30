<?php

require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel for logging and HTTP client
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== Laravel AI Service Connection Test ===\n\n";

// Test if Laravel logging works
echo "Testing Laravel logging...\n";
try {
    Log::info('Laravel AI connection test started', ['timestamp' => now()]);
    echo "✓ Log entry created successfully\n";
} catch (\Exception $e) {
    echo "✘ Failed to create log entry: " . $e->getMessage() . "\n";
}

// Test if Laravel HTTP client can connect to AI service
echo "\nTesting AI service health endpoint with Laravel HTTP client...\n";
try {
    $response = Http::timeout(5)->get('http://localhost:55025/health');
    
    if ($response->successful()) {
        echo "✓ Laravel HTTP client connection successful\n";
        echo "Response: " . $response->body() . "\n";
        Log::info('Successfully connected to AI service health endpoint', [
            'status_code' => $response->status(),
            'response' => $response->json()
        ]);
    } else {
        echo "✘ Laravel HTTP client received error response: " . $response->status() . "\n";
        Log::error('Error response from AI service', [
            'status_code' => $response->status(),
            'response' => $response->body()
        ]);
    }
} catch (\Exception $e) {
    echo "✘ Laravel HTTP client exception: " . $e->getMessage() . "\n";
    Log::error('Exception while connecting to AI service', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Test if Laravel can connect to the scraper endpoint
echo "\nTesting scraper health with Laravel HTTP client...\n";
try {
    $response = Http::timeout(5)->get('http://localhost:55025/scraper/health');
    
    if ($response->successful()) {
        echo "✓ Connected to scraper endpoint successfully\n";
        echo "Response: " . $response->body() . "\n";
        Log::info('Successfully connected to scraper endpoint', [
            'status_code' => $response->status(),
            'response' => $response->json()
        ]);
    } else {
        echo "✘ Failed to connect to scraper endpoint: " . $response->status() . "\n";
        Log::error('Error response from scraper endpoint', [
            'status_code' => $response->status(),
            'response' => $response->body()
        ]);
    }
} catch (\Exception $e) {
    echo "✘ Exception while connecting to scraper: " . $e->getMessage() . "\n";
    Log::error('Exception while connecting to scraper', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Check if we can find the log file
echo "\nChecking for log file...\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    echo "✓ Log file exists: $logPath\n";
    echo "Last few lines of log file:\n";
    $logLines = array_slice(file($logPath), -5);
    echo implode('', $logLines) . "\n";
} else {
    echo "✘ Log file not found at: $logPath\n";
}

echo "\n=== Test completed ===\n"; 