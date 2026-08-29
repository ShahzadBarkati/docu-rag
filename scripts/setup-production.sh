#!/bin/bash
# Production setup script for Docu-RAG

set -e

echo "🚀 Setting up Docu-RAG for production..."

# Install dependencies
echo "📦 Installing PHP dependencies..."
cd backend
composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate application key if not exists
if [ ! -f .env ]; then
    echo "⚙️ Setting up environment..."
    cp .env.example .env
fi

php artisan key:generate --no-interaction

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

# Optimize application
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo "✅ Production setup completed!"
echo "📝 Next steps:"
echo "   1. Configure your .env file with production values"
echo "   2. Set up your Neon database connection"
echo "   3. Add your GEMINI_API_KEY to .env"
echo "   4. Deploy using GitHub Actions"