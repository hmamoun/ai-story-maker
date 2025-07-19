#!/bin/bash

# Quick Zip Commands
# Usage: ./zip.sh [command]

case "$1" in
    "setup")
        echo "🔧 Setting up zip scripts..."
        cp zip_current_folder.sh ai-story-maker/ 2>/dev/null
        cp zip_current_folder.sh "API Gateway/" 2>/dev/null
        chmod +x ai-story-maker/zip_current_folder.sh 2>/dev/null
        chmod +x "API Gateway/zip_current_folder.sh" 2>/dev/null
        echo "✅ Setup complete!"
        ;;
    "ai")
        echo "📦 Zipping AI Story Maker..."
        cd ai-story-maker && chmod +x zip_current_folder.sh && ./zip_current_folder.sh
        ;;
    "api")
        echo "📦 Zipping API Gateway..."
        cd "API Gateway" && chmod +x zip_current_folder.sh && ./zip_current_folder.sh
        ;;
    "all")
        echo "🚀 Zipping all plugins..."
        ./run_zip_actions.sh
        ;;
    *)
        echo "Usage: ./zip.sh [command]"
        echo "Commands:"
        echo "  setup  - Copy scripts to plugin folders"
        echo "  ai     - Zip AI Story Maker only"
        echo "  api    - Zip API Gateway only"
        echo "  all    - Zip both plugins"
        ;;
esac 