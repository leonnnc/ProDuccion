# 🖥️ Configurar Servidor Local

## 🚨 Problema Actual
El error "No se pudo conectar con la API" significa que **necesitas un servidor web ejecutándose** para que funcione la aplicación completa.

## 🎬 Solución Inmediata: Demo Sin Servidor

### ✅ **Usar el Demo**
1. Abre `app-demo.html` en tu navegador
2. Usa las credenciales: `demo@sistema.com` / `demo123`
3. Explora todas las funcionalidades (datos simulados)

## 🖥️ Configurar Servidor Local Completo

### Opción 1: XAMPP (Recomendado)

#### 1️⃣ **Descargar e Instalar**
- Ve a [apachefriends.org](https://www.apachefriends.org/)
- Descarga XAMPP para tu sistema operativo
- Instala con configuración por defecto

#### 2️⃣ **Configurar Proyecto**
```bash
# Copiar archivos del proyecto a:
C:\xampp\htdocs\gestion-produccion\

# Estructura final:
C:\xampp\htdocs\gestion-produccion\
├── index.html
├── api/
├── js/
├── css/
└── ...
```

#### 3️⃣ **Iniciar Servicios**
1. Abrir XAMPP Control Panel
2. Iniciar **Apache** ✅
3. Iniciar **MySQL** ✅

#### 4️⃣ **Configurar Base de Datos**
1. Ir a `http://localhost/phpmyadmin`
2. Crear base de datos: `gestion_produccion`
3. Importar: `api/install/database.sql`

#### 5️⃣ **Configurar Conexión**
Editar `api/config/config.php`:
```php
'database' => [
    'host' => 'localhost',
    'name' => 'gestion_produccion',
    'user' => 'root',
    'password' => '', // Vacío en XAMPP
    'charset' => 'utf8mb4'
]
```

#### 6️⃣ **Acceder**
- URL: `http://localhost/gestion-produccion/`
- ✅ Debería funcionar completamente

### Opción 2: WAMP (Windows)

#### 1️⃣ **Descargar e Instalar**
- Ve a [wampserver.com](http://www.wampserver.com/)
- Descarga e instala WAMP

#### 2️⃣ **Configurar**
```bash
# Copiar archivos a:
C:\wamp64\www\gestion-produccion\

# Acceder via:
http://localhost/gestion-produccion/
```

### Opción 3: Servidor PHP Integrado

#### Para Desarrollo Rápido
```bash
# En la carpeta del proyecto:
php -S localhost:8000

# Acceder via:
http://localhost:8000
```

**⚠️ Nota:** Necesitarás configurar MySQL por separado.

## 🔧 Verificar Configuración

### 1️⃣ **Probar PHP**
Crear `test.php`:
```php
<?php
echo "✅ PHP funcionando!<br>";
echo "Versión: " . PHP_VERSION;
?>
```

### 2️⃣ **Probar MySQL**
```php
<?php
$conn = new mysqli('localhost', 'root', '', 'gestion_produccion');
if ($conn->connect_error) {
    echo "❌ Error: " . $conn->connect_error;
} else {
    echo "✅ MySQL conectado!";
}
?>
```

### 3️⃣ **Usar Herramienta de Diagnóstico**
- Abrir `test-connection.html`
- Hacer clic en "Probar Conexión"
- Verificar resultados

## 🚀 Alternativas Online

### Hosting Gratuito para Pruebas
- **000webhost.com** - Hosting PHP/MySQL gratuito
- **InfinityFree** - Hosting ilimitado gratuito
- **Heroku** - Para aplicaciones web

### Servicios de Desarrollo
- **GitHub Codespaces** - Entorno completo en la nube
- **Repl.it** - IDE online con servidor incluido

## 📋 Checklist de Configuración

### ✅ **Servidor Web**
- [ ] Apache/Nginx ejecutándose
- [ ] PHP habilitado (versión 8.0+)
- [ ] mod_rewrite activo

### ✅ **Base de Datos**
- [ ] MySQL ejecutándose
- [ ] Base de datos `gestion_produccion` creada
- [ ] Tablas importadas desde `database.sql`

### ✅ **Archivos**
- [ ] Proyecto en carpeta web del servidor
- [ ] Permisos de lectura/escritura correctos
- [ ] Configuración en `api/config/config.php`

### ✅ **Acceso**
- [ ] URL: `http://localhost/tu-proyecto/`
- [ ] No usar `file://` sino `http://`

## 🆘 Solución de Problemas

### ❌ **"Apache no inicia"**
- Cambiar puerto en XAMPP (80 → 8080)
- Desactivar IIS si está activo
- Verificar que no haya otro servidor ejecutándose

### ❌ **"MySQL no conecta"**
- Verificar credenciales en `config.php`
- Asegurar que MySQL esté ejecutándose
- Crear usuario/contraseña si es necesario

### ❌ **"Página no carga"**
- Verificar URL: `http://localhost/proyecto/`
- Comprobar que archivos estén en `htdocs/`
- Revisar logs de Apache

## 🎯 Resultado Final

Una vez configurado correctamente:
- ✅ **Login/Registro** funcionando
- ✅ **Dashboard** con datos reales
- ✅ **Proyectos, Tareas, Calendario** completamente funcionales
- ✅ **API REST** respondiendo correctamente
- ✅ **Base de datos** guardando información

---

**¡Con servidor local tendrás la experiencia completa! 🚀**

Mientras tanto, usa `app-demo.html` para explorar las funcionalidades.