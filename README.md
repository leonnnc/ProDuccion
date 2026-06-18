# ProDuccion 🎬✨

**ProDuccion** es una Aplicación Web Progresiva (PWA) de alto rendimiento y diseño moderno (Glassmorphism), diseñada específicamente para la gestión integral de equipos de producción audiovisual, técnicos y creativos en iglesias, ministerios, centros de eventos y organizaciones de transmisión en vivo.

El sistema unifica la agenda de servicios bimestrales, el control de asistencia, la capacitación del personal y el reconocimiento al servicio, todo sincronizado en tiempo real. Está construido sin frameworks pesados, utilizando tecnologías nativas de la web (HTML, CSS y JS puro) conectadas de forma directa y reactiva con Firebase.

---

## 🚀 Características Clave y Nuevos Módulos (v2.2.4)

### 1. 📅 Agenda Bimestral y Reservas en Tiempo Real
*   **Gestión por Áreas**: Soporte para múltiples roles técnicos: *Cámaras, Switchers, Visuales, Iluminación, Fotografía, Streaming, Sonido, Edición, Protocolos y Diseño*.
*   **Asignación de Turnos**: Los líderes pueden programar los servicios del mes y los voluntarios (Siervos) pueden inscribirse directamente a los roles requeridos.
*   **Presencia Activa**: Panel interactivo con lista de usuarios conectados en vivo y chat global con envío de imágenes.

### 2. 🔔 Panel Inteligente: "Mi Próximo Servicio"
*   Ubicado en la parte superior del Dashboard de cada voluntario. Muestra dinámicamente si tiene algún servicio programado próximamente.
*   **Confirmación Directa**: Permite al voluntario confirmar asistencia (`confirmado: true`) o reportar inasistencia con un clic.
*   **Integración en la Agenda**: Cuando un siervo confirma asistencia, aparece un check verde `✔` junto a su nombre en la agenda general visible para todos los líderes de área.
*   **Acceso a Recursos**: Descarga rápida con un clic del archivo PDF con el cronograma y lineamientos del servicio semanal.

### 3. 💬 Mural de Agradecimientos y Logros del Domingo
*   Un feed social interactivo en la base del Dashboard donde los Coordinadores y Administradores pueden felicitar públicamente al equipo por su labor.
*   **Reacciones Sociales en Tiempo Real**: Permite reaccionar con emojis (❤️ Amor, 🙌 Celebración, 👏 Aplauso) que se actualizan y reflejan en caliente para todos los usuarios.
*   **Generador de Notificaciones**: Al publicar una nueva nota de agradecimiento, se despacha una notificación nativa en el dispositivo de todos los siervos registrados.

### 4. 🏅 Gamificación de Servicio y Medallas de Perfil
*   Diseñado para motivar e incentivar el compromiso del equipo técnico. El sistema analiza la base de datos local y dibuja de forma interactiva medallas SVG personalizadas en el perfil del voluntario:
    1.  🎓 **Especialista Capacitado**: Otorgada al completar la ruta básica de capacitación (onboarding).
    2.  🔥 **Fiel Servidor**: Con 3 niveles de progresión (Bronce, Plata, Oro) basados en la asistencia histórica acumulada (1+, 5+, 12+ servicios).
    3.  🌟 **Compromiso de Acero**: Para voluntarios activos que tengan 3 o más servicios futuros reservados y confirmados.
    4.  🛡️ **Pilar del Equipo**: Reconoce la constancia de usuarios cuya antigüedad de registro en el sistema supera los 30 días.
*   **Barra de Progreso de Onboarding**: Muestra visualmente qué porcentaje de las capacitaciones obligatorias ha completado el usuario.

### 5. 📖 Sección "Aprende" y Ruta de Onboarding
*   Renovada para dar capacitaciones técnicas específicas por área técnica.
*   **Reproductor Centralizado Premium**: Cuadro de video de YouTube de alta fidelidad con título interactivo, descripción del curso y botón de autogestión `[✓ Marcar curso como completado]`.
*   **Cursos Fijos Integrados**:
    *   🎥 *Operación de Cámaras y Composición* (Área Cámaras - ID: `3n3_Suh_kKk`)
    *   📸 *Fotografía y Control de Luz* (Área Fotografía - ID: `8BGDwD9sRsw`)
    *   🎛️ *Dirección de Switcher de Video en Vivo* (Área Switchers - ID: `L06w4dJ9q98`)
*   Al hacer clic en "Iniciar Curso", el video se carga de forma instantánea en el reproductor principal con animaciones suaves y se desplaza automáticamente hacia la pantalla de visualización.

### 6. 🚀 Control de Versiones PWA y Actualizaciones Instantáneas
*   **Indicador de Versión**: Visibilidad en el menú lateral de la versión activa de la PWA (ej: `v2.2.4`).
*   **Actualizaciones con un clic**: La PWA detecta silenciosamente cambios en el Service Worker. Al haber una nueva versión disponible en el servidor, despliega un banner interactivo en la esquina del Dashboard: *"🚀 Actualización Disponible. Nueva versión lista para instalar. [Actualizar]"*.
*   Al presionar "Actualizar", la PWA ejecuta `skipWaiting()`, purga las cachés antiguas e instala la nueva versión refrescando el navegador instantáneamente sin necesidad de intervención manual o de reinstalar el aplicativo.

