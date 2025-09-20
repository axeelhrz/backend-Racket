#!/bin/bash

# Script para limpiar todos los datos de prueba del sistema
# Uso: ./clean-test-data.sh [--confirm]

echo "🧹 LIMPIEZA COMPLETA DE DATOS DE PRUEBA"
echo "======================================"
echo ""

# Verificar si se debe saltar confirmaciones
CONFIRM_FLAG=""
if [ "$1" = "--confirm" ]; then
    CONFIRM_FLAG="--confirm"
    echo "⚠️  Modo automático activado (sin confirmaciones)"
    echo ""
fi

echo "📋 Este script limpiará:"
echo "  1. Todos los campos personalizados (custom_fields)"
echo "  2. Todos los registros de prueba (quick_registrations)"
echo ""

# Si no está en modo automático, pedir confirmación general
if [ -z "$CONFIRM_FLAG" ]; then
    read -p "¿Continuar con la limpieza completa? (y/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Limpieza cancelada."
        exit 1
    fi
    echo ""
fi

echo "🚀 Iniciando limpieza..."
echo ""

# 1. Limpiar campos personalizados
echo "1️⃣  Limpiando campos personalizados..."
php artisan custom-fields:clean-test-data $CONFIRM_FLAG

echo ""
echo "2️⃣  Limpiando registros de prueba..."
php artisan registrations:clean-test-data $CONFIRM_FLAG

echo ""
echo "✅ LIMPIEZA COMPLETA FINALIZADA"
echo "==============================="
echo ""
echo "📊 Para verificar el estado actual, puedes ejecutar:"
echo "   php artisan custom-fields:clean-test-data --type=brand"
echo "   php artisan registrations:clean-test-data --email=test"
echo ""
echo "🎉 ¡Sistema limpio y listo para producción!"