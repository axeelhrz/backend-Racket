#!/bin/bash

echo "🚀 Instalando Raquet Power Backend en cPanel..."

# Instalar dependencias de Composer
echo "📦 Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

# Generar clave de aplicación
echo "🔑 Generando clave de aplicación..."
php artisan key:generate

# Crear enlace simbólico para storage (si es necesario)
echo "🔗 Creando enlace simbólico para storage..."
php artisan storage:link

# Limpiar y optimizar cachés
echo "🧹 Optimizando cachés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones
echo "🗄️ Ejecutando migraciones de base de datos..."
php artisan migrate --force

# Ejecutar seeders (opcional)
echo "🌱 Ejecutando seeders..."
php artisan db:seed --force

echo "✅ Instalación completada!"
echo "🌐 Tu API estará disponible en: https://tudominio.com/api/"