#!/bin/bash

# Install Wasmer CLI if not installed
if ! command -v wasmer &> /dev/null; then
    curl https://get.wasmer.io -sSfL | sh
    source ~/.wasmer/wasmer.sh
fi

# Login to Wasmer (you'll need to do this manually first)
echo "Please run: wasmer login"
echo "Then run this script again"

# Build and deploy
wasmer deploy

echo "Deployment completed!"
echo "Your app should be available at: https://your-app-name.wasmer.app"