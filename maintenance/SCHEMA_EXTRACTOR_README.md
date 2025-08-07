# 🗄️ Database Schema Extractor - User Guide

## Overview
The Database Schema Extractor is a comprehensive tool for extracting, analyzing, and comparing database schemas. It's designed to help developers and database administrators identify differences between development and production environments, document database structures, and maintain schema consistency.

## Features

### 📤 Schema Extraction
- **Complete Schema Export**: Extract all database structure information
- **Table Details**: Columns, data types, constraints, defaults
- **Index Information**: All indexes with their configurations
- **Foreign Key Relationships**: Complete relationship mapping
- **Table Statistics**: Row counts, sizes, and metadata

### 🔄 Schema Comparison
- **Side-by-Side Comparison**: Compare two complete schemas
- **Difference Detection**: Identify missing tables, columns, indexes
- **Structural Analysis**: Detect data type changes and modifications
- **Visual Diff Report**: Color-coded difference visualization

### 💾 Export Options
- **JSON Format**: Complete schema data with all details
- **SQL Documentation**: Human-readable schema documentation
- **CSV Export**: Tabular column listing for spreadsheet analysis

## Getting Started

### 1. Extract Current Database Schema

#### Basic Extraction
1. Open the Schema Extractor tool
2. Click on "📤 Extract Schema" tab
3. Click "🚀 Extract Schema" button
4. View the comprehensive schema information

#### Quick Table Overview
1. Click "📋 Quick Table List" for a summary
2. View table names, engines, row counts, and sizes

### 2. Export Schema Data

#### Choose Export Format
- **JSON**: Preserves all schema details, best for comparisons
- **SQL**: Human-readable documentation format
- **CSV**: Column listing for spreadsheet analysis

#### Export Process
1. After extracting schema, choose your format
2. Click "⬇️ Download Schema"
3. File will be saved with timestamp

### 3. Compare Schemas

#### Prepare Schema Files
1. Extract schema from development database
2. Export as JSON format
3. Repeat for production database
4. Save both JSON files

#### Comparison Process
1. Switch to "🔄 Compare Schemas" tab
2. Upload Schema 1 (e.g., Development)
3. Upload Schema 2 (e.g., Production)
4. Click "🔍 Compare Schemas"
5. Review detailed difference report

#### Alternative: Use Current Database
- Click "📤 Use Current DB as Schema 1/2"
- Compare current database with uploaded schema file

## Schema Information Extracted

### Database Level
- Database name and character set
- MySQL version information
- Total database size
- Extraction timestamp

### Table Level
- **Structure**: Engine, row format, collation
- **Statistics**: Row count, data size, index size
- **Timestamps**: Creation and last update times
- **Comments**: Table descriptions and notes

### Column Level
- **Data Types**: Complete type information with length/precision
- **Constraints**: NULL/NOT NULL, defaults, auto-increment
- **Position**: Ordinal position in table
- **Character Sets**: Column-specific character sets
- **Comments**: Column descriptions

### Index Level
- **Index Types**: PRIMARY, UNIQUE, INDEX, FULLTEXT
- **Column Composition**: Multi-column indexes
- **Statistics**: Cardinality and optimization data
- **Configuration**: Index options and comments

### Relationship Level
- **Foreign Keys**: Complete constraint definitions
- **Referenced Tables**: Target table and column mapping
- **Cascade Rules**: UPDATE and DELETE actions
- **Constraint Names**: Named relationship tracking

## Comparison Features

### Difference Types Detected

#### Missing Tables
- **In Schema 1**: Tables present in Schema 2 but missing in Schema 1
- **In Schema 2**: Tables present in Schema 1 but missing in Schema 2

#### Column Differences
- **Missing Columns**: Columns missing in either schema
- **Data Type Changes**: Different data types or sizes
- **Constraint Changes**: NULL/NOT NULL modifications
- **Default Value Changes**: Different default values

#### Index Differences
- **Missing Indexes**: Indexes present in one schema but not the other
- **Index Configuration**: Different index types or options

#### Visual Indicators
- **🟢 Added**: Items present in Schema 2 but missing in Schema 1
- **🔴 Removed**: Items present in Schema 1 but missing in Schema 2
- **🟡 Modified**: Items with different configurations

## Use Cases

### 1. Development vs Production Comparison
```
1. Extract development database schema (JSON)
2. Extract production database schema (JSON)
3. Compare to find differences
4. Generate deployment scripts based on differences
```

### 2. Schema Documentation
```
1. Extract complete schema
2. Export as SQL format for documentation
3. Include in project documentation
4. Update regularly for version control
```

### 3. Database Migration Planning
```
1. Extract source database schema
2. Extract target database schema
3. Compare to identify migration requirements
4. Plan data migration strategy
```

### 4. Quality Assurance
```
1. Extract schema at different development stages
2. Compare schemas across environments
3. Verify deployment completeness
4. Ensure consistency across instances
```

## Export Format Details

### JSON Format
```json
{
  "database_info": {
    "database_name": "gems",
    "charset": "utf8mb4",
    "mysql_version": "8.0.32"
  },
  "tables": {
    "table_name": {
      "columns": [...],
      "indexes": [...],
      "foreign_keys": [...]
    }
  },
  "extraction_time": "2025-08-07 15:30:45"
}
```

### SQL Format
```sql
-- Database Schema Export
-- Database: gems
-- Generated: 2025-08-07 15:30:45

-- Table: users
-- Columns: 15
-- Indexes: 3
-- Foreign Keys: 2
```

### CSV Format
```csv
Table,Column,Position,Type,Nullable,Default,Key,Extra,Comment
users,id,1,int(11),NO,,PRI,auto_increment,Primary key
users,username,2,varchar(255),NO,,UNI,,User login name
```

## Best Practices

### 1. Regular Schema Extraction
- Extract schemas before major releases
- Keep historical schema files for reference
- Document schema changes in version control

### 2. Environment Consistency
- Compare development with staging regularly
- Verify production deployment completeness
- Maintain schema synchronization

### 3. Documentation
- Use SQL export for human-readable documentation
- Include schema diagrams in project docs
- Update documentation with each release

### 4. Backup and Version Control
- Store schema files in version control
- Tag schema files with release versions
- Maintain schema change logs

## Troubleshooting

### Common Issues

1. **Extraction Timeout**
   - Large databases may take time to extract
   - Use "Quick Table List" for overview first
   - Consider extracting specific tables only

2. **Permission Errors**
   - Ensure database user has SELECT privileges
   - Verify access to information_schema tables
   - Check database connection settings

3. **Comparison Failures**
   - Ensure both files are valid JSON
   - Check file size limits for uploads
   - Verify schema structure compatibility

4. **Export Issues**
   - Check browser download settings
   - Verify sufficient disk space
   - Try different export formats

### Performance Tips

1. **Large Databases**
   - Extract during off-peak hours
   - Use JSON format for complete data
   - Consider partial extractions for testing

2. **Network Considerations**
   - Large schema files may take time to upload
   - Use compression for file transfers
   - Consider splitting very large comparisons

## API Endpoints

The Schema Extractor provides these API endpoints:

- `extract_schema`: Get complete database schema
- `get_tables`: Get quick table overview
- `get_table_details`: Get specific table information
- `compare_schemas`: Compare two schema files
- `export_schema`: Download schema in various formats

## Integration

### Command Line Usage
```bash
# Extract schema
curl -X POST http://yourserver/gems2/maintenance/schema_extractor.php \
     -d "action=extract_schema" > schema.json

# Get table list
curl -X POST http://yourserver/gems2/maintenance/schema_extractor.php \
     -d "action=get_tables"
```

### Automated Workflows
- Include in CI/CD pipelines
- Schedule regular schema extractions
- Integrate with deployment scripts
- Use for automated testing

---

This tool provides comprehensive database schema management capabilities for maintaining consistency and understanding structural differences across your database environments.
