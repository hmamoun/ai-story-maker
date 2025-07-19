#!/bin/bash

# Quick Zip Actions Runner
# Run this script to execute all zip actions

echo "🚀 Starting Zip Actions..."
echo ""

# Function to run zip action
run_zip_action() {
    local folder_name=$1
    local folder_path=$2
    
    echo "📦 Zipping $folder_name..."
    
    if [ -d "$folder_path" ]; then
        cd "$folder_path"
        
        # Check if zip script exists
        if [ -f "zip_current_folder.sh" ]; then
            chmod +x zip_current_folder.sh
            ./zip_current_folder.sh
            echo "✅ $folder_name zipped successfully!"
        else
            echo "❌ zip_current_folder.sh not found in $folder_path"
            echo "   Please copy the script to this folder first."
        fi
        
        cd ..
    else
        echo "❌ Folder not found: $folder_path"
    fi
    
    echo ""
}

# Setup: Copy script to both folders
echo "🔧 Setting up zip scripts..."
cp zip_current_folder.sh ai-story-maker/ 2>/dev/null
cp zip_current_folder.sh "API Gateway/" 2>/dev/null
echo "✅ Scripts copied to plugin folders"
echo ""

# Run zip actions
run_zip_action "AI Story Maker" "ai-story-maker"
run_zip_action "API Gateway" "API Gateway"

echo "🎉 All zip actions completed!"
echo "📁 Check your output directory for the zip files." 