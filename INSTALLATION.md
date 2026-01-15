# Installation Guide - Bizfunnel Licensing Client PHP SDK

This guide covers different installation methods for the Bizfunnel Licensing Client PHP SDK.

## Method 1: Manual Installation (Simplest)

### For Core PHP Projects

1. **Download the SDK file**
   ```bash
   # Download LicenseClient.php to your project
   ```

2. **Copy to your project directory**
   ```bash
   # Example: Copy to a lib or includes directory
   mkdir -p /path/to/your/project/lib
   cp LicenseClient.php /path/to/your/project/lib/
   ```

3. **Include in your code**
   ```php
   require_once __DIR__ . '/lib/LicenseClient.php';
   
   $client = new LicenseClient(
       'https://your-license-server.com',
       'your-api-token',
       __DIR__ . '/storage'
   );
   ```

### For Laravel Projects

1. **Copy the SDK file**
   ```bash
   # Copy to app/Services directory
   cp LicenseClient.php /path/to/laravel/app/Services/
   ```

2. **Use in your code**
   ```php
   // In app/Services/LicenseService.php or any service class
   require_once app_path('Services/LicenseClient.php');
   
   // Or use it directly
   $client = new LicenseClient(
       config('app.license_api_url'),
       auth()->user()?->api_token,
       storage_path('app')
   );
   ```

## Method 2: Composer Installation (Recommended)

### Step 1: Create a Composer Package

If you want to distribute the SDK via Composer, you can create a `composer.json` file:

```json
{
    "name": "bizfunnel/licensing-client-php-sdk",
    "description": "Bizfunnel Licensing Client PHP SDK",
    "type": "library",
    "require": {
        "php": "^8.0",
        "ext-curl": "*"
    },
    "autoload": {
        "files": ["LicenseClient.php"]
    }
}
```

### Step 2: Install via Composer

**Option A: Install from local path**
```bash
# In your client project's composer.json
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

# Then run
composer require bizfunnel/licensing-client-php-sdk:@dev
```

**Option B: Install from Git repository**
```bash
# In your client project's composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/bizfunnel/licensing-client-php-sdk.git"
        }
    ],
    "require": {
        "bizfunnel/licensing-client-php-sdk": "^1.0"
    }
}

# Then run
composer require bizfunnel/licensing-client-php-sdk
```

**Option C: Install from Packagist (if published)**
```bash
composer require bizfunnel/licensing-client-php-sdk
```

### Step 3: Use in your code

```php
// The class will be autoloaded automatically
$client = new LicenseClient(
    'https://your-license-server.com',
    'your-api-token',
    storage_path('app') // For Laravel
);
```

## Method 3: Git Submodule (For Git Projects)

1. **Add as submodule**
   ```bash
   git submodule add https://github.com/bizfunnel/licensing-client-php-sdk.git lib/licensing-client
   ```

2. **Include in your code**
   ```php
   require_once __DIR__ . '/lib/licensing-client/LicenseClient.php';
   ```

3. **Update submodule**
   ```bash
   git submodule update --remote
   ```

## Method 4: Direct Download from Repository

1. **Download the SDK**
   ```bash
   # Using curl
   curl -O https://raw.githubusercontent.com/bizfunnel/licensing-client-php-sdk/main/LicenseClient.php
   
   # Or using wget
   wget https://raw.githubusercontent.com/bizfunnel/licensing-client-php-sdk/main/LicenseClient.php
   ```

2. **Place in your project and include**
   ```php
   require_once __DIR__ . '/LicenseClient.php';
   ```

## Post-Installation Steps

### 1. Create Storage Directory

```bash
# Create a secure directory for token storage
mkdir -p storage
chmod 700 storage  # Make it readable only by owner
```

### 2. Add to .gitignore

Add the token storage file to your `.gitignore`:

```gitignore
# License token
.license_token
storage/.license_token
```

### 3. Configure Environment Variables

**For Laravel (.env):**
```env
LICENSE_API_URL=https://your-license-server.com
LICENSE_CHECK_INTERVAL=30
```

**For Core PHP:**
```php
// config.php
define('LICENSE_API_URL', 'https://your-license-server.com');
define('LICENSE_CHECK_INTERVAL', 30);
```

### 4. Test Installation

Create a test file to verify installation:

```php
<?php
// test-installation.php

require_once 'LicenseClient.php';

try {
    $client = new LicenseClient(
        'https://your-license-server.com',
        null, // No token needed for public validation
        __DIR__ . '/storage'
    );
    
    echo "✓ LicenseClient class loaded successfully!\n";
    echo "✓ SDK is ready to use!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

Run the test:
```bash
php test-installation.php
```

## Directory Structure Recommendations

### Core PHP Project
```
your-project/
├── lib/
│   └── LicenseClient.php
├── storage/
│   └── .license_token (auto-generated)
├── config.php
└── index.php
```

### Laravel Project
```
laravel-project/
├── app/
│   └── Services/
│       └── LicenseClient.php (optional)
├── storage/
│   └── app/
│       └── .license_token (auto-generated)
└── config/
    └── license.php (optional config file)
```

## Troubleshooting

### Issue: Class not found
**Solution:** Make sure you've included the file:
```php
require_once __DIR__ . '/path/to/LicenseClient.php';
```

### Issue: cURL not available
**Solution:** Install PHP cURL extension:
```bash
# Ubuntu/Debian
sudo apt-get install php-curl

# macOS (Homebrew)
brew install php-curl

# Enable in php.ini
extension=curl
```

### Issue: Permission denied for token storage
**Solution:** Set proper permissions:
```bash
chmod 755 storage
chmod 644 storage/.license_token
```

### Issue: Laravel HTTP client not detected
**Solution:** This is normal for core PHP. The SDK will use cURL instead.

## Next Steps

After installation, see the [README.md](README.md) for usage examples and API documentation.

