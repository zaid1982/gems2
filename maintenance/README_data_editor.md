# Interactive Data Editor

A powerful web-based database record editor with spreadsheet-like functionality for the GEMS2 system.

## 🌟 Features

- **Visual Table Editing**: Edit database records with a familiar spreadsheet interface
- **Safe Editing**: Primary keys and auto-increment columns are protected
- **Batch Operations**: Make multiple changes and save them in a single transaction
- **Pagination**: Efficiently handle large tables with adjustable page sizes
- **Search Functionality**: Quickly find records across all text columns
- **Change Tracking**: Visual indicators show modified records with detailed change summaries
- **Real-time Validation**: Data type validation and constraint checking
- **Responsive Design**: Works seamlessly on desktop and mobile devices

## 🚀 Quick Start

1. **Access the Tool**: Open `data_editor.html` in your web browser
2. **Select Table**: Choose a table from the dropdown menu
3. **Edit Data**: Click on any editable cell to start editing
4. **Save Changes**: Use the "Save All" button to commit your changes

## 🎯 Use Cases

- **Data Maintenance**: Quick corrections and updates to existing records
- **Bulk Updates**: Modify multiple records efficiently
- **Data Entry**: Add new records with guided input
- **Quality Control**: Review and fix data quality issues
- **Non-technical Access**: Provide database access to users without SQL knowledge

## 🛡️ Safety Features

- **Protected Columns**: Primary keys and auto-increment fields cannot be edited
- **Transaction Safety**: All changes are applied atomically
- **Data Validation**: Input is validated against column constraints
- **Change Preview**: Review all modifications before saving
- **Rollback Protection**: Failed operations automatically rollback

## 📖 Interface Guide

### Main Controls
- **Table Selector**: Choose which table to work with
- **Records Per Page**: Adjust pagination (10, 25, 50, 100)
- **Search Box**: Filter records by content
- **Save All**: Commit pending changes
- **Reload**: Refresh data from database

### Cell Editing
- **Click to Edit**: Click any editable cell to start editing
- **Visual Feedback**: Yellow (editing), green (changed), red (error)
- **Keyboard Shortcuts**: Enter (save), Escape (cancel)
- **Data Types**: Automatic validation for different column types

### Row Actions
- **Edit Row**: Enter edit mode for entire row
- **Delete Row**: Remove record with confirmation

## 🔧 Technical Details

### Files
- `data_editor.html` - Frontend interface
- `data_editor.php` - Backend API
- `help_data_editor.html` - Comprehensive documentation

### API Endpoints
- `list_tables` - Get all available tables
- `table_schema` - Get table structure and constraints
- `table_data` - Get paginated table records
- `save_changes` - Save multiple record updates
- `add_record` - Insert new records
- `delete_record` - Remove records

### Database Requirements
- MySQL/MariaDB with information_schema access
- PDO PHP extension
- Proper user permissions for CRUD operations

## 📊 Performance

- **Pagination**: Handles tables with millions of records efficiently
- **Search Optimization**: Indexed text searches across columns
- **Memory Management**: Streams large result sets
- **Transaction Batching**: Bulk operations for better performance

## ⚠️ Important Notes

1. **Backup First**: Always backup important data before bulk operations
2. **Test Environment**: Use development databases for testing
3. **Foreign Keys**: Ensure referenced records exist when editing foreign keys
4. **Large Tables**: Use search filters for tables with many records
5. **Concurrent Access**: Be aware of other users editing the same data

## 🔍 Troubleshooting

### Common Issues

**"Cannot edit this cell"**
- Cell contains primary key or auto-increment value
- Column has edit restrictions

**"Changes failed to save"**
- Check data type requirements
- Verify foreign key constraints
- Ensure required fields are not empty

**"Table not loading"**
- Verify database connection
- Check table permissions
- Try filtering large tables with search

### Recovery Options
- **Reload Table**: Discards unsaved changes and refreshes data
- **Discard Changes**: Removes all pending modifications
- **Browser Refresh**: Resets entire interface

## 📈 Usage Statistics

- **Tables Supported**: All tables with primary keys
- **Concurrent Users**: Multiple users can edit different tables
- **Record Limits**: No theoretical limit (pagination handles large datasets)
- **Column Types**: All MySQL/MariaDB data types supported

## 🔗 Integration

The data editor integrates seamlessly with the GEMS2 dashboard and uses the existing:
- Database connection configuration
- Authentication system (when implemented)
- Audit logging capabilities
- Error handling and logging

## 📝 Best Practices

1. **Small Batches**: Edit related records in manageable groups
2. **Validate First**: Use the preview feature before saving
3. **Regular Saves**: Don't accumulate too many changes
4. **Search Filter**: Use search to isolate records for editing
5. **Schema Review**: Check table structure before major edits

## 🆕 Recent Updates

- **v1.0**: Initial release with core editing functionality
- Enhanced security with SQL injection protection
- Improved error handling and user feedback
- Responsive design for mobile compatibility
- Comprehensive help documentation

## 🤝 Support

For issues or questions:
1. Check the built-in help system
2. Review error messages in browser console
3. Verify database permissions and connectivity
4. Contact system administrator for database-level issues

---

**GEMS2 Interactive Data Editor** - Making database management accessible to everyone.
