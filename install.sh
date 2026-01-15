#!/bin/bash

# Bizfunnel Licensing Client PHP SDK Installation Script
# This script helps install the Bizfunnel Licensing Client PHP SDK in your project

set -e

echo "🚀 Bizfunnel Licensing Client PHP SDK Installation Script"
echo "=================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed. Please install PHP 8.0 or higher."
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
print_success "PHP $PHP_VERSION found"

# Check PHP version (8.0+)
PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')

if [ "$PHP_MAJOR" -lt 8 ]; then
    print_error "PHP 8.0 or higher is required. You have PHP $PHP_VERSION"
    exit 1
fi

# Check if cURL extension is available
if ! php -m | grep -q curl; then
    print_error "PHP cURL extension is not installed. Please install it:"
    echo "  Ubuntu/Debian: sudo apt-get install php-curl"
    echo "  macOS: brew install php-curl"
    exit 1
fi
print_success "PHP cURL extension found"

# Ask for installation method
echo ""
echo "Select installation method:"
echo "1) Manual copy to project directory"
echo "2) Composer (if available)"
echo "3) Create symlink"
read -p "Enter choice [1-3]: " choice

case $choice in
    1)
        read -p "Enter target directory path: " target_dir
        if [ ! -d "$target_dir" ]; then
            print_error "Directory does not exist: $target_dir"
            exit 1
        fi
        
        cp LicenseClient.php "$target_dir/"
        print_success "LicenseClient.php copied to $target_dir/"
        
        # Create storage directory
        storage_dir="$target_dir/storage"
        mkdir -p "$storage_dir"
        chmod 700 "$storage_dir" 2>/dev/null || true
        print_success "Storage directory created at $storage_dir"
        
        # Create .gitignore if it doesn't exist
        gitignore_file="$target_dir/.gitignore"
        if [ ! -f "$gitignore_file" ]; then
            touch "$gitignore_file"
        fi
        
        if ! grep -q ".license_token" "$gitignore_file"; then
            echo "" >> "$gitignore_file"
            echo "# License token" >> "$gitignore_file"
            echo ".license_token" >> "$gitignore_file"
            echo "storage/.license_token" >> "$gitignore_file"
            print_success "Added .license_token to .gitignore"
        fi
        
        echo ""
        print_success "Installation complete!"
        echo ""
        echo "Usage:"
        echo "  require_once '$target_dir/LicenseClient.php';"
        ;;
        
    2)
        if ! command -v composer &> /dev/null; then
            print_error "Composer is not installed. Please install Composer first."
            exit 1
        fi
        
        echo ""
        print_info "You need to provide the path to your CLIENT PROJECT directory"
        print_info "This is where you want to install the SDK (where composer.json exists)"
        echo ""
        echo "Examples:"
        echo "  - /var/www/my-project"
        echo "  - /Users/username/projects/my-app"
        echo "  - ./../my-client-project"
        echo "  - /home/user/laravel-app"
        echo ""
        read -p "Enter your CLIENT PROJECT directory path: " project_dir
        
        # Expand ~ and resolve relative paths
        project_dir="${project_dir/#\~/$HOME}"
        project_dir=$(cd "$(dirname "$project_dir")" 2>/dev/null && pwd)/$(basename "$project_dir") 2>/dev/null || echo "$project_dir"
        
        if [ ! -d "$project_dir" ]; then
            print_error "Directory does not exist: $project_dir"
            exit 1
        fi
        
        if [ ! -f "$project_dir/composer.json" ]; then
            print_error "composer.json not found in $project_dir"
            echo ""
            print_info "Make sure you're pointing to the root of your project where composer.json exists"
            exit 1
        fi
        
        print_success "Found composer.json in $project_dir"
        
        # Get SDK directory path
        sdk_dir="$(cd "$(dirname "$0")" && pwd)"
        
        # Add repository to composer.json
        cd "$project_dir"
        
        print_info "Preparing Composer installation..."
        
        # Check if repository already exists
        if ! grep -q "license-management/sdk" composer.json; then
            print_info "Adding SDK repository to composer.json..."
            
            # Create a temporary composer.json with repository
            # We'll use composer config command if available, otherwise show instructions
            if composer config repositories.bizfunnel-license-sdk --quiet 2>/dev/null; then
                composer config repositories.bizfunnel-license-sdk path "$sdk_dir" --quiet
                print_success "Repository added to composer.json"
            else
                # Manual approach - show what to add
                print_info "Please add this to the 'repositories' section in your composer.json:"
                echo ""
                echo "  \"repositories\": {"
                echo "      \"bizfunnel-license-sdk\": {"
                echo "          \"type\": \"path\","
                echo "          \"url\": \"$sdk_dir\""
                echo "      }"
                echo "  },"
                echo ""
                read -p "Press Enter after updating composer.json (or Ctrl+C to cancel)..."
            fi
        else
            print_success "Repository already configured"
        fi
        
        echo ""
        print_info "Installing SDK via Composer..."
        composer require bizfunnel/licensing-client-php-sdk:@dev || {
            print_error "Composer installation failed."
            echo ""
            print_info "You may need to manually add the repository to composer.json:"
            echo ""
            echo "{"
            echo "    \"repositories\": {"
            echo "        \"bizfunnel-license-sdk\": {"
            echo "            \"type\": \"path\","
            echo "            \"url\": \"$sdk_dir\""
            echo "        }"
            echo "    }"
            echo "}"
            echo ""
            exit 1
        }
        
        print_success "SDK installed via Composer!"
        echo ""
        print_info "The SDK is now available in your project."
        print_info "You can use it with: require_once vendor/autoload.php;"
        ;;
        
    3)
        read -p "Enter target directory path: " target_dir
        if [ ! -d "$target_dir" ]; then
            print_error "Directory does not exist: $target_dir"
            exit 1
        fi
        
        sdk_dir="$(cd "$(dirname "$0")" && pwd)"
        ln -sf "$sdk_dir/LicenseClient.php" "$target_dir/LicenseClient.php"
        print_success "Symlink created: $target_dir/LicenseClient.php -> $sdk_dir/LicenseClient.php"
        ;;
        
    *)
        print_error "Invalid choice"
        exit 1
        ;;
esac

echo ""
print_success "Bizfunnel Licensing Client PHP SDK is ready to use!"
echo ""
echo "Next steps:"
echo "1. Read the README.md for usage examples"
echo "2. Check INSTALLATION.md for detailed instructions"
echo "3. See example.php for code examples"

