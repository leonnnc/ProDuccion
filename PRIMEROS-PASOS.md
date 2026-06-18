# 🚀 Primeros Pasos - Despliegue Automático

## ⚡ Configuración en 5 Minutos

### 1️⃣ Crear Repositorio en GitHub
1. Ve a [GitHub.com](https://github.com)
2. Clic en **"New repository"**
3. Nombre: `sistema-gestion-produccion`
4. Descripción: `Sistema de Gestión de Producción Audiovisual`
5. Público o Privado (tu elección)
6. **NO** marcar "Add a README file"
7. Clic en **"Create repository"**

### 2️⃣ Subir tu Proyecto
```bash
# En tu terminal/cmd, dentro de la carpeta del proyecto:
git init
git add .
git commit -m "🎬 Sistema de Gestión de Producción - Versión inicial"
git branch -M main
git remote add origin https://github.com/TU-USUARIO/sistema-gestion-produccion.git
git push -u origin main
```

### 3️⃣ Configurar Secrets de FTP
1. Ve a tu repositorio en GitHub
2. **Settings** > **Secrets and variables** > **Actions**
3. Clic en **"New repository secret"**
4. Agregar estos 3 secrets:

#### 🔐 FTP_SERVER
- **Name**: `FTP_SERVER`
- **Secret**: `ftp.tudominio.com` (sin http://)

#### 🔐 FTP_USERNAME  
- **Name**: `FTP_USERNAME`
- **Secret**: `usuario@tudominio.com`

#### 🔐 FTP_PASSWORD
- **Name**: `FTP_PASSWORD`
- **Secret**: `tu-contraseña-ftp`

### 4️⃣ ¡Primer Despliegue!
```bash
# Hacer cualquier cambio pequeño (ej: agregar un comentario)
git add .
git commit -m "🚀 Primer despliegue automático"
git push origin main
```

### 5️⃣ Verificar Despliegue
1. Ve a tu repositorio en GitHub
2. Clic en la pestaña **"Actions"**
3. Verás el despliegue en progreso
4. Cuando aparezca ✅, tu sitio estará actualizado

## 🎯 Datos que Necesitas de tu Hosting

Contacta a tu proveedor de hosting para obtener:

| Dato | Ejemplo | Dónde Encontrarlo |
|------|---------|-------------------|
| **Servidor FTP** | `ftp.tudominio.com` | Panel de control del hosting |
| **Usuario FTP** | `usuario@tudominio.com` | Configuración de FTP |
| **Contraseña FTP** | `contraseña123` | La que configuraste |
| **Directorio** | `/public_html/` | Usualmente este por defecto |

## 🔄 Flujo de Trabajo Diario

```bash
# 1. Hacer cambios en el código
# 2. Probar localmente
git add .
git commit -m "Descripción de los cambios"
git push origin main
# 3. ¡Se despliega automáticamente!
```

## 🆘 ¿Problemas?

### ❌ Error en GitHub Actions
- Revisar que los secrets estén correctos
- Verificar que el FTP esté habilitado
- Contactar soporte del hosting

### ❌ No se actualiza el sitio
- Limpiar caché del navegador (Ctrl+F5)
- Esperar 2-3 minutos después del despliegue
- Verificar que no haya errores en Actions

---

**¡Tu sistema está listo para despliegue automático! 🎉**

Cada cambio que hagas se desplegará automáticamente a tu hosting.