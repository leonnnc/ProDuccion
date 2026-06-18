# 📋 Guía de Instalación - Sistema de Gestión de Producción

## 🎯 Requisitos del Sistema

### Servidor Web
- **Apache 2.4+** con mod_rewrite habilitado
- **PHP 8.0+** con las siguientes extensiones:
  - PDO y PDO_MySQL
  - JSON
  - Session
  - mbstring
  - openssl

### Base de Datos
- **MySQL 5.7+** o **MariaDB 10.3+**

### Hosting Recomendado
- **GoDaddy Shared Hosting** (compatible)
- **cPanel** con acceso a bases de datos MySQL
- Al menos **500MB** de espacio en disco
- **PHP 8.0+** habilitado

## 🚀 Instalación Paso a Paso

### Paso 1: Descargar y Subir Archivos

1. **Descarga** todos los archivos del sistema
2. **Sube** los archivos a tu directorio `public_html` (o carpeta raíz de tu dominio)
3. **Estructura** final debe verse así:
   ```
   public_html/
   ├── index.html
   ├── .htaccess
   ├── css/
   ├── js/
   ├── api/
   └── assets/
   ```

### Paso 2: Configurar Base de Datos

#### 2.1 Crear Base de Datos en cPanel
1. Accede a **cPanel** de tu hosting
2. Ve a **"Bases de datos MySQL"**
3. Crea una nueva base de datos: `tu_usuario_gestion_produccion`
4. Crea un usuario para la base de datos
5. Asigna **todos los privilegios** al usuario

#### 2.2 Configurar Conexión
1. Edita el archivo `api/config/config.php`:
   ```php
   <?php
   return [
       'database' => [
           'host' => 'localhost',
           'name' => 'tu_usuario_gestion_produccion',
           'user' => 'tu_usuario_db',
           'password' => 'tu_password_db'
       ],
       'app' => [
           'debug' => false, // Cambiar a true solo para debugging
           'timezone' => 'America/Mexico_City'
       ]
   ];
   ?>
   ```

### Paso 3: Instalar Base de Datos

#### Opción A: Instalación Automática (Recomendada)
1. Ve a: `https://tudominio.com/api/install/install.php`
2. Sigue las instrucciones en pantalla
3. El sistema creará automáticamente todas las tablas y datos iniciales

#### Opción B: Instalación Manual
1. Accede a **phpMyAdmin** desde cPanel
2. Selecciona tu base de datos
3. Ve a la pestaña **"Importar"**
4. Sube el archivo `api/install/database.sql`
5. Ejecuta la importación

### Paso 4: Configurar Permisos

#### 4.1 Permisos de Archivos
Asegúrate de que los permisos sean correctos:
- **Carpetas**: 755
- **Archivos PHP**: 644
- **Archivo .htaccess**: 644

#### 4.2 Verificar mod_rewrite
El archivo `.htaccess` debe estar en la raíz y contener:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/index.php [QSA,L]
```

### Paso 5: Primer Acceso

1. **Accede** a tu sitio: `https://tudominio.com`
2. **Regístrate** con los siguientes datos de prueba:
   - **Email**: admin@tudominio.com
   - **Contraseña**: password123
   - **Grupo**: Admin
   - **Área**: Coordinación

3. **Cambia** inmediatamente la contraseña por una segura

## 🔧 Configuración Adicional

### Configurar Email (Opcional)
Para que funcionen los emails de bienvenida:

1. Edita `api/config/config.php` y agrega:
   ```php
   'email' => [
       'from_name' => 'Sistema de Gestión de Producción',
       'from_email' => 'noreply@tudominio.com',
       'reply_to' => 'soporte@tudominio.com'
   ]
   ```

### Configurar Timezone
Ajusta la zona horaria en `api/config/config.php`:
```php
'app' => [
    'timezone' => 'America/Mexico_City' // Cambia según tu ubicación
]
```

### Habilitar HTTPS (Recomendado)
1. Activa **SSL** en tu hosting
2. Agrega al inicio de `.htaccess`:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

## 🧪 Verificar Instalación

### Pruebas Básicas
1. **Acceso**: Verifica que puedas acceder a la página principal
2. **Login**: Prueba el sistema de autenticación
3. **Navegación**: Verifica que todos los módulos carguen correctamente
4. **API**: Prueba que `https://tudominio.com/api/auth/check-session` responda

### Datos de Prueba
El sistema incluye datos de ejemplo:
- **Usuarios**: 5 usuarios de diferentes áreas
- **Áreas**: 11 áreas de producción audiovisual
- **Grupos**: Admin, Staff, Users
- **Base de conocimiento**: Respuestas del chatbot

## 🔒 Seguridad Post-Instalación

### 1. Cambiar Credenciales
- Cambia **todas** las contraseñas por defecto
- Usa contraseñas **fuertes** (mínimo 8 caracteres)

### 2. Configurar Códigos de Administrador
Edita los códigos válidos en `js/app.js`:
```javascript
const validCodes = ['TU_CODIGO_SECRETO', 'OTRO_CODIGO'];
```

### 3. Deshabilitar Debug
En `api/config/config.php`:
```php
'debug' => false
```

### 4. Eliminar Archivos de Instalación
Después de la instalación, elimina:
- `api/install/install.php` (opcional)
- `test-areas.html`

## 🆘 Solución de Problemas

### Error: "Base de datos no encontrada"
- Verifica las credenciales en `config.php`
- Asegúrate de que la base de datos existe
- Verifica que el usuario tenga permisos

### Error: "Página no encontrada" (404)
- Verifica que `.htaccess` esté en la raíz
- Confirma que mod_rewrite esté habilitado
- Revisa los permisos de archivos

### Error: "No se puede conectar a la API"
- Verifica que PHP esté funcionando
- Revisa los logs de error del servidor
- Confirma que las rutas de la API respondan

### Problemas de Permisos
```bash
# Si tienes acceso SSH, ejecuta:
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 644 .htaccess
```

### Limpiar Caché
Si hay problemas después de cambios:
1. Limpia caché del navegador
2. Reinicia sesión PHP si es posible
3. Verifica logs de error

## 📞 Soporte

### Logs de Error
Revisa los logs en:
- **cPanel**: Logs de error
- **PHP**: `error_log` en la carpeta raíz
- **MySQL**: Logs de la base de datos

### Información del Sistema
Para obtener ayuda, proporciona:
- Versión de PHP
- Versión de MySQL
- Mensajes de error específicos
- Configuración del hosting

### Contacto
- **Email**: soporte@tudominio.com
- **Documentación**: Revisa este archivo
- **Logs**: Incluye logs relevantes al reportar problemas

## 🎉 ¡Instalación Completada!

Una vez completada la instalación:

1. **Explora** todos los módulos del sistema
2. **Configura** usuarios y áreas según tus necesidades
3. **Personaliza** el sistema con tus datos
4. **Capacita** a tu equipo en el uso del sistema

¡Tu Sistema de Gestión de Producción está listo para usar! 🚀