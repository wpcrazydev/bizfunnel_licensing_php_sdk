# Bizfunnel Licensing Client PHP SDK - File Index

This directory contains the complete Bizfunnel Licensing Client PHP SDK with all necessary files for installation and usage.

## Core Files

### `LicenseClient.php`
**Main SDK file** - The core class that handles all license validation operations.
- Works with both core PHP and Laravel
- Auto-detects Laravel HTTP client
- Handles token storage and validation
- **Required for all installations**

## Documentation

### `README.md`
**Main documentation** - Complete API reference and usage examples.
- Installation methods
- API documentation
- Code examples
- Response formats
- Error handling

### `INSTALLATION.md`
**Detailed installation guide** - Step-by-step installation instructions.
- Multiple installation methods
- Post-installation steps
- Troubleshooting
- Directory structure recommendations

### `QUICKSTART.md`
**Quick start guide** - Get started in 5 minutes.
- Fast setup instructions
- Common use cases
- Basic examples
- Troubleshooting tips

## Examples

### `example.php`
**Core PHP example** - Demonstrates usage in plain PHP projects.
- Basic setup
- License validation
- Token management with auto-refresh
- Error handling
- All available methods

### `example-laravel.php`
**Laravel integration example** - Shows how to integrate in Laravel.
- Service class example
- Controller usage
- Configuration examples
- Auto-refresh validation
- Best practices

## Installation Tools

### `install.sh`
**Installation script** - Automated installation helper.
- Checks PHP requirements
- Validates cURL extension
- Multiple installation methods
- Creates necessary directories
- Updates .gitignore

### `composer.json`
**Composer package definition** - For Composer-based installations.
- Package metadata
- Dependencies
- Autoloading configuration

## Configuration

### `.gitignore`
**Git ignore rules** - Prevents committing sensitive files.
- Ignores license tokens
- Protects storage directory

## Installation Methods Summary

1. **Manual Copy** (Simplest)
   - Copy `LicenseClient.php` to your project
   - Include with `require_once`

2. **Composer** (Recommended)
   - Use `composer.json` for package installation
   - Automatic autoloading

3. **Installation Script**
   - Run `./install.sh` for guided installation
   - Handles setup automatically

4. **Git Submodule**
   - Add as submodule for version control
   - Easy updates

## Quick Reference

### Minimum Requirements
- PHP 8.0+
- cURL extension
- JSON extension
- Laravel 8.0+ (optional, for HTTP client - auto-detected)

### File Structure
```
sdk/
├── LicenseClient.php      # Main SDK (REQUIRED)
├── README.md              # Full documentation
├── INSTALLATION.md        # Installation guide
├── QUICKSTART.md          # Quick start
├── example.php            # Core PHP example
├── example-laravel.php    # Laravel example
├── install.sh             # Installation script
├── composer.json          # Composer config
└── .gitignore            # Git ignore rules
```

### Available Methods
- `setupOrValidateLicense()` - Setup/validate with auto-refresh
- `publicValidateLicense()` - Public validation by domain/IP
- `validateLocalToken()` - Validate stored token locally
- `validateLocalTokenWithAutoRefresh()` - Validate with auto-refresh
- `getLocalToken()` - Get stored token

### Installation Priority
1. Read `QUICKSTART.md` for fastest setup
2. Check `INSTALLATION.md` for detailed steps
3. Review `README.md` for complete API docs
4. See examples for code patterns

## Support

For issues or questions:
1. Check the documentation files
2. Review the example files
3. Consult the troubleshooting sections
4. Contact support if needed

