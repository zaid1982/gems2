# 🗂️ Log Configuration for Windows Server Deployment

## Overview
This document explains how to configure log file paths for Windows server deployment in the GEMS2 system.

## Configuration Changes Made

### 1. Updated config.ini
**File**: `api/library/config.ini`

**Before** (Development/Local):
```ini
log_dir = /Applications/XAMPP/logs/gems
```

**After** (Windows Server/Relative Path):
```ini
log_dir = ./logs
```

### 2. Directory Structure Created
```
gems2/
├── maintenance/
│   ├── logs/                    ← New log directory within project
│   │   ├── debug/               ← Debug logs
│   │   │   └── debug_20250807.log
│   │   └── error/               ← Error logs
│   │       └── error_20250807.log
│   ├── log_explorer.html        ← Log viewer interface
│   ├── log_explorer.php         ← Log API backend
│   └── dashboard.html           ← Updated dashboard
└── api/
    └── library/
        └── config.ini           ← Updated configuration
```

## Windows Server Configuration Options

### Option 1: Relative Path (Recommended)
```ini
log_dir = ./logs
```
- **Pros**: Project-portable, works with any deployment path
- **Cons**: Logs are within web-accessible directory
- **Best for**: Development, staging, or when logs need web access

### Option 2: Windows Absolute Path
```ini
log_dir = C:\inetpub\logs\gems
```
- **Pros**: Logs outside web directory, more secure
- **Cons**: Fixed path, requires manual directory creation
- **Best for**: Production Windows servers

### Option 3: Relative to Drive Root
```ini
log_dir = ../../../logs/gems
```
- **Pros**: Outside web directory
- **Cons**: Path-dependent, may break with different deployments
- **Best for**: Specific server configurations

## Enhanced Path Resolution

The `log_explorer.php` now includes intelligent path resolution:

```php
function getLogDirectory() {
    // Handles various path formats:
    // ./logs                    (relative to maintenance folder)
    // ../../logs               (relative paths)
    // C:\inetpub\logs\gems     (Windows absolute)
    // /var/log/gems            (Linux absolute)
}
```

## Deployment Instructions

### For Windows Server (IIS/Apache)

1. **Upload Project**: Deploy entire gems2 folder to your web directory
2. **Set Permissions**: Ensure web server can write to `maintenance/logs/`
3. **Configure Path**: Update `config.ini` with appropriate log_dir
4. **Create Directories**: Ensure log directories exist and are writable

```powershell
# PowerShell commands for Windows setup
mkdir C:\inetpub\wwwroot\gems2\maintenance\logs\debug
mkdir C:\inetpub\wwwroot\gems2\maintenance\logs\error
icacls "C:\inetpub\wwwroot\gems2\maintenance\logs" /grant "IIS_IUSRS:(OI)(CI)F"
```

### For Linux/Unix Servers

```bash
# Bash commands for Linux setup
mkdir -p /var/www/html/gems2/maintenance/logs/{debug,error}
chown -R www-data:www-data /var/www/html/gems2/maintenance/logs
chmod -R 755 /var/www/html/gems2/maintenance/logs
```

## Security Considerations

### ✅ Recommended Setup (Production)
```ini
log_dir = C:\logs\gems           # Windows
log_dir = /var/log/gems          # Linux
```
- Logs outside web directory
- Better security
- Standard log location

### ⚠️ Development Setup
```ini
log_dir = ./logs
```
- Logs accessible via web
- Convenient for development
- Should not be used in production

## Log File Structure

The system expects this directory structure:
```
{log_dir}/
├── debug/
│   ├── debug_YYYYMMDD.log
│   └── debug_YYYYMMDD.log
└── error/
    ├── error_YYYYMMDD.log
    └── error_YYYYMMDD.log
```

## Testing Configuration

Test your configuration with these commands:

```bash
# Test API endpoint
curl "http://yourserver/gems2/maintenance/log_explorer.php?action=list_directories"

# Should return:
{
    "success": true,
    "directories": [
        {"name": "debug", ...},
        {"name": "error", ...}
    ],
    "log_dir": "your_configured_path"
}
```

## Environment-Specific Configurations

### Development (Local XAMPP/WAMP)
```ini
log_dir = ./logs
environment = development
```

### Staging Server
```ini
log_dir = ../logs/gems
environment = staging
```

### Production Server
```ini
log_dir = C:\ProgramData\gems\logs    # Windows
log_dir = /var/log/gems               # Linux
environment = production
```

## Troubleshooting

### Common Issues

1. **Directory Not Found**
   ```
   Error: Log directory not found: ./logs
   ```
   **Solution**: Create the directory or update the path in config.ini

2. **Permission Denied**
   ```
   Error: Permission denied accessing log directory
   ```
   **Solution**: Set proper permissions for web server user

3. **Path Resolution Failed**
   ```
   Error: Unable to resolve relative path
   ```
   **Solution**: Use absolute path or check directory structure

### Debug Steps

1. Check if directory exists:
   ```php
   var_dump(file_exists($logDir));
   ```

2. Check permissions:
   ```php
   var_dump(is_readable($logDir));
   var_dump(is_writable($logDir));
   ```

3. Check resolved path:
   ```php
   var_dump(realpath($logDir));
   ```

## Best Practices

1. **Use relative paths for portability**
2. **Keep logs outside web directory in production**
3. **Implement log rotation for large files**
4. **Set up proper backup for log files**
5. **Monitor disk space usage**
6. **Secure log directory permissions**

---

This configuration ensures your log explorer works seamlessly across different deployment environments while maintaining security and portability.
