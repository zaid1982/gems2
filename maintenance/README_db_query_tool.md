# 🗄️ GEMS2 Database Query Tool

A comprehensive web-based database management and query execution tool for the GEMS2 system.

## 🌟 Features

### 📊 Database Overview
- **Real-time database statistics** (size, table count, version)
- **Visual database information display**
- **Connection status monitoring**

### 📋 Table Browser
- **Complete table listing** with sizes and row counts
- **Sortable by size** (largest tables first)
- **Interactive table selection**
- **Table metadata display**

### 🔍 Column Inspector
- **Detailed column information** for any selected table
- **Data types and constraints**
- **Primary keys, unique keys, and indexes**
- **Nullable and default value indicators**
- **Quick query generators** for each table

### ⚡ SQL Query Executor
- **Syntax-highlighted query editor**
- **Multi-line query support**
- **Query formatting capabilities**
- **Execution time tracking**
- **Result set pagination**

### 🛡️ Security Features
- **Read-only by default** (SELECT, SHOW, DESCRIBE only)
- **Query validation and sanitization**
- **SQL injection protection**
- **Safe query execution environment**

### 📥 Export Capabilities
- **CSV export** of query results
- **Formatted data download**
- **Timestamped file naming**

### 🎨 User Interface
- **Modern, responsive design**
- **Dark/light theme compatible**
- **Mobile-friendly layout**
- **Real-time loading indicators**

## 🚀 Quick Start

### 1. Access the Tool
```
http://localhost/gems2/maintenance/db_query_tool.html
```

### 2. Browse Database
- View database statistics in the left sidebar
- Click on any table to explore its structure
- Use quick query buttons for common operations

### 3. Execute Queries
- Enter SQL queries in the editor
- Use quick query templates
- Click "Execute Query" to run
- Export results as needed

## 📖 Usage Examples

### Basic Queries
```sql
-- View all users
SELECT * FROM sys_user LIMIT 10;

-- Count work orders
SELECT COUNT(*) FROM wo_task;

-- Check table structure
DESCRIBE ast_asset;

-- Show database tables
SHOW TABLES;
```

### Advanced Queries
```sql
-- Work order statistics by status
SELECT 
    wo_task_status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM wo_task), 2) as percentage
FROM wo_task 
GROUP BY wo_task_status 
ORDER BY count DESC;

-- Asset summary by type
SELECT 
    at.asset_type_desc,
    COUNT(a.asset_id) as total_assets,
    AVG(a.asset_cost) as avg_cost
FROM ast_asset a
JOIN ast_asset_type at ON a.asset_type_id = at.asset_type_id
GROUP BY at.asset_type_desc
ORDER BY total_assets DESC;

-- PPM task completion rates
SELECT 
    DATE_FORMAT(ppm_task_time_created, '%Y-%m') as month,
    COUNT(*) as total_tasks,
    SUM(CASE WHEN ppm_task_status = 16 THEN 1 ELSE 0 END) as completed_tasks,
    ROUND(SUM(CASE WHEN ppm_task_status = 16 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as completion_rate
FROM ppm_task
WHERE ppm_task_time_created >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(ppm_task_time_created, '%Y-%m')
ORDER BY month DESC;
```

### System Information Queries
```sql
-- Database size by table
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
    table_rows
FROM information_schema.TABLES 
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC
LIMIT 10;

-- Current database connections
SHOW PROCESSLIST;

-- MySQL version and status
SELECT 
    @@version as mysql_version,
    @@version_comment as version_comment,
    DATABASE() as current_database,
    USER() as current_user,
    NOW() as current_time;
```

## 🎯 Quick Query Templates

The tool provides pre-built query templates:

| Template | Description | Query |
|----------|-------------|--------|
| **Show Tables** | List all database tables | `SHOW TABLES;` |
| **Show Processes** | View active connections | `SHOW PROCESSLIST;` |
| **Current Database** | Display current database | `SELECT DATABASE();` |
| **Current Time** | Show server time | `SELECT NOW();` |
| **Table Status** | Table statistics | `SHOW TABLE STATUS;` |
| **MySQL Version** | Database version info | `SELECT VERSION();` |

