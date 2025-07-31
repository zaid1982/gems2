#!/bin/bash

# GEMS Production Deployment Script
# This script helps deploy GEMS to production while excluding development files

# Configuration
SOURCE_DIR="/Applications/XAMPP/xamppfiles/htdocs/gems2"
PRODUCTION_SERVER="your-production-server.com"
PRODUCTION_PATH="/var/www/html/gems2"
PRODUCTION_USER="your-username"

# Files and folders to exclude from production deployment
EXCLUDE_LIST=(
    "developer/"           # All developer tools and testing files
    ".git/"               # Git repository files
    "*.log"               # Log files
    ".DS_Store"           # macOS system files
    "Thumbs.db"           # Windows thumbnail files
    "*.tmp"               # Temporary files
    "*.bak"               # Backup files
)

echo "🚀 GEMS Production Deployment Script"
echo "=================================="
echo "Source: $SOURCE_DIR"
echo "Target: $PRODUCTION_USER@$PRODUCTION_SERVER:$PRODUCTION_PATH"
echo ""

# Build exclude parameters
EXCLUDE_PARAMS=""
for item in "${EXCLUDE_LIST[@]}"; do
    EXCLUDE_PARAMS+="--exclude='$item' "
done

echo "📋 Files/Folders to exclude:"
for item in "${EXCLUDE_LIST[@]}"; do
    echo "  - $item"
done
echo ""

# Dry run first (preview what will be copied)
echo "🔍 Dry run preview (what will be copied):"
echo "rsync -avz --dry-run $EXCLUDE_PARAMS $SOURCE_DIR/ $PRODUCTION_USER@$PRODUCTION_SERVER:$PRODUCTION_PATH/"
echo ""
read -p "❓ Continue with dry run? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    eval "rsync -avz --dry-run $EXCLUDE_PARAMS $SOURCE_DIR/ $PRODUCTION_USER@$PRODUCTION_SERVER:$PRODUCTION_PATH/"
    echo ""
    read -p "🚀 Proceed with actual deployment? (y/n): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "🚀 Starting production deployment..."
        eval "rsync -avz $EXCLUDE_PARAMS $SOURCE_DIR/ $PRODUCTION_USER@$PRODUCTION_SERVER:$PRODUCTION_PATH/"
        echo "✅ Deployment completed!"
        echo ""
        echo "📝 Post-deployment checklist:"
        echo "  1. Verify database connection settings"
        echo "  2. Check file permissions (especially for uploads)"
        echo "  3. Test critical functionality"
        echo "  4. Verify no developer files are present"
    else
        echo "❌ Deployment cancelled."
    fi
else
    echo "❌ Dry run cancelled."
fi

echo ""
echo "📖 Manual deployment command:"
echo "rsync -avz $EXCLUDE_PARAMS $SOURCE_DIR/ $PRODUCTION_USER@$PRODUCTION_SERVER:$PRODUCTION_PATH/"
