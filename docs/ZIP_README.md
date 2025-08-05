# Plugin Zipper Scripts

This directory contains scripts to zip plugins with timestamps and save them to a configurable output directory.

## Files

### `zip_current_folder.sh` (Generic Script)
- **Purpose:** Zips the current folder it's in (excluding itself)
- **Usage:** Copy this file to any folder you want to zip
- **Output:** Creates timestamped zip files in the configured output directory

### `zip_plugins.sh` (Specific Script)
- **Purpose:** Zips both ai-story-maker and API Gateway pluginsس
- **Usage:** Run from the root directory
- **Output:** Creates two timestamped zip files

### `cursor_zip_action.json` (Cursor Actions)
- **Purpose:** Cursor IDE actions for running zip scripts
- **Usage:** Import into Cursor for quick access

## Configuration

### Output Directory
Edit the `OUTPUT_DIR` variable in the scripts:
```bash
OUTPUT_DIR="/Users/ano/Documents/repos/subscription tests"
```

## Usage

### Method 1: Generic Script (Recommended)
1. Copy `zip_current_folder.sh` to any folder you want to zip
2. Make it executable: `chmod +x zip_current_folder.sh`
3. Run it: `./zip_current_folder.sh`

### Method 2: Specific Script
1. Make executable: `chmod +x zip_plugins.sh`
2. Run it: `./zip_plugins.sh`

### Method 3: Cursor Actions
1. Import `cursor_zip_action.json` into Cursor
2. Use the command palette to run zip actions

## Setup Commands

```bash
# Make scripts executable
chmod +x zip_current_folder.sh
chmod +x zip_plugins.sh

# Copy generic script to plugin folders
cp zip_current_folder.sh ai-story-maker/
cp zip_current_folder.sh "API Gateway/"

# Make copies executable
chmod +x ai-story-maker/zip_current_folder.sh
chmod +x "API Gateway/zip_current_folder.sh"
```

## Output Format

Zip files are named with timestamps:
```
ai-story-maker_2024-01-15_14-30-25.zip
api-gateway_2024-01-15_14-30-25.zip
```

## Excluded Files

The scripts automatically exclude:
- Git files (`.git*`)
- macOS files (`.DS_Store*`)
- Node.js dependencies (`node_modules/*`)
- Composer dependencies (`vendor/*`)
- Log files (`*.log`)
- Temporary files (`*.tmp`, `*.cache`)
- IDE files (`.vscode/*`, `.idea/*`)
- Build artifacts (`dist/*`, `build/*`)
- Test files (`tests/*`, `test/*`)
- Python cache (`__pycache__/*`, `*.pyc`)
- Environment files (`.env*`)
- Lock files (`composer.lock`, `package-lock.json`)

## Features

- ✅ Timestamped filenames
- ✅ Automatic output directory creation
- ✅ Comprehensive file exclusions
- ✅ File size reporting
- ✅ Contents count
- ✅ Error handling
- ✅ Cross-platform compatibility 