## 🔧 Technical Details

### Frontend (HTML/CSS/JavaScript)
- **Pure JavaScript** (no external dependencies)
- **Responsive CSS Grid layout**
- **AJAX-based communication**
- **Real-time data updates**
- **Modern ES6+ features**

### Backend (PHP)
- **PDO database connections**
- **JSON API responses**
- **Security validation**
- **Error handling**
- **Query logging (optional)**

### Database Compatibility
- **MySQL 5.7+**
- **MariaDB 10.2+**
- **GEMS2 database schema**

## 🛡️ Security Considerations

### Query Restrictions
- **READ-ONLY operations** by default
- **No DROP, DELETE, TRUNCATE** commands
- **No ALTER or CREATE** statements
- **Input validation** and sanitization

### Access Control
- **Local network access** recommended
- **Production environment** - restrict access
- **Database credentials** - secure storage

### Safety Features
- **Query validation** before execution
- **Error message** filtering
- **Connection timeout** protection
- **Resource usage** monitoring

## 📁 File Structure

```
maintenance/
├── db_query_tool.html          # Main web interface
├── db_query_tool.php           # Backend API handler
├── README_db_query_tool.md     # This documentation
└── logs/                       # Query execution logs (auto-created)
    └── query_tool_YYYY-MM-DD.log
```

## 🔧 Configuration

### Database Connection
The tool uses the existing GEMS2 database configuration:
```php
// Configuration loaded from
require_once('../api/class/Constant.php');

// Connection details
$host = Constant::$dbHost;         // www.metadatasystem.my
$username = Constant::$dbUserName; // gems
$password = Constant::$dbUserPassword; // Metadata@2025
$database = Constant::$dbName;     // gems
```

### Security Settings
To enable write operations (NOT RECOMMENDED for production):
```php
// In db_query_tool.php, modify this function:
function isSelectQuery($query) {
    // Add additional allowed operations
    $allowedStarts = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'INSERT', 'UPDATE'];
    // ... rest of function
}
```

## 🐛 Troubleshooting

### Common Issues

**1. "Database connection failed"**
- Check database credentials in `Constant.php`
- Verify network connectivity to database server
- Ensure MySQL/MariaDB service is running

**2. "Query error: Access denied"**
- Verify user permissions in database
- Check if user has SELECT privileges
- Confirm database name is correct

**3. "No results found"**
- Query executed successfully but returned no data
- Check your WHERE conditions
- Verify table name spelling

**4. "For security reasons, only SELECT queries allowed"**
- Tool is configured for read-only access
- Use appropriate SELECT/SHOW/DESCRIBE queries
- Contact administrator for write access

### Debug Mode
To enable detailed error reporting, add to the top of `db_query_tool.php`:
```php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📊 Performance Tips

### Query Optimization
- **Use LIMIT** for large result sets
- **Add indexes** to frequently queried columns
- **Avoid SELECT *** on large tables
- **Use WHERE clauses** to filter data

### Large Database Handling
- **Paginate results** for tables with millions of rows
- **Use COUNT(*)** before fetching all data
- **Monitor execution time** for slow queries
- **Consider read replicas** for heavy usage

## 🔄 Future Enhancements

### Planned Features
- [ ] **Query history** and favorites
- [ ] **Visual query builder**
- [ ] **Data export** in multiple formats (JSON, XML)
- [ ] **User authentication** and role-based access
- [ ] **Query scheduling** and automation
- [ ] **Real-time monitoring** dashboard
- [ ] **Database backup** integration
- [ ] **Multi-database** support

### Advanced Features
- [ ] **Stored procedure** execution
- [ ] **View creation** and management
- [ ] **Index optimization** suggestions
- [ ] **Query performance** analysis
- [ ] **Data visualization** charts
- [ ] **Collaborative** query sharing

## 📞 Support

For issues, questions, or feature requests:
1. Check this documentation first
2. Review error messages in browser console
3. Check database connection and permissions
4. Contact system administrator

## 📄 License

This tool is part of the GEMS2 system and follows the same licensing terms.

---

**⚠️ Important**: This tool provides direct database access. Use responsibly and ensure proper security measures in production environments.
