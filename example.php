<?php

/**
 * Example usage of LicenseClient SDK
 * 
 * This example demonstrates how to use the LicenseClient in both
 * core PHP and Laravel environments.
 */

require_once __DIR__ . '/LicenseClient.php';

// Configuration
$baseUrl = 'http://localhost:8000'; // Change to your license server URL
$storagePath = __DIR__ . '/storage';

// Initialize the client
$client = new LicenseClient($baseUrl, $storagePath);

// Example 1: Setup or Validate License
echo "=== Example 1: Setup or Validate License ===\n";
try {
    $response = $client->setupOrValidateLicense(
        licenseKey: 'key_abc123_123456_20240101120000', // Replace with actual license key
        domain: 'example.com',
        ip: '192.168.1.1',
        directory: __DIR__,
        checkInterval: 30
    );

    if ($response['status'] === 'success') {
        echo "✓ License setup successful!\n";
        echo "  Local token saved to: " . $storagePath . "/.license_token\n";
    } else {
        echo "✗ Error: " . $response['message'] . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: Public License Validation
echo "=== Example 2: Public License Validation ===\n";
try {
    $response = $client->publicValidateLicense('example.com');
    
    if ($response['status'] === 'success') {
        echo "✓ " . $response['message'] . "\n";
    } else {
        echo "✗ " . $response['message'] . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Validate Local Token
echo "=== Example 3: Validate Local Token ===\n";
$validation = $client->validateLocalToken(
    localToken: null, // Loads from storage
    domain: 'example.com',
    ip: '192.168.1.1',
    directory: __DIR__,
    checkInterval: 30
);

if ($validation['valid']) {
    echo "✓ Token is valid!\n";
    if (isset($validation['data'])) {
        echo "  License Key: " . ($validation['data']['license_key'] ?? 'N/A') . "\n";
        echo "  Status: " . ($validation['data']['license_status'] ?? 'N/A') . "\n";
        echo "  Last Checked: " . ($validation['data']['last_checked_at'] ?? 'N/A') . "\n";
    }
} else {
    echo "✗ Token validation failed: " . $validation['message'] . "\n";
}

echo "\n";

// Example 4: Get Stored Token
echo "=== Example 4: Get Stored Token ===\n";
$token = $client->getLocalToken();
if ($token) {
    echo "✓ Token found (first 50 chars): " . substr($token, 0, 50) . "...\n";
} else {
    echo "✗ No token stored\n";
}

