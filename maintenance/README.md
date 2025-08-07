# Database Maintenance Scripts

This folder contains database maintenance and schema extraction utilities for the GEMS2 system.

## �️ Available Tools

### 1. **Interactive Data Editor** ⭐ NEW
**File:** `data_editor.html` + `data_editor.php`
- **Purpose**: Edit database records with spreadsheet-like interface
- **Features**: 
  - Visual cell editing with change tracking
  - Batch updates with transaction safety
  - Pagination and search capabilities
  - Protected primary keys and auto-increment fields
  - Real-time validation and error handling
- **Perfect for**: Quick data maintenance, bulk updates, non-technical users
- **Documentation**: `help_data_editor.html`

### 2. **Database Query Tool**
**File:** `db_query_tool.html` + `db_query_tool.php`
- **Purpose**: Execute SQL queries and explore database structure
- **Features**: 
  - SQL query execution with syntax highlighting
  - Table browsing and column information
  - Export results to CSV format
  - Query history and error handling
- **Perfect for**: Advanced users, data analysis, development work

### 3. **Schema Extraction Tools**
**Files:** `extract_table_schemas*.php`
- **Purpose**: Extract database schema as CREATE TABLE statements
- **Variants**: Simple, advanced, and comprehensive extraction
- **Features**: 
  - Complete table structure extraction
  - Foreign key relationships
  - Triggers and stored procedures (advanced version)
  - Statistics and analysis
- **Perfect for**: Database documentation, migrations, backups

### 4. **Database Management Dashboard**
**File:** `dashboard.html`
- **Purpose**: Central hub for all database tools
- **Features**: 
  - Quick access to all tools
  - Database connection status
  - Real-time statistics and metrics
  - Tool descriptions and help links
- **Perfect for**: Overview, navigation, system monitoring

### 5. **SQL Deployment Scripts**

### `db_query_tool.html` + `db_query_tool.php`
**Web-based database management interface**
- Interactive SQL query execution
- Complete table and column browser
- Real-time database statistics
- Export results to CSV
- Modern responsive interface
- Read-only security by default

**Access:** `http://localhost/gems2/maintenance/db_query_tool.html`

**Features:**
- 📊 Database overview with size and table count
- 📋 Interactive table browser with metadata
- 🔍 Detailed column inspector with data types
- ⚡ SQL query editor with syntax support
- 📥 CSV export functionality
- 🛡️ Security validation (read-only by default)

**Perfect for:**
- Executing ad-hoc queries without database client
- Exploring database schema and relationships
- Quick data analysis and reporting
- Checking table structures and constraints

## Schema Extraction Scripts

### 1. `extract_table_schemas_advanced.php` ⭐ **Recommended**
**Most comprehensive and feature-rich**
- Extracts complete database schema with detailed analysis
- Generates both SQL schema file and analysis report
- Includes table statistics, relationships, and performance insights
- Can switch between local and production databases
- Output files:
  - `database_schema_advanced_YYYY-MM-DD_HH-mm-ss.sql` - Complete schema
  - `database_analysis_YYYY-MM-DD_HH-mm-ss.txt` - Analysis report

**Usage:**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gems2/maintenance
php extract_table_schemas_advanced.php
```

### 2. `extract_table_schemas.php`
**Comprehensive database structure extractor**
- Extracts tables, views, triggers, stored procedures, and functions
- Most detailed CREATE TABLE statements with all metadata
- Includes foreign keys, indexes, and constraints
- Complete database recreation capability

**Usage:**
```bash
php extract_table_schemas.php
```

### 3. `extract_table_schemas_simple.php`
**Lightweight and fast**
- Basic table structure extraction only
- Quick overview of database schema
- Smaller output files

**Usage:**
```bash
php extract_table_schemas_simple.php
```

## Configuration

All scripts can work with both:
- **Production Database**: `www.metadatasystem.my` (default)
- **Local Database**: `localhost` (set `$useLocalDB = true`)

Database credentials are automatically loaded from `../api/class/Constant.php`.

## Generated Files

The scripts generate timestamped files:
- **SQL Files**: Complete CREATE TABLE statements that can recreate the database
- **Analysis Files**: Detailed reports with table statistics and relationships

## Database Information (Last Run: 2025-08-01)

- **Database**: gems
- **Host**: www.metadatasystem.my
- **Total Tables**: 118
- **Database Size**: 4,710.86 MB
- **Largest Tables**:
  - `ppm_task_qual`: 5.1M rows, 1,004 MB
  - `sys_upload`: 2.3M rows, 758 MB
  - `ppm_task`: 793K rows, 504 MB
  - `wfl_task`: 1.9M rows, 499 MB

## Example Output Files

- `database_schema_advanced_2025-08-01_15-53-39.sql` (144.65 KB)
- `database_analysis_2025-08-01_15-53-39.txt` (36.58 KB)

## Notes

- Scripts require PHP with PDO MySQL extension
- Production database access requires proper network connectivity
- Generated SQL files are compatible with MySQL/MariaDB
- Files are automatically timestamped to prevent overwrites

## Security

⚠️ **Important**: These scripts contain database credentials. Keep this folder secure and do not expose it via web access.
