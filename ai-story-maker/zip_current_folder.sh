#!/bin/bash

# Generic Folder Zipper Script
# 
# Zips the current folder (excluding this script) with timestamp
# and saves to a configurable output directory

# ===== CONFIGURATION =====
OUTPUT_DIR="/Users/ano/Documents/repos/subscription tests"
# ========================

# Get current directory name and script name
CURRENT_DIR=$(basename "$PWD")
SCRIPT_NAME=$(basename "$0")
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

echo "Starting folder zip process..."
echo "Current folder: $CURRENT_DIR"
echo "Script name: $SCRIPT_NAME"
echo "Timestamp: $TIMESTAMP"
echo "Output directory: $OUTPUT_DIR"
echo ""

# Create output directory if it doesn't exist
mkdir -p "$OUTPUT_DIR"

# Create zip filename with timestamp
ZIP_FILENAME="${CURRENT_DIR}_${TIMESTAMP}.zip"
ZIP_PATH="$OUTPUT_DIR/$ZIP_FILENAME"

echo "Creating zip: $ZIP_PATH"

# Create zip file, excluding the script itself and common unnecessary files
if zip -r "$ZIP_PATH" . \
    -x "$SCRIPT_NAME" \
    -x "*.git*" \
    -x "*.DS_Store*" \
    -x "node_modules/*" \
    -x "vendor/*" \
    -x "*.log" \
    -x "*.tmp" \
    -x "*.zip" \
    -x ".vscode/*" \
    -x ".idea/*" \
    -x "*.cache" \
    -x "*.swp" \
    -x "*.swo" \
    -x "*~" \
    -x "Thumbs.db" \
    -x ".env*" \
    -x "composer.lock" \
    -x "package-lock.json" \
    -x "yarn.lock" \
    -x ".npm/*" \
    -x ".yarn/*" \
    -x "dist/*" \
    -x "build/*" \
    -x "coverage/*" \
    -x "tests/*" \
    -x "test/*" \
    -x "__pycache__/*" \
    -x "*.pyc" \
    -x "*.pyo" \
    -x ".pytest_cache/*" \
    -x ".coverage" \
    -x "htmlcov/*" \
    -x ".tox/*" \
    -x ".venv/*" \
    -x "venv/*" \
    -x "env/*" \
    -x ".env.local" \
    -x ".env.development" \
    -x ".env.test" \
    -x ".env.production"; then
    
    # Get file size
    FILE_SIZE=$(du -h "$ZIP_PATH" | cut -f1)
    echo "✓ Successfully created: $ZIP_FILENAME ($FILE_SIZE)"
    echo "✓ Saved to: $ZIP_PATH"
    
    # Show zip contents count
    CONTENTS_COUNT=$(unzip -l "$ZIP_PATH" | tail -1 | awk '{print $2}')
    echo "✓ Contains $CONTENTS_COUNT files/directories"
    
else
    echo "❌ Error: Could not create zip file: $ZIP_PATH"
    exit 1
fi

echo ""
echo "Zip process completed!"
echo "Output directory: $OUTPUT_DIR"

# List all zip files in output directory (optional)
echo ""
echo "All zip files in output directory:"
ls -lh "$OUTPUT_DIR"/*.zip 2>/dev/null | while read -r line; do
    echo "  $line"
done

echo ""
echo "Done!" 