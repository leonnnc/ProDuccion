# 🚀 Despliegue Automático con GitHub Actions

## ⚡ Configuración Rápida

### 1. Subir el proyecto a GitHub

```bash
git init
git add .
git commit -m "Sistema de Gestión de Producción - Versión inicial"
git remote add origin https://github.com/TU-USUARIO/TU-REPOSITORIO.git
git push -u origin main
```

### 2. Configurar Secrets en GitHub

Ve a tu repositorio en GitHub:

1. **Settings** > **Secrets and variables** > **Actions**
2. Hacer clic en **"New repository secret"**
3. Agregar estos 3 secrets:

| Secret         | Valor                   | Ejemplo                 |
| -------------- | ----------------------- | ----------------------- |
| `FTP_SERVER`   | Dirección de tu hosting | `ftp.tudominio.com`     |
| `FTP_USERNAME` | Usuario FTP             | `usuario@tudominio.com` |
| `FTP_PASSWORD` | Contraseña FTP          | `tu-contraseña-segura`  |

### 3. ¡Listo! 🎉

Cada vez que hagas `git push origin main`, se desplegará automáticamente.

## 🔄 Flujo de Trabajo

```bash
# 1. Hacer cambios en el código
# 2. Guardar y probar localmente
git add .
git commit -m "Descripción de los cambios"
git push origin main
# 3. ¡GitHub Actions despliega automáticamente!
```

## 📊 Monitorear Despliegues

1. Ve a tu repositorio en GitHub
2. Clic en la pestaña **"Actions"**
3. Verás el historial de todos los despliegues
4. Clic en cualquier despliegue para ver detalles

## 🛠️ Solución de Problemas

### ❌ Error de conexión FTP

- Verificar que los secrets estén correctos
- Comprobar que el FTP esté habilitado en tu hosting
- Contactar soporte de tu proveedor

### ❌ Archivos no se actualizan

- Limpiar caché del navegador (Ctrl+F5)
- Verificar que el despliegue terminó exitosamente
- Revisar logs en la pestaña Actions

## 📞 Datos del Hosting

Necesitas obtener de tu proveedor de hosting:

- **Servidor FTP**: Ejemplo: `ftp.tudominio.com`
- **Usuario**: Ejemplo: `usuario@tudominio.com`
- **Contraseña**: Tu contraseña de FTP
- **Directorio**: Usualmente `/public_html/`

---

**¡Despliegue automático configurado! 🚀**

Cada push a `main` desplegará tu sistema automáticamente.
