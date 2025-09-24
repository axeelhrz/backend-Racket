#!/bin/bash

# Script to start Laravel development server with optimized settings

echo "🚀 Starting Laravel development server with optimized settings..."

# Kill any existing processes on port 8001
echo "🔄 Checking for existing processes on port 8001..."
if lsof -ti:8001 > /dev/null 2>&1; then
    echo "⚠️  Found existing processes on port 8001. Terminating..."
    lsof -ti:8001 | xargs kill -9
    sleep 2
fi

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize Laravel for development
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache

# Start the server with custom PHP configuration
echo "🌟 Starting server on http://0.0.0.0:8001"
echo "📝 Using custom PHP configuration from php.ini"
echo "🛑 Press Ctrl+C to stop the server"
echo ""

# Start server with custom php.ini if it exists
if [ -f "php.ini" ]; then
    php -c php.ini artisan serve --host=0.0.0.0 --port=8001
else
    php artisan serve --host=0.0.0.0 --port=8001
fi