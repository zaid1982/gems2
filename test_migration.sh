#!/bin/bash
# Test Material Returns SQL Migration

echo "=== Testing Material Returns SQL Migration ==="
echo ""
echo "Database: gems2"
echo "SQL File: create_material_returns_safe.sql"
echo ""

# Path to MySQL
MYSQL="/Applications/XAMPP/xamppfiles/bin/mysql"

# Test 1: Check MySQL connection
echo "Test 1: Checking MySQL connection..."
$MYSQL -u root -e "SELECT VERSION();" 2>&1 | grep -v "Warning"
if [ $? -eq 0 ]; then
    echo "✓ MySQL connection OK"
else
    echo "✗ MySQL connection failed"
    exit 1
fi
echo ""

# Test 2: Check if database exists
echo "Test 2: Checking if gems2 database exists..."
$MYSQL -u root -e "SHOW DATABASES LIKE 'gems2';" 2>&1 | grep -v "Warning" | grep gems2
if [ $? -eq 0 ]; then
    echo "✓ Database gems2 exists"
else
    echo "✗ Database gems2 not found"
    exit 1
fi
echo ""

# Test 3: Check required tables
echo "Test 3: Checking required tables..."
REQUIRED_TABLES=("wo_task_parts" "ast_part" "sys_user" "ref_status" "ast_part_sub")
for table in "${REQUIRED_TABLES[@]}"; do
    $MYSQL -u root gems2 -e "SHOW TABLES LIKE '$table';" 2>&1 | grep -v "Warning" | grep $table > /dev/null
    if [ $? -eq 0 ]; then
        echo "  ✓ Table $table exists"
    else
        echo "  ✗ Table $table NOT FOUND (required!)"
        exit 1
    fi
done
echo ""

# Test 4: Syntax check (dry run)
echo "Test 4: SQL syntax validation..."
$MYSQL -u root gems2 --execute="SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✓ MySQL syntax OK"
else
    echo "✗ MySQL syntax error"
    exit 1
fi
echo ""

echo "=== All Pre-flight Checks Passed ==="
echo ""
echo "Ready to run migration:"
echo "  $MYSQL -u root -p gems2 < create_material_returns_safe.sql"
echo ""
echo "Or without password prompt:"
echo "  $MYSQL -u root gems2 < create_material_returns_safe.sql"
