# 🔧 Solución: Error "Failed to fetch"

## 🚨 Problema
El error "Failed to fetch" indica que el frontend no puede conectarse con la API del backend.

## 🔍 Diagnóstico Rápido

### 1️⃣ Usar el Archivo de Prueba
1. Abre `test-connection.html` en tu navegador
2. Haz clic en "🚀 Probar Conexión"
3. Haz clic en "🐘 Probar PHP"
4. Revisa los resultados

### 2️⃣ Verificar Configuración del Servidor

#### ✅ **Si usas XAMPP/WAMP/MAMP:**
- Asegúrate de que Apache y MySQL estén ejecutándose
- Los archivos deben estar en `htdocs/` (XAMPP) o `www/` (WAMP)
- Accede via `http://localhost/tu-proyecto/`

#### ✅ **Si usas un servidor local:**
- Verifica que PHP esté habilitado
- Comprueba que mod_rewrite esté activo
- Los archivos deben tener permisos correctos

## 🛠️ Soluciones Paso a Paso

### Solución 1: Verificar Ubicación de Archivos
```
📁 Estructura correcta:
htdocs/tu-proyecto/
├── index.html
├── api/
│   ├── index.php
│   ├── controllers/
│   └── ...
├── js/
├── css/
└── ...
```

### Solución 2: Verificar URL de Acceso
- ❌ **Incorrecto**: `file:///C:/xampp/htdocs/proyecto/index.html`
- ✅ **Correcto**: `http://localhost/proyecto/`

### Solución 3: Configurar Base de Datos
1. Crear base de datos MySQL llamada `gestion_produccion`
2. Ejecutar `api/install/database.sql`
3. Configurar `api/config/config.php` con tus datos

### Solución 4: Verificar PHP
Crear archivo `test-php.php`:
```php
<?php
echo "PHP está funcionando!";
phpinfo();
?>
```

## 🔧 Mejoras Implementadas

### ✅ **Detección Automática de URLs**
La aplicación ahora prueba automáticamente diferentes URLs:
- `./api/endpoint`
- `/api/endpoint`  
- `api/endpoint`

### ✅ **Logs Detallados**
Abre las herramientas de desarrollador (F12) para ver:
- URLs que se están probando
- Respuestas del servidor
- Errores específicos

### ✅ **Fallback Inteligente**
Si una URL falla, automáticamente prueba la siguiente.

## 🚀 Pasos para Resolver

### 1. **Verificar Servidor Web**
```bash
# Si usas XAMPP, verificar que esté ejecutándose:
# - Apache: ✅ Running
# - MySQL: ✅ Running
```

### 2. **Verificar Ubicación**
- Los archivos deben estar en la carpeta web del servidor
- Acceder via `http://localhost/` no `file://`

### 3. **Verificar Base de Datos**
- Crear base de datos `gestion_produccion`
- Importar `api/install/database.sql`
- Configurar credenciales en `api/config/config.php`

### 4. **Probar Conexión**
- Abrir `test-connection.html`
- Revisar resultados de las pruebas
- Verificar logs en consola del navegador

## 📞 Si el Problema Persiste

### Revisar Logs del Servidor
- **XAMPP**: `xampp/apache/logs/error.log`
- **WAMP**: `wamp/logs/apache_error.log`

### Verificar Configuración PHP
- `php.ini` debe permitir `allow_url_fopen`
- Extensiones MySQL habilitadas
- `mod_rewrite` activo en Apache

### Contactar Soporte
Si nada funciona, proporciona:
- Resultado de `test-connection.html`
- Logs de la consola del navegador
- Configuración de tu servidor web
- Sistema operativo que usas

---

**¡Con estas mejoras, la aplicación debería conectarse automáticamente! 🎉**