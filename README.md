# Bizfunnel Licensing Client PHP SDK

A PHP SDK for validating and setting up licenses that works in both core PHP and Laravel applications.

## Installation

### Quick Start

Choose one of the following installation methods:

1. **[Manual Installation](#method-1-manual-installation)** - Copy the file directly (simplest)
2. **[Composer Installation](#method-2-composer-installation)** - Install via Composer (recommended)
3. **[Git Submodule](#method-3-git-submodule)** - Add as Git submodule
4. **[Direct Download](#method-4-direct-download)** - Download from repository

For detailed installation instructions, see [INSTALLATION.md](INSTALLATION.md).

### Method 1: Manual Installation

**For Core PHP:**
```bash
# Copy the file to your project
cp LicenseClient.php /path/to/your/project/lib/

# Include in your code
require_once __DIR__ . '/lib/LicenseClient.php';
```

**For Laravel:**
```bash
# Copy to app/Services
cp LicenseClient.php /path/to/laravel/app/Services/

# Use in your code
require_once app_path('Services/LicenseClient.php');
```

### Method 2: Composer Installation

**Option A: Local Path (Development)**
```bash
# In your project's composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/sdk"
        }
    ],
    "require": {
        "bizfunnel/licensing-client-php-sdk": "@dev"
    }
}

composer require bizfunnel/licensing-client-php-sdk:@dev
```

**Option B: Git Repository**
```bash
# Add repository to composer.json
composer config repositories.bizfunnel-license-sdk vcs https://github.com/bizfunnel/licensing-client-php-sdk.git

# Install
composer require bizfunnel/licensing-client-php-sdk
```

**Option C: Packagist (if published)**
```bash
composer require bizfunnel/licensing-client-php-sdk
```

### Method 3: Git Submodule

```bash
git submodule add https://github.com/bizfunnel/licensing-client-php-sdk.git lib/licensing-client
require_once __DIR__ . '/lib/licensing-client/LicenseClient.php';
```

### Method 4: Direct Download

```bash
curl -O https://raw.githubusercontent.com/bizfunnel/licensing-client-php-sdk/main/LicenseClient.php
# or
wget https://raw.githubusercontent.com/bizfunnel/licensing-client-php-sdk/main/LicenseClient.php
```

### Post-Installation

1. **Create storage directory:**
   ```bash
   mkdir -p storage && chmod 700 storage
   ```

2. **Add to .gitignore:**
   ```gitignore
   .license_token
   storage/.license_token
   ```

3. **Test installation:**
   ```php
   require_once 'LicenseClient.php';
   $client = new LicenseClient('https://your-server.com');
   echo "SDK installed successfully!";
   ```

## Usage

### Basic Setup

```php
// Initialize the client
$client = new LicenseClient(
    baseUrl: 'https://your-license-server.com',
    storagePath: '/path/to/storage' // Optional, defaults to current directory
);

// The SDK automatically detects if Laravel HTTP client is available
// and uses it instead of cURL for better integration
```

### Main Method: Setup or Validate License

**This is the primary method you'll use for everything.** It intelligently:
- Checks local token first (fast, no server call if valid)
- Auto-refreshes from server if token is invalid/expired
- Works for both initial setup and ongoing validation
- No authentication required

```php
try {
    $response = $client->setupOrValidateLicense(
        licenseKey: 'key_abc123_123456_20240101120000',
        domain: 'example.com',
        ip: '192.168.1.1',
        directory: '/var/www/myapp',
        checkInterval: 30, // Days between checks (7-90)
        autoRefresh: true  // Automatically refresh token if invalid (default: true)
    );

    if ($response['status'] === 'success') {
        echo "License setup successful!\n";
        echo "Local token: " . $response['data']['local_token'] . "\n";
        echo "Cached: " . ($response['data']['cached'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "Error: " . $response['message'] . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
```

### Public License Validation

This method validates a license by domain or IP without authentication.

```php
try {
    $response = $client->publicValidateLicense('example.com');
    
    if ($response['status'] === 'success') {
        echo $response['message'] . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
```

### Ongoing Validation

**Just use the same method!** For ongoing validation, simply call `setupOrValidateLicense()` again. It will automatically use the cached token if valid (fast, no server call), or refresh from server if needed.

```php
// Same method works for both initial setup and ongoing validation
$response = $client->setupOrValidateLicense(
    licenseKey: 'key_abc123_123456_20240101120000',
    domain: 'example.com',
    ip: '192.168.1.1',
    directory: '/var/www/myapp',
    checkInterval: 30
);

if ($response['status'] === 'success') {
    echo "License is valid!\n";
    
    // Check if using cached token (no server call) or fresh from server
    if ($response['data']['cached'] ?? false) {
        echo "Using cached token (fast, no server call)\n";
    } else {
        echo "Token refreshed from server\n";
    }
}
```

### Advanced Methods (Optional)

These methods are available for advanced use cases, but `setupOrValidateLicense()` is recommended for most scenarios.

#### Validate Local Token Only

Validate a stored local token without making a server request (no auto-refresh).

```php
$result = $client->validateLocalToken(
    localToken: null, // If null, loads from storage
    domain: 'example.com',
    ip: '192.168.1.1',
    directory: '/var/www/myapp',
    checkInterval: 30
);

if ($result['valid']) {
    echo "Token is valid!\n";
    print_r($result['data']); // License data
} else {
    echo "Token validation failed: " . $result['message'] . "\n";
}
```

#### Get Stored Local Token

```php
$token = $client->getLocalToken();
if ($token) {
    echo "Stored token: " . $token . "\n";
} else {
    echo "No token stored\n";
}
```

## Laravel Integration Example

```php
<?php

namespace App\Services;

use LicenseClient;

class LicenseService
{
    private LicenseClient $client;

    public function __construct()
    {
        $this->client = new LicenseClient(
            baseUrl: config('app.license_api_url'),
            storagePath: storage_path('app')
        );
    }

    /**
     * Setup or validate license (main method - handles everything)
     */
    public function setupOrValidateLicense(
        string $licenseKey,
        string $domain = null,
        string $ip = null,
        string $directory = null
    ): array {
        return $this->client->setupOrValidateLicense(
            licenseKey: $licenseKey,
            domain: $domain ?? request()->getHost(),
            ip: $ip ?? request()->ip(),
            directory: $directory ?? base_path(),
            checkInterval: 30,
            autoRefresh: true
        );
    }

    /**
     * Check if license is valid (convenience method)
     */
    public function isValid(string $licenseKey): bool
    {
        $result = $this->setupOrValidateLicense($licenseKey);
        return $result['status'] === 'success';
    }
}
```

## Core PHP Example

```php
<?php

require_once 'LicenseClient.php';

// Initialize client
$client = new LicenseClient(
    'https://your-license-server.com',
    __DIR__ . '/storage'
);

// Setup license (first time)
$response = $client->setupOrValidateLicense(
    licenseKey: 'key_abc123_123456_20240101120000',
    domain: $_SERVER['HTTP_HOST'],
    ip: $_SERVER['SERVER_ADDR'],
    directory: __DIR__,
    checkInterval: 30
);

if ($response['status'] === 'success') {
    echo "License activated!\n";
    if ($response['data']['cached'] ?? false) {
        echo "Using cached token\n";
    }
} else {
    echo "Error: " . $response['message'] . "\n";
}

// Later, just use the same method again - it handles everything automatically
$validation = $client->setupOrValidateLicense(
    licenseKey: 'key_abc123_123456_20240101120000',
    domain: $_SERVER['HTTP_HOST'],
    ip: $_SERVER['SERVER_ADDR'],
    directory: __DIR__,
    checkInterval: 30
);

if ($validation['status'] === 'success') {
    echo "License is valid\n";
    if ($validation['data']['cached'] ?? false) {
        echo "Using cached token (no server call)\n";
    }
} else {
    echo "License validation failed: " . $validation['message'] . "\n";
}
```

## Response Format

### Success Response (setupOrValidateLicense)

```php
[
    'status' => 'success',
    'message' => 'License setup successfully',
    'data' => [
        'local_token' => 'random1.hash1.encoded_data.hash2.random2',
        'cached' => false  // true if using cached token, false if fresh from server
    ]
]
```

### Success Response (validateLocalToken)

```php
[
    'status' => 'success',
    'message' => 'Local token is valid',
    'valid' => true,
    'data' => [
        'license_key' => 'key_abc123_123456_20240101120000',
        'license_status' => 'active',
        'last_checked_at' => '2024-01-01 12:00:00',
        // ... other license data
    ]
]
```

### Success Response (validateLocalTokenWithAutoRefresh)

```php
[
    'status' => 'success',
    'message' => 'Token was invalid, but successfully refreshed',
    'valid' => true,
    'refreshed' => true,  // Indicates token was refreshed
    'data' => [
        // License data
    ]
]
```

### Error Response

```php
[
    'status' => 'error',
    'message' => 'Error message here',
    'valid' => false  // For validation methods
]
```

### Warning Response (validateLocalToken)

```php
[
    'status' => 'warning',
    'message' => 'Token check interval exceeded. Please re-validate with server.',
    'valid' => false,
    'data' => [
        // License data (may be present even if expired)
    ]
]
```

## Token Storage

The SDK automatically saves the local token to a file named `.license_token` in the specified storage path. This file should be:

- Kept secure (not publicly accessible)
- Added to `.gitignore` if using version control
- Backed up appropriately

## Error Handling

Always wrap SDK calls in try-catch blocks:

```php
try {
    $response = $client->setupOrValidateLicense(...);
} catch (Exception $e) {
    // Handle exception
    error_log($e->getMessage());
}
```

## API Methods Summary

| Method | Description | Parameters |
|--------|-------------|------------|
| `__construct()` | Initialize the client | `baseUrl` (string), `storagePath` (string\|null) |
| `setupOrValidateLicense()` | Setup or validate license with auto-refresh | `licenseKey`, `domain`, `ip`, `directory`, `checkInterval` (7-90), `autoRefresh` (bool) |
| `publicValidateLicense()` | Publicly validate license by domain/IP | `domainOrIp` (string) |
| `validateLocalToken()` | Validate stored local token | `localToken` (string\|null), `domain`, `ip`, `directory`, `checkInterval` |
| `validateLocalTokenWithAutoRefresh()` | Validate and auto-refresh token | `licenseKey`, `domain`, `ip`, `directory`, `checkInterval`, `autoRefresh` (bool) |
| `getLocalToken()` | Get stored token | None |

## Requirements

- PHP 8.0 or higher
- cURL extension (for core PHP)
- JSON extension
- For Laravel: Laravel 8.0+ (optional, for HTTP client - auto-detected)

## License

This SDK is provided as-is for use with the License Management System.

