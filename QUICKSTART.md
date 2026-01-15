# Quick Start Guide - Bizfunnel Licensing Client PHP SDK

Get up and running with the Bizfunnel Licensing Client PHP SDK in 5 minutes!

## Step 1: Download the SDK

```bash
# Option 1: Copy the file
cp LicenseClient.php /path/to/your/project/

# Option 2: Use the installation script
./install.sh

# Option 3: Download from repository
curl -O https://raw.githubusercontent.com/bizfunnel/licensing-client-php-sdk/main/LicenseClient.php
```

## Step 2: Include in Your Code

### Core PHP
```php
<?php
require_once __DIR__ . '/LicenseClient.php';

$client = new LicenseClient(
    'https://your-license-server.com',  // Your license server URL
    __DIR__ . '/storage'                 // Where to store the token
);
```

### Laravel
```php
<?php
// In app/Services/LicenseService.php
require_once app_path('Services/LicenseClient.php');

$client = new LicenseClient(
    config('app.license_api_url'),
    storage_path('app')
);
```

## Step 3: Setup Your License

```php
try {
    $response = $client->setupOrValidateLicense(
        licenseKey: 'your-license-key',
        domain: 'example.com',
        ip: '192.168.1.1',
        directory: __DIR__,
        checkInterval: 30  // Days between checks (7-90)
    );

    if ($response['status'] === 'success') {
        echo "License activated! Token saved.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Step 4: Validate License (Later)

```php
// Validate stored token
$result = $client->validateLocalToken(
    domain: 'example.com',
    ip: '192.168.1.1',
    directory: __DIR__,
    checkInterval: 30
);

if ($result['valid']) {
    echo "License is valid!\n";
} else {
    echo "License expired or invalid. Re-validate with server.\n";
}
```

## Complete Example

```php
<?php
require_once 'LicenseClient.php';

// Initialize
$client = new LicenseClient(
    'https://your-license-server.com',
    __DIR__ . '/storage'
);

// Setup license (first time)
$response = $client->setupOrValidateLicense(
    'key_abc123_123456_20240101120000',
    $_SERVER['HTTP_HOST'],
    $_SERVER['SERVER_ADDR'],
    __DIR__,
    30
);

if ($response['status'] === 'success') {
    echo "✓ License activated\n";
    
    // Later, validate the stored token
    $validation = $client->validateLocalToken(
        domain: $_SERVER['HTTP_HOST'],
        ip: $_SERVER['SERVER_ADDR'],
        directory: __DIR__,
        checkInterval: 30
    );
    
    if ($validation['valid']) {
        echo "✓ License is valid\n";
    }
}
```

## Common Use Cases

### 1. Check License on Application Start

```php
// bootstrap.php or index.php
$client = new LicenseClient('https://your-server.com', __DIR__ . '/storage');

$validation = $client->validateLocalToken(
    domain: $_SERVER['HTTP_HOST'],
    ip: $_SERVER['SERVER_ADDR'],
    directory: __DIR__,
    checkInterval: 30
);

if (!$validation['valid']) {
    die("License validation failed. Please contact support.");
}
```

### 2. Laravel Middleware

```php
// app/Http/Middleware/ValidateLicense.php
public function handle($request, Closure $next)
{
    $client = new LicenseClient(
        config('app.license_api_url'),
        storage_path('app')
    );
    
    $result = $client->validateLocalToken(
        domain: $request->getHost(),
        ip: $request->ip(),
        directory: base_path(),
        checkInterval: 30
    );
    
    if (!$result['valid']) {
        abort(403, 'License validation failed');
    }
    
    return $next($request);
}
```

### 3. Public License Check

```php
// Check if a domain/IP has a valid license (no auth required)
$response = $client->publicValidateLicense('example.com');
echo $response['message'];
```

## Troubleshooting

**Problem:** "Class not found"
- **Solution:** Make sure you've included the file: `require_once 'LicenseClient.php';`

**Problem:** "cURL extension not found"
- **Solution:** Install PHP cURL: `sudo apt-get install php-curl` (Linux) or `brew install php-curl` (macOS)

**Problem:** "Permission denied" when saving token
- **Solution:** Create storage directory and set permissions: `mkdir -p storage && chmod 700 storage`

## Next Steps

- Read the full [README.md](README.md) for detailed API documentation
- Check [INSTALLATION.md](INSTALLATION.md) for advanced installation options
- See [example.php](example.php) for more code examples

## Need Help?

- Check the examples in `example.php` and `example-laravel.php`
- Review the API documentation in `README.md`
- Contact support if you encounter issues

