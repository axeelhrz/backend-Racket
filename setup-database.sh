#!/bin/bash

echo "🚀 Setting up Raquet Power Database..."
echo ""

# Check if we're in the backend directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the backend directory (backendd/)"
    exit 1
fi

# Reset database (optional - uncomment if needed)
echo "🗄️  Resetting database..."
php artisan migrate:fresh --force

echo ""
echo "📊 Running migrations..."
php artisan migrate --force

echo ""
echo "🌱 Seeding database with initial data..."
php artisan db:seed --force

echo ""
echo "✅ Database setup completed successfully!"
echo ""
echo "📧 Default credentials:"
echo "Super Admin: admin@raquetpower.com / admin123456"
echo "League Admins: [email] / liga123456"
echo "Club Admins: [email] / club123456"
echo ""
echo "🎯 Available test accounts:"
echo "Liga Nacional: liga.nacional@raquetpower.com / liga123456"
echo "Liga Pichincha: liga.pichincha@raquetpower.com / liga123456"
echo "Club Campeones: club.campeones@raquetpower.com / club123456"
echo "Club Raqueta de Oro: club.raquetadeoro@raquetpower.com / club123456"
echo ""
echo "🚀 You can now test the authentication system!"