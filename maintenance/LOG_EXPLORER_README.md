# 📁 Log Explorer - User Guide

## Overview
The Log Explorer is a comprehensive web-based tool for browsing, monitoring, and analyzing log files in your GEMS2 system. It provides an intuitive interface for managing log files with real-time monitoring capabilities.

## Features

### 🗂️ Directory Navigation
- **Browse Log Directories**: Navigate through different log categories (debug, error, etc.)
- **File Listing**: View all log files with size, modification date, and quick actions
- **Breadcrumb Navigation**: Easy navigation with breadcrumb trail

### 👁️ File Viewing
- **Full File View**: Display complete log file content with line numbers
- **Tail Mode**: Show last 50 lines for quick review
- **Syntax Highlighting**: Enhanced readability with proper formatting
- **Line Numbers**: Easy reference and navigation

### 🔴 Live Monitoring
- **Real-time Updates**: Auto-refresh every 3 seconds in live mode
- **Live Indicator**: Visual indication when live monitoring is active
- **Automatic Scrolling**: New content automatically scrolled into view
- **Start/Stop Controls**: Easy toggle for live monitoring

### 🔍 Search Functionality
- **Full-text Search**: Search within individual log files
- **Highlight Results**: Search terms highlighted in results
- **Line Numbers**: Search results show exact line numbers
- **Case-insensitive**: Default case-insensitive search

### ⬇️ Download Options
- **Individual Files**: Download any log file for offline analysis
- **Direct Access**: Secure file download with proper headers
- **Large File Support**: Handle large log files efficiently

## Getting Started

### 1. Access the Tool
- Open the Log Explorer from the maintenance dashboard
- Or directly navigate to `log_explorer.html`

### 2. Browse Directories
- Click on any directory card to view its contents
- Common directories include:
  - **debug**: Debug logs and application traces
  - **error**: Error logs and exception details

### 3. View Log Files
- Click the "👁️ View" button next to any file
- Use "⬇️ Download" to save files locally

### 4. Monitor in Real-time
- Click "▶️ Start Live" to begin real-time monitoring
- The tool will automatically refresh every 3 seconds
- Click "⏸️ Stop Live" to stop monitoring

### 5. Search Within Files
- Use the search box when viewing a file
- Press Enter or click the search icon
- Results will highlight matching terms

## Interface Elements

### Navigation
- **🏠 Home**: Return to directory listing
- **🔄 Refresh**: Refresh current view
- **Breadcrumbs**: Navigate between levels

### File Actions
- **👁️ View**: Open file in viewer
- **⬇️ Download**: Download file to local system

### Viewer Controls
- **📄 View Full**: Display complete file content
- **📊 Tail**: Show last 50 lines only
- **▶️ Start Live**: Begin real-time monitoring
- **⬇️ Download**: Download current file
- **❌ Close**: Close file viewer

### Live Mode Features
- **Live Indicator**: Green dot shows active monitoring
- **Auto-refresh**: Content updates every 3 seconds
- **Automatic Scrolling**: New content scrolled into view
- **Easy Toggle**: Start/stop with single click

## Technical Details

### Configuration
- Log directory is read from `config.ini`
- Currently configured: `/Applications/XAMPP/logs/gems`
- Supports multiple subdirectories

### Security
- Path validation prevents directory traversal
- File access limited to configured log directory
- Secure download headers

### Performance
- Efficient file handling for large logs
- Pagination for better performance
- Optimized refresh intervals

### File Support
- Any text-based log file
- Special highlighting for `.log` files
- Size information and timestamps

## Troubleshooting

### Common Issues

1. **Directory Not Found**
   - Check if log directory exists
   - Verify path in `config.ini`
   - Ensure proper permissions

2. **File Access Denied**
   - Check file permissions
   - Verify web server access rights
   - Ensure files are readable

3. **Live Mode Not Working**
   - Check network connectivity
   - Verify file is still being written
   - Refresh the page and try again

4. **Search Not Finding Results**
   - Verify search term spelling
   - Check if file contains expected content
   - Try case-sensitive search if needed

### Tips for Best Performance

1. **Large Files**: Download large files for detailed analysis
2. **Live Monitoring**: Use for actively written logs only
3. **Search**: Use specific terms for faster results
4. **Navigation**: Use breadcrumbs for quick navigation

## API Endpoints

The Log Explorer uses the following API endpoints:

- `list_directories`: Get available log directories
- `list_files`: Get files in specific directory
- `view_file`: Get file content with pagination
- `tail_file`: Get last N lines of file
- `search_logs`: Search within file content
- `download_file`: Download file securely

## Integration

The Log Explorer integrates seamlessly with:
- **Dashboard**: Accessible from main maintenance dashboard
- **Config System**: Reads log directory from configuration
- **Security**: Follows same security practices as other tools

## Future Enhancements

Potential future features:
- Log rotation management
- Multi-file search
- Export filtered results
- Scheduled log cleanup
- Advanced filtering options
- Log archiving tools

---

For additional support or feature requests, please refer to the main GEMS2 maintenance documentation.
