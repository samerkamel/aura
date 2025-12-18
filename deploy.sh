#!/bin/bash
#
# Manual deployment script for Aura
# Usage: ./deploy.sh
#

set -e

echo "🚀 Starting deployment..."

# Navigate to project directory
cd "$(dirname "$0")"

echo "📥 Pulling latest changes..."
git fetch origin main
git reset --hard origin/main

echo "📦 Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "🗄️ Running migrations..."
php artisan migrate --force

echo "🔧 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔄 Restarting queue workers..."
php artisan queue:restart

echo "✅ Deployment completed successfully!"
