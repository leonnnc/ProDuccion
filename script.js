// ==========================================
// SCRIPT.JS — Login / Registro con Firebase
// ==========================================
import { DB, AUTH } from './firebase.js';
import { showNotification } from './utils.js';

const APP_VERSION = '2.2.0';

// Registrar Service Worker en idle para no bloquear la carga con soporte de actualización
if ('serviceWorker' in navigator) {
    const registerSW = () => {
        navigator.serviceWorker.register('sw.js').then(reg => {
            if (reg.waiting) {
                notificarActualizacionDisponible(reg.waiting);
            }
            reg.onupdatefound = () => {
                const installingWorker = reg.installing;
                if (installingWorker) {
                    installingWorker.onstatechange = () => {
                        if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            notificarActualizacionDisponible(installingWorker);
                        }
                    };
                }
            };
        }).catch(() => {});
    };

    if ('requestIdleCallback' in window) {
        requestIdleCallback(registerSW);
    } else {
        setTimeout(registerSW, 500);
    }

    let refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!refreshing) {
            refreshing = true;
            window.location.reload();
        }
    });
}

function notificarActualizacionDisponible(worker) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px;
        background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
        color: white; padding: 16px 20px; border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        z-index: 9999; display: flex; align-items: center; gap: 15px;
        border: 1px solid rgba(79, 172, 254, 0.4); font-family: 'Outfit', sans-serif;
        animation: slideInSW 0.3s ease-out;
    `;
    
    const styleAnim = document.createElement('style');
    styleAnim.textContent = `
        @keyframes slideInSW {
            from { transform: translateY(50px) scale(0.9); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(styleAnim);

    toast.innerHTML = `
        <div style="display:flex; flex-direction:column; gap:4px;">
            <span style="font-weight:700; font-size:0.92rem; color:#4facfe;">🚀 Actualización Disponible</span>
            <span style="font-size:0.8rem; opacity:0.8;">Nueva versión de ProDuccion lista para instalar.</span>
        </div>
        <button id="btn-actualizar-ahora" class="btn-primary" style="padding: 8px 14px; font-size: 0.78rem; border-radius: 8px; font-weight: 600; white-space: nowrap;">Actualizar</button>
    `;
    
    document.body.appendChild(toast);
    
    document.getElementById('btn-actualizar-ahora')?.addEventListener('click', () => {
        worker.postMessage({ action: 'skipWaiting' });
    });
}

// Estilos de animación
const style = document.createElement('style');
style.textContent = `@keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }`;
document.head.appendChild(style);


/**
 * Redirige al dashboard.
 */
function irAlDashboard() {
    window.location.replace('dashboard.html');
}

document.addEventListener('DOMContentLoaded', async () => {
    const loginPanel      = document.getElementById('login-panel');
    const registerPanel   = document.getElementById('register-panel');
    const forgotPanel     = document.getElementById('forgot-panel');
    const showRegisterBtn = document.getElementById('show-register');
    const showLoginBtn    = document.getElementById('show-login');
    const showForgotBtn   = document.getElementById('show-forgot');
    const showLoginFromForgotBtn = document.getElementById('show-login-from-forgot');

    if (!loginPanel) return;

    if (sessionStorage.getItem('sesion_activa')) {
        window.location.replace('dashboard.html');
        return;
    }
    // Migrar datos locales a Firebase en background (sin bloquear UI)
    DB.migrarDesdeLocalStorage().catch(() => {});

    const switchPanel = (hidePanel, showPanel) => {
        hidePanel.style.opacity = '0';
        hidePanel.style.transform = 'translateY(-20px) scale(0.95)';
        setTimeout(() => {
            hidePanel.classList.add('hidden');
            hidePanel.style.opacity = '';
            hidePanel.style.transform = '';
            showPanel.classList.remove('hidden');
            void showPanel.offsetWidth;
            showPanel.style.opacity = '1';
            showPanel.style.transform = 'translateY(0) scale(1)';
        }, 300);
    };

    if (showRegisterBtn) showRegisterBtn.addEventListener('click', (e) => { e.preventDefault(); switchPanel(loginPanel, registerPanel); });
    if (showLoginBtn)    showLoginBtn.addEventListener('click',    (e) => { e.preventDefault(); switchPanel(registerPanel, loginPanel); });
    if (showForgotBtn)   showForgotBtn.addEventListener('click',   (e) => { e.preventDefault(); switchPanel(loginPanel, forgotPanel); });
    if (showLoginFromForgotBtn) showLoginFromForgotBtn.addEventListener('click', (e) => { e.preventDefault(); switchPanel(forgotPanel, loginPanel); });

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    // Mínimo 8 caracteres, al menos una letra y un número
    function validarClave(pwd) { return pwd.length >= 8 && /[a-zA-Z]/.test(pwd) && /[0-9]/.test(pwd); }

    function mostrarError(inputId, msg) {
        const el = document.getElementById(inputId);
        if (!el) return;
        el.style.borderColor = '#ff4757';
        let hint = el.parentElement.querySelector('.field-hint');
        if (!hint) { hint = document.createElement('span'); hint.className = 'field-hint'; el.parentElement.appendChild(hint); }
        hint.textContent = msg; hint.style.color = '#ff4757';
    }
    function limpiarError(inputId) {
        const el = document.getElementById(inputId);
        if (!el) return;
        el.style.borderColor = '';
        const hint = el.parentElement.querySelector('.field-hint');
        if (hint) hint.textContent = '';
    }

    // ─── LOGIN ───────────────────────────────────────────────
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const correo = document.getElementById('login-email').value.trim();
            const clave  = document.getElementById('login-password').value;
            const btn    = e.target.querySelector('button[type="submit"]');

            if (!emailRegex.test(correo)) { showNotification('El correo no tiene un formato válido.', 'error'); return; }
            if (!clave) { showNotification('Ingresa tu contraseña.', 'error'); return; }

            btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Verificando...';
            btn.style.pointerEvents = 'none';

            try {
                const userCredential = await AUTH.login(correo, clave);
                const usuarios = await DB.getUsuarios();
                const usuario = usuarios.find(u => u.correo.toLowerCase() === correo.toLowerCase());

                if (!usuario) {
                    btn.textContent = 'Iniciar Sesión';
                    btn.style.pointerEvents = 'all';
                    showNotification('Usuario autenticado pero sin perfil en la base de datos.', 'error');
                    AUTH.logout();
                    return;
                }

                btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Ingresando...';
                sessionStorage.setItem('sesion_activa', JSON.stringify({
                    nombre: usuario.nombre,
                    correo: usuario.correo,
                    rol:    usuario.rol,
                    area:   usuario.area || '',
                    uid:    userCredential.user.uid
                }));
                showNotification(`¡Bienvenido, ${usuario.nombre.split(' ')[0]}!`);
                irAlDashboard();
            } catch (error) {
                btn.textContent = 'Iniciar Sesión';
                btn.style.pointerEvents = 'all';
                console.error("Login error:", error);
                
                // Tratar el caso donde el usuario existe en DB pero no en Auth (ej: antiguos)
                try {
                    const usuarios = await DB.getUsuarios();
                    const existeAntiguo = usuarios.find(u => u.correo.toLowerCase() === correo.toLowerCase());
                    if (existeAntiguo && error.code === 'auth/invalid-credential') {
                        showNotification('Debido a una actualización de seguridad, necesitas restablecer tu contraseña. Ve a "¿Olvidaste tu contraseña?".', 'error');
                        return;
                    }
                } catch (dbError) {
                    console.warn("No se pudo verificar base de datos por falta de permisos:", dbError);
                }
                
                showNotification('Credenciales incorrectas o usuario no existe.', 'error');
            }
        });
    }

    // ─── REGISTRO ────────────────────────────────────────────
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            let valido = true;

            const nombre   = document.getElementById('reg-nombre')?.value.trim() || '';
            const apellido = document.getElementById('reg-apellido')?.value.trim() || '';
            const correo   = document.getElementById('reg-correo')?.value.trim() || '';
            const area     = document.getElementById('reg-area')?.value || '';
            const subarea  = area === 'T\u00e9cnica' ? (document.getElementById('reg-subarea')?.value || 'Switcher') : '';
            const telefono = document.getElementById('reg-telefono')?.value.trim() || '';
            const pwd      = document.getElementById('reg-password')?.value || '';
            const pwdConf  = document.getElementById('reg-password-confirm')?.value || '';

            if (!nombre)   { mostrarError('reg-nombre',   'Campo requerido'); valido = false; } else limpiarError('reg-nombre');
            if (!apellido) { mostrarError('reg-apellido', 'Campo requerido'); valido = false; } else limpiarError('reg-apellido');
            if (!emailRegex.test(correo)) { mostrarError('reg-correo', 'Correo no válido'); valido = false; } else limpiarError('reg-correo');
            if (!area)     { mostrarError('reg-area',     'Selecciona un área'); valido = false; } else limpiarError('reg-area');
            if (!validarClave(pwd)) { mostrarError('reg-password', 'Mín. 8 chars, incluye letras y números'); valido = false; } else limpiarError('reg-password');
            if (pwd !== pwdConf)    { mostrarError('reg-password-confirm', 'Las contraseñas no coinciden'); valido = false; } else limpiarError('reg-password-confirm');

            if (!valido) return;

            const btn = e.target.querySelector('button[type="submit"]');
            btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Registrando...';
            btn.style.pointerEvents = 'none';

            try {
                // Crear usuario en Firebase Auth (Lanza error si el correo ya existe)
                const userCredential = await AUTH.registrar(correo, pwd);

                // Como ya estamos autenticados, podemos leer/escribir en la base de datos
                const usuarios = await DB.getUsuarios();
                usuarios.push({
                    uid: userCredential.user.uid,
                    nombre: `${nombre} ${apellido}`.trim(),
                    correo,
                    area,
                    subarea,
                    telefono,
                    rol: 'Siervo',
                    fecha: new Date().toISOString()
                });
                await DB.setUsuarios(usuarios);

                showNotification('¡Registro exitoso! Ya puedes iniciar sesión.');
                btn.textContent = 'Completar Registro';
                btn.style.pointerEvents = 'all';
                e.target.reset();
                switchPanel(registerPanel, loginPanel);
                
                // Por seguridad cerramos la sesión generada por el registro automático
                AUTH.logout();
            } catch (error) {
                btn.textContent = 'Completar Registro';
                btn.style.pointerEvents = 'all';
                console.error("Registro error:", error);
                if (error.code === 'auth/email-already-in-use') {
                    showNotification('Ya existe una cuenta con ese correo.', 'error');
                } else {
                    showNotification('Error al registrar: ' + (error.message || 'Intenta de nuevo'), 'error');
                }
            }
        });
    }

    // ─── GOOGLE LOGIN ──────────────────────────────────────────
    const btnGoogleLogin = document.getElementById('btn-google-login');
    const btnGoogleRegister = document.getElementById('btn-google-register');

    async function handleGoogleAuth(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const textOriginal = btn.innerHTML;
        btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Conectando...';
        btn.style.pointerEvents = 'none';

        try {
            const result = await AUTH.loginGoogle();
            const user = result.user;
            let usuarios = await DB.getUsuarios();
            
            // Reparar uid vacío por si acaso
            let huboCambios = false;
            usuarios.forEach(u => {
                if (u.correo.toLowerCase() === user.email.toLowerCase() && !u.uid) {
                    u.uid = user.uid;
                    huboCambios = true;
                }
            });
            if(huboCambios) await DB.setUsuarios(usuarios);

            let usuarioEnDB = usuarios.find(u => u.correo.toLowerCase() === user.email.toLowerCase());

            if (usuarioEnDB) {
                // Usuario existe, login normal
                sessionStorage.setItem('sesion_activa', JSON.stringify(usuarioEnDB));
                irAlDashboard();
            } else {
                // Opción B: Usuario nuevo de Google, pedir área
                if (loginPanel) loginPanel.classList.add('hidden');
                if (registerPanel) registerPanel.classList.add('hidden');
                if (forgotPanel) forgotPanel.classList.add('hidden');
                
                // Creamos el panel de completar área dinámicamente si no existe
                let areaPanel = document.getElementById('google-area-panel');
                if (!areaPanel) {
                    areaPanel = document.createElement('div');
                    areaPanel.id = 'google-area-panel';
                    areaPanel.className = 'glass-panel';
                    areaPanel.innerHTML = `
                        <div class="panel-header">
                            <h1>Casi <span class="highlight">Listo</span></h1>
                            <p>Dinos de qué área eres para terminar tu registro con Google</p>
                        </div>
                        <form id="google-area-form">
                            <div class="input-group">
                                <label>Área a la que perteneces</label>
                                <select id="google-reg-area" required>
                                    <option value="" disabled selected>Elige un área...</option>
                                    <option value="Visuales">📺 Visuales</option>
                                    <option value="Filmakers">🎥 Filmakers</option>
                                    <option value="Fotografía">📸 Fotografía</option>
                                    <option value="Switchers">🎛️ Switchers</option>
                                    <option value="Cámaras">📸 Cámaras</option>
                                    <option value="Streaming">🌐 Streaming</option>
                                    <option value="Luces">💡 Luces</option>
                                    <option value="Diseño">🎨 Diseño</option>
                                    <option value="Edición">✂️ Edición</option>
                                    <option value="Coordinación">📋 Coordinación</option>
                                    <option value="Protocolos">🤝 Protocolos</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary" style="margin-top:20px;">Finalizar Registro</button>
                        </form>
                    `;
                    document.querySelector('.container').appendChild(areaPanel);

                    document.getElementById('google-area-form').addEventListener('submit', async (ev) => {
                        ev.preventDefault();
                        const area = document.getElementById('google-reg-area').value;
                        if (!area) return;
                        
                        const btnSub = ev.target.querySelector('button');
                        btnSub.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Guardando...';
                        btnSub.style.pointerEvents = 'none';

                        const nuevoUsuario = {
                            uid: user.uid,
                            nombre: user.displayName || 'Usuario de Google',
                            correo: user.email,
                            area: area,
                            telefono: '',
                            rol: 'Siervo',
                            fecha: new Date().toISOString(),
                            fotoUrl: user.photoURL || null
                        };
                        usuarios.push(nuevoUsuario);
                        await DB.setUsuarios(usuarios);
                        
                        sessionStorage.setItem('sesion_activa', JSON.stringify(nuevoUsuario));
                        irAlDashboard();
                    });
                }
                
                // Mostrar el panel
                areaPanel.classList.remove('hidden');
                areaPanel.style.opacity = '1';
                areaPanel.style.transform = 'translateY(0) scale(1)';
            }
        } catch (error) {
            btn.innerHTML = textOriginal;
            btn.style.pointerEvents = 'all';
            console.error("Google Auth Error:", error);
            showNotification('Error con Google: ' + (error.message || 'Cerraste la ventana'), 'error');
        }
    }

    if (btnGoogleLogin) btnGoogleLogin.addEventListener('click', handleGoogleAuth);
    if (btnGoogleRegister) btnGoogleRegister.addEventListener('click', handleGoogleAuth);

    // ─── RECUPERACIÓN DE CONTRASEÑA ──────────────────────────
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const correo = document.getElementById('forgot-email').value.trim();
            const btn    = e.target.querySelector('button[type="submit"]');

            if (!emailRegex.test(correo)) {
                showNotification('Ingresa un correo válido.', 'error');
                return;
            }

            btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">↻</span> Enviando...';
            btn.style.pointerEvents = 'none';

            try {
                await AUTH.recuperar(correo);
                
                // Mostrar confirmación
                const resultDiv = document.getElementById('forgot-result');
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `
                    <p style="font-size:0.95rem;color:var(--accent-green);text-align:center;margin-bottom:8px;">
                        ✓ Enlace de recuperación enviado.
                    </p>
                    <p style="font-size:0.85rem;color:var(--text-muted);text-align:center;">
                        Por favor, revisa tu bandeja de entrada o la carpeta de SPAM para restablecer tu contraseña.
                    </p>
                `;
                
                btn.textContent = 'Generar Enlace Temporal';
                btn.style.pointerEvents = 'all';
                showNotification('Enlace de recuperación enviado.');
            } catch (error) {
                btn.textContent = 'Generar Enlace Temporal';
                btn.style.pointerEvents = 'all';
                console.error("Recovery error:", error);
                if (error.code === 'auth/user-not-found') {
                    showNotification('No encontramos una cuenta con ese correo.', 'error');
                } else {
                    showNotification('No se pudo enviar el correo de recuperación.', 'error');
                }
            }
        });
    }
});
