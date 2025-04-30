<?php

echo "=== AI Service Connection Test ===\n\n";

// Test both HTTP and cURL methods for redundancy
echo "Testing AI service health endpoint (HTTP)...\n";
try {
    // Create stream context with timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 5, // 5 seconds timeout
            'ignore_errors' => true
        ]
    ]);
    
    // Make the HTTP request
    $response = @file_get_contents('http://localhost:55025/health', false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        echo "✘ Failed to connect via HTTP: " . ($error['message'] ?? 'Unknown error') . "\n";
    } else {
        echo "✓ HTTP connection successful\n";
        echo "Response: " . $response . "\n";
    }
} catch (Exception $e) {
    echo "✘ Exception occurred in HTTP request: " . $e->getMessage() . "\n";
}

echo "\nTesting AI service health endpoint (cURL)...\n";
try {
    // Initialize cURL session
    $ch = curl_init('http://localhost:55025/health');
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Check response
    if ($response === false) {
        echo "✘ Failed to connect via cURL: " . $error . "\n";
    } else {
        echo "✓ cURL connection successful (HTTP Code: $httpCode)\n";
        echo "Response: " . $response . "\n";
    }
    
    // Close cURL session
    curl_close($ch);
} catch (Exception $e) {
    echo "✘ Exception occurred in cURL request: " . $e->getMessage() . "\n";
}

// Check each service endpoint
$services = [
    'scraper' => 'http://localhost:55025/scraper/health',
    'rewrite' => 'http://localhost:55025/rewrite/health',
    'keyword-rewrite' => 'http://localhost:55025/keyword-rewrite/health',
    'facebook-scraper' => 'http://localhost:55025/facebook-scraper/health',
    'facebook-rewrite' => 'http://localhost:55025/facebook-rewrite/health'
];

echo "\nTesting individual AI services...\n";
foreach ($services as $name => $url) {
    echo "\nTesting $name service ($url)...\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($response === false) {
        echo "✘ Failed to connect to $name service: " . $error . "\n";
    } else {
        echo "✓ $name service connection successful (HTTP Code: $httpCode)\n";
        echo "Response: " . $response . "\n";
    }
    
    curl_close($ch);
}

echo "\n=== Test completed ===\n"; 