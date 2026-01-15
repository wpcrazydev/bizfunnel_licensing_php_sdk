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
```

### Setup or Validate License

This method sets up a new license or validates an existing one. No authentication required.

```php
try {
    $response = $client->setupOrValidateLicense(
        licenseKey: 'key_abc123_123456_20240101120000',
        domain: 'example.com',
        ip: '192.168.1.1',
        directory: '/var/www/myapp',
        checkInterval: 30 // Days between checks (7-90)
    );

    if ($response['status'] === 'success') {
        echo "License setup successful!\n";
        echo "Local token: " . $response['data']['local_token'] . "\n";
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

### Validate Local Token

Validate a stored local token without making a server request.

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

### Get Stored Local Token

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

    public function setupLicense(string $licenseKey, string $domain, string $ip, string $directory): array
    {
        return $this->client->setupOrValidateLicense(
            licenseKey: $licenseKey,
            domain: $domain,
            ip: $ip,
            directory: $directory,
            checkInterval: 30
        );
    }

    public function validateLicense(): bool
    {
        $result = $this->client->validateLocalToken(
            domain: request()->getHost(),
            ip: request()->ip(),
            directory: base_path(),
            checkInterval: 30
        );

        return $result['valid'];
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

// Setup license
$response = $client->setupOrValidateLicense(
    'key_abc123_123456_20240101120000',
    $_SERVER['HTTP_HOST'],
    $_SERVER['SERVER_ADDR'],
    __DIR__,
    30
);

if ($response['status'] === 'success') {
    echo "License activated!\n";
} else {
    echo "Error: " . $response['message'] . "\n";
}

// Later, validate local token
$validation = $client->validateLocalToken(
    null,
    $_SERVER['HTTP_HOST'],
    $_SERVER['SERVER_ADDR'],
    __DIR__,
    30
);

if ($validation['valid']) {
    echo "License is valid\n";
} else {
    echo "License validation failed: " . $validation['message'] . "\n";
}
```

## Response Format

### Success Response

```php
[
    'status' => 'success',
    'message' => 'License setup successfully',
    'data' => [
        'local_token' => 'random1.hash1.encoded_data.hash2.random2'
    ]
]
```

### Error Response

```php
[
    'status' => 'error',
    'message' => 'Error message here'
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

## Requirements

- PHP 8.0 or higher
- cURL extension (for core PHP)
- For Laravel: Laravel 8.0+ (optional, for HTTP client)

## License

This SDK is provided as-is for use with the License Management System.