---

## 🛠️ Tecnologías y Estructura Técnica

El proyecto sigue un enfoque ultra-liviano de "no-frameworks", lo cual asegura tiempos de carga sub-segundo, compatibilidad total en móviles antiguos y facilidad extrema de hosting:

*   **Frontend**: HTML5 Semántico, Javascript moderno (ES6+ Modules), y CSS3 puro con variables dinámicas de color para soporte de tema claro/oscuro.
*   **Backend (BaaS)**: Conectado a **Firebase**:
    *   *Realtime Database* (Sincronización en caliente y persistencia de Agenda, Mural, Presencia, Mensajes y Perfiles).
    *   *Firebase Authentication* (Registro y acceso seguro de voluntarios).
    *   *Firebase Storage* (Almacenamiento de avatares de usuario y PDFs de programación).
*   **PWA**: Estrategia de caching `Stale-While-Revalidate` a través de `sw.js` que permite al software funcionar sin internet de forma temporal y cargar en milisegundos.

---

## 💻 Instalación y Ejecución Local

Para levantar el entorno de desarrollo local, siga estos pasos:

1.  **Clonar el Repositorio**:
    ```bash
    git clone https://github.com/leonnnc/produccion2000.git
    cd produccion2000
    ```

2.  **Iniciar un Servidor Web Local**:
    Dado que es un sitio estático, puede usar cualquier servidor web.
    
    *   **Usando Python**:
        ```bash
        python -m http.server 5888
        ```
    *   **Usando Node.js**:
        ```bash
        npx http-server -p 5888
        ```

3.  **Acceder al Navegador**:
    Abra su navegador y visite `http://localhost:5888`

---

## ⚙️ Configuración y Despliegue con Firebase

Para conectar este proyecto a su propia base de datos de Firebase:

1.  Vaya a [Firebase Console](https://console.firebase.google.com/) y cree un nuevo proyecto.
2.  Habilite **Email/Password Provider** en *Authentication*.
3.  Active **Realtime Database** y configure las siguientes reglas de lectura y escritura en la pestaña *Reglas*:
    ```json
    {
      "rules": {
        ".read": "auth != null",
        ".write": "auth != null"
      }
    }
    ```
4.  Active **Firebase Storage** y establezca las reglas básicas de acceso.
5.  Copie la configuración de su Aplicación Web de Firebase (ApiKey, AuthDomain, DatabaseURL, ProjectId, etc.) y reemplace el objeto de configuración inicial de Firebase en [firebase.js](file:///c:/Users/LpLeonnnc/Documents/web/produccion/firebase.js):
    ```javascript
    const firebaseConfig = {
        apiKey: "TU_API_KEY",
        authDomain: "TU_AUTH_DOMAIN",
        databaseURL: "TU_DATABASE_URL",
        projectId: "TU_PROJECT_ID",
        storageBucket: "TU_STORAGE_BUCKET",
        messagingSenderId: "TU_MESSAGING_SENDER_ID",
        appId: "TU_APP_ID"
    };
    ```

---

## 🔄 ¿Cómo Actualizar la Versión del Software (PWA)?

Cuando realice cambios en el código (HTML, CSS o JS) y desee que los usuarios finales reciban la actualización de inmediato:

1.  Abra el archivo [sw.js](file:///c:/Users/LpLeonnnc/Documents/web/produccion/sw.js) y modifique la constante `CACHE_NAME` incrementando el número de versión:
    ```javascript
    const CACHE_NAME = 'produc-v12'; // Cambiar de v11 a v12
    ```
2.  Abra [dashboard.html](file:///c:/Users/LpLeonnnc/Documents/web/produccion/dashboard.html) y modifique el número de versión visualizado en el pie de página de la barra lateral:
    ```html
    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 15px; border-top: 1px solid var(--panel-border); padding-top: 8px;">
        Versión v2.2.4
    </div>
    ```
3.  Suba los cambios a su hosting de producción. El navegador de los usuarios detectará automáticamente el cambio en `sw.js`, descargará en segundo plano los nuevos archivos, y desplegará el popup flotante para aplicar la actualización en un clic.

---

## 🤝 Contribuciones

Si desea contribuir agregando nuevos módulos, mejorando los estilos CSS o refactorizando código:

1.  Haga un **Fork** de este repositorio.
2.  Cree una rama para su cambio: `git checkout -b feature/nueva-mejora`.
3.  Realice los commits detallando los cambios.
4.  Envíe un **Pull Request** a la rama principal `main` para su posterior revisión.

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Libre de uso, modificación y distribución comercial o comunitaria para el servicio y la excelencia en producciones audiovisuales.
