# 🧹 Instrucciones de Limpieza de Datos de Prueba

Este documento explica cómo limpiar todos los datos de prueba que se agregaron durante el desarrollo y testing del sistema.

## 📋 Datos que se Limpiarán

### 1. Campos Personalizados (`custom_fields`)
- **Marcas** (compartidas entre raquetas y cauchos)
- **Modelos de Raqueta**
- **Modelos de Caucho Drive**
- **Modelos de Caucho Back**
- **Hardness Drive**
- **Hardness Back**
- **Clubes**
- **Ligas**

### 2. Registros de Prueba (`quick_registrations`)
- Registros con emails de prueba (test, prueba, demo)
- Registros con nombres de prueba
- Registros con datos por defecto del formulario

## 🚀 Métodos de Limpieza

### Opción 1: Script Automático (Recomendado)

```bash
# Limpieza completa con confirmaciones
./clean-test-data.sh

# Limpieza automática sin confirmaciones
./clean-test-data.sh --confirm
```

### Opción 2: Comandos Individuales

#### Limpiar Campos Personalizados

```bash
# Ver estadísticas actuales
php artisan custom-fields:clean-test-data

# Limpiar todos los campos personalizados
php artisan custom-fields:clean-test-data --confirm

# Limpiar solo un tipo específico
php artisan custom-fields:clean-test-data --type=brand --confirm
php artisan custom-fields:clean-test-data --type=club --confirm
php artisan custom-fields:clean-test-data --type=league --confirm
```

#### Limpiar Registros de Prueba

```bash
# Ver estadísticas actuales
php artisan registrations:clean-test-data

# Limpiar todos los registros de prueba
php artisan registrations:clean-test-data --confirm

# Limpiar por filtros específicos
php artisan registrations:clean-test-data --email=test --confirm
php artisan registrations:clean-test-data --name=prueba --confirm
```

## 📊 Verificación Post-Limpieza

Después de la limpieza, puedes verificar que todo esté limpio:

```bash
# Verificar campos personalizados
php artisan custom-fields:clean-test-data --type=brand
php artisan custom-fields:clean-test-data --type=club
php artisan custom-fields:clean-test-data --type=league

# Verificar registros
php artisan registrations:clean-test-data --email=test
```

## ⚠️ Precauciones

1. **Backup**: Siempre haz un backup de la base de datos antes de ejecutar la limpieza
2. **Confirmación**: Los comandos sin `--confirm` pedirán confirmación antes de proceder
3. **Irreversible**: Una vez eliminados, los datos no se pueden recuperar
4. **Producción**: Asegúrate de estar en el entorno correcto antes de ejecutar

## 🎯 Tipos de Campo Disponibles

Para el comando `--type`, puedes usar:

- `brand` - Marcas (compartidas)
- `racket_model` - Modelos de raqueta
- `drive_rubber_model` - Modelos de caucho drive
- `backhand_rubber_model` - Modelos de caucho back
- `drive_rubber_hardness` - Hardness drive
- `backhand_rubber_hardness` - Hardness back
- `club` - Clubes
- `league` - Ligas

## 📝 Ejemplos de Uso

### Limpieza Completa Paso a Paso

```bash
# 1. Ver estado actual
php artisan custom-fields:clean-test-data
php artisan registrations:clean-test-data

# 2. Ejecutar limpieza completa
./clean-test-data.sh

# 3. Verificar que todo esté limpio
php artisan custom-fields:clean-test-data
php artisan registrations:clean-test-data
```

### Limpieza Selectiva

```bash
# Solo limpiar marcas de prueba
php artisan custom-fields:clean-test-data --type=brand --confirm

# Solo limpiar clubes de prueba
php artisan custom-fields:clean-test-data --type=club --confirm

# Solo limpiar registros con email de prueba
php artisan registrations:clean-test-data --email=test --confirm
```

## 🔍 Patrones de Detección

### Registros de Prueba Detectados Automáticamente:
- Emails que contengan: `test`, `prueba`, `demo`
- Nombres que contengan: `test`, `prueba`
- Datos por defecto del formulario:
  - Nombre: `Juan`
  - Cédula: `0999999999`
  - Teléfono: `0989999999`

### Campos Personalizados:
- Todos los registros en la tabla `custom_fields` se consideran datos de prueba

## ✅ Resultado Esperado

Después de la limpieza completa:
- ✅ Tabla `custom_fields` completamente vacía
- ✅ Solo registros reales en `quick_registrations`
- ✅ Sistema listo para producción
- ✅ Solo opciones predefinidas disponibles en los formularios

## 🆘 Solución de Problemas

### Error de Permisos
```bash
chmod +x clean-test-data.sh
```

### Error de Base de Datos
```bash
php artisan migrate:status
php artisan migrate
```

### Verificar Conexión
```bash
php artisan tinker
>>> App\Models\CustomField::count()
>>> App\Models\QuickRegistration::count()
```