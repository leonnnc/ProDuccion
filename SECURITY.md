# 🔒 Política de Seguridad

## Versiones Soportadas

Actualmente damos soporte de seguridad a las siguientes versiones:

| Versión | Soportada          |
| ------- | ------------------ |
| 1.0.x   | ✅ Sí             |

## 🚨 Reportar una Vulnerabilidad

Si descubres una vulnerabilidad de seguridad, por favor **NO** abras un issue público.

### Proceso de Reporte

1. **Email**: Envía un email detallado a [security@tudominio.com]
2. **Información**: Incluye toda la información posible:
   - Descripción de la vulnerabilidad
   - Pasos para reproducir
   - Impacto potencial
   - Versión afectada

### Qué Esperar

- **Confirmación**: Respuesta en 48 horas
- **Evaluación**: Análisis en 7 días
- **Resolución**: Parche en 30 días (dependiendo de la severidad)
- **Divulgación**: Coordinada después del parche

## 🛡️ Medidas de Seguridad Implementadas

### Autenticación
- ✅ Hash de contraseñas con bcrypt
- ✅ Sesiones seguras con regeneración de ID
- ✅ Timeout automático de sesiones
- ✅ Protección contra fuerza bruta

### Validación de Datos
- ✅ Validación en frontend y backend
- ✅ Sanitización de entrada
- ✅ Protección contra inyección SQL
- ✅ Validación de tipos de datos

### Headers de Seguridad
- ✅ CORS configurado
- ✅ X-Content-Type-Options
- ✅ X-Frame-Options
- ✅ X-XSS-Protection
- ✅ Referrer-Policy

### Archivos y Permisos
- ✅ Protección de archivos sensibles
- ✅ Prevención de listado de directorios
- ✅ Configuración segura de Apache

## 🔍 Auditorías de Seguridad

Realizamos auditorías regulares de:
- Dependencias de terceros
- Configuración del servidor
- Código fuente
- Logs de acceso

## 📞 Contacto

Para reportes de seguridad:
- **Email**: security@tudominio.com
- **Respuesta**: 48 horas máximo

¡Gracias por ayudar a mantener el proyecto seguro! 🙏