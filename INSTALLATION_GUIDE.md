# Installation Guide - Bizfunnel Licensing Client PHP SDK

## What Path to Enter?

When the installation script asks for a path, here's what you need to know:

## For Composer Installation (Option 2)

When asked: **"Enter your CLIENT PROJECT directory path"**

### What to Enter:

Enter the **full path** to your **client project** (the project where you want to use the SDK), not the SDK directory itself.

### Examples:

**If your project is at:**
```
/Users/john/projects/my-application
```

**You would enter:**
```
/Users/john/projects/my-application
```

**Or using relative path from SDK directory:**
```
../my-application
```

**Or using `~` for home directory:**
```
~/projects/my-application
```

### How to Find Your Project Path:

**On macOS/Linux:**
```bash
# Navigate to your project
cd /path/to/your/project

# Get the full path
pwd
# Copy this path and paste it when asked
```

**On Windows (Git Bash):**
```bash
cd /c/Users/YourName/projects/my-project
pwd
```

**Or just drag and drop:**
- In most terminals, you can drag the folder from Finder/File Explorer into the terminal to get the path

### What the Script Checks:

The script will verify:
1. ✅ The directory exists
2. ✅ It contains a `composer.json` file
3. ✅ It's a valid Composer project

### Example Session:

```
Select installation method:
1) Manual copy to project directory
2) Composer (if available)
3) Create symlink
Enter choice [1-3]: 2

ℹ You need to provide the path to your CLIENT PROJECT directory
ℹ This is where you want to install the SDK (where composer.json exists)

Examples:
  - /var/www/my-project
  - /Users/username/projects/my-app
  - ./../my-client-project
  - /home/user/laravel-app

Enter your CLIENT PROJECT directory path: /Users/john/projects/my-app
✓ Found composer.json in /Users/john/projects/my-app
```

## For Manual Installation (Option 1)

When asked: **"Enter target directory path"**

Enter where you want to copy the `LicenseClient.php` file:

**Examples:**
```
/Users/john/projects/my-app/lib
/var/www/my-project/includes
./lib
../my-project/vendor
```

## Quick Reference

| Question | What to Enter | Example |
|----------|---------------|---------|
| Client project directory | Path to your project root (where composer.json is) | `/Users/john/my-app` |
| Target directory | Where to copy LicenseClient.php | `/Users/john/my-app/lib` |

## Troubleshooting

**Error: "composer.json not found"**
- Make sure you're pointing to the **root** of your project
- The directory should contain `composer.json` file
- Check if you're in the right project

**Error: "Directory does not exist"**
- Use absolute paths (starting with `/` or `~`)
- Or use relative paths from current directory
- Check for typos in the path

**Still Confused?**
- Use Option 1 (Manual copy) instead - it's simpler
- Or just copy `LicenseClient.php` manually to your project

