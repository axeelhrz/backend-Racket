#!/bin/bash

echo "🚀 Deploying CORS fixes to Railway..."

# Clear Laravel caches
echo "📦 Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache

# Run database migrations if needed
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Test the API endpoints
echo "🧪 Testing API endpoints..."
echo "Testing health endpoint..."
curl -X GET "https://web-production-40b3.up.railway.app/api/health" \
  -H "Accept: application/json" \
  -H "Origin: https://raquet-power2-0.vercel.app"

echo ""
echo "Testing CORS with OPTIONS request..."
curl -X OPTIONS "https://web-production-40b3.up.railway.app/api/test" \
  -H "Origin: https://raquet-power2-0.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v

echo ""
echo "Testing database connection..."
curl -X GET "https://web-production-40b3.up.railway.app/api/db-check" \
  -H "Accept: application/json" \
  -H "Origin: https://raquet-power2-0.vercel.app"

echo ""
echo "✅ Deployment complete! Test your frontend now."
echo ""
echo "🔧 If you still have issues, try these test commands in your browser console:"
echo ""
echo "// Test CORS:"
echo "fetch('https://web-production-40b3.up.railway.app/api/test').then(r=>r.json()).then(console.log)"
echo ""
echo "// Test registration:"
echo "fetch('https://web-production-40b3.up.railway.app/api/test-register', {"
echo "  method: 'POST',"
echo "  headers: {'Content-Type': 'application/json'},"
echo "  body: JSON.stringify({name: 'Test', email: 'test@example.com', password: '123456'})"
echo "}).then(r=>r.json()).then(console.log)"