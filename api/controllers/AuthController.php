<?php
/**
 * Controlador de Autenticación
 * Maneja login, logout y verificación de usuarios
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Session.php';

class AuthController {
    private $usuarioModel;
    private $session;
    
    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->session = new Session();
    }
    
    /**
     * Iniciar sesión
     */
    public function login() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos inválidos');
                return;
            }
            
            // Validar campos requeridos
            if (empty($input['email']) || empty($input['password'])) {
                Response::error('Email y contraseña son requeridos');
                return;
            }
            
            // Intentar autenticar
            $usuario = $this->usuarioModel->autenticar($input['email'], $input['password']);
            
            if (!$usuario) {
                Response::error('Credenciales inválidas');
                return;
            }
            
            // Crear sesión
            $this->session->start();
            $this->session->set('user_id', $usuario['id']);
            $this->session->set('user_email', $usuario['email']);
            $this->session->set('user_area', $usuario['area_id']);
            $this->session->set('login_time', time());
            
            // Regenerar ID de sesión por seguridad
            $this->session->regenerateId();
            
            // Respuesta exitosa
            Response::success([
                'message' => 'Inicio de sesión exitoso',
                'user' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'area_id' => $usuario['area_id'],
                    'area_nombre' => $usuario['area_nombre'],
                    'permisos' => $usuario['area_permisos']
                ]
            ]);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Destruir sesión
            $this->session->start();
            $this->session->destroy();
            
            Response::success(['message' => 'Sesión cerrada correctamente']);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener usuario actual
     */
    public function getUser() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Verificar autenticación
            if (!$this->isAuthenticated()) {
                Response::error('No autenticado', 401);
                return;
            }
            
            $userId = $this->session->get('user_id');
            $usuario = $this->usuarioModel->obtenerPorId($userId);
            
            if (!$usuario) {
                Response::error('Usuario no encontrado', 404);
                return;
            }
            
            Response::success([
                'user' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'area_id' => $usuario['area_id'],
                    'area_nombre' => $usuario['area_nombre'],
                    'permisos' => $usuario['area_permisos']
                ]
            ]);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated() {
        $this->session->start();
        
        $userId = $this->session->get('user_id');
        $loginTime = $this->session->get('login_time');
        
        // Verificar si hay sesión activa
        if (!$userId || !$loginTime) {
            return false;
        }
        
        // Verificar timeout de sesión (1 hora por defecto)
        $sessionTimeout = 3600; // 1 hora
        if (time() - $loginTime > $sessionTimeout) {
            $this->session->destroy();
            return false;
        }
        
        // Actualizar tiempo de actividad
        $this->session->set('login_time', time());
        
        return true;
    }
    
    /**
     * Middleware de autenticación
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            Response::error('Acceso no autorizado', 401);
            exit;
        }
    }
    
    /**
     * Middleware de permisos
     */
    public function requirePermission($modulo, $accion) {
        $this->requireAuth();
        
        $userId = $this->session->get('user_id');
        
        if (!$this->usuarioModel->tienePermiso($userId, $modulo, $accion)) {
            Response::error('Permisos insuficientes', 403);
            exit;
        }
    }
    
    /**
     * Registrar nuevo usuario
     */
    public function register() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Debug: Log de datos recibidos
            error_log('Datos de registro recibidos: ' . json_encode($input));
            
            if (!$input) {
                Response::error('Datos inválidos');
                return;
            }
            
            // Validar campos requeridos
            $errors = Response::validateInput($input, [
                'nombre' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
                'email' => ['required' => true, 'type' => 'email'],
                'password' => ['required' => true, 'min_length' => 6],
                'grupo_id' => ['required' => true, 'type' => 'integer'],
                'area_id' => ['required' => true, 'type' => 'integer']
            ]);
            
            if (!empty($errors)) {
                Response::validation($errors);
                return;
            }
            
            // Validar acceso de administrador si es necesario
            if (isset($input['grupo_id']) && $input['grupo_id'] == 1) {
                $this->validarAccesoAdmin($input);
            }
            
            // Crear usuario
            $usuarioId = $this->usuarioModel->crear($input);
            
            // Obtener usuario creado
            $usuario = $this->usuarioModel->obtenerPorId($usuarioId);
            
            // Enviar email de confirmación si se solicita
            if (isset($input['send_email']) && $input['send_email']) {
                $this->sendWelcomeEmail($usuario);
            }
            
            // Crear sesión automáticamente
            $this->session->start();
            $this->session->set('user_id', $usuario['id']);
            $this->session->set('user_email', $usuario['email']);
            $this->session->set('user_area', $usuario['area_id']);
            $this->session->set('login_time', time());
            
            // Regenerar ID de sesión por seguridad
            $this->session->regenerateId();
            
            Response::success([
                'message' => 'Usuario registrado exitosamente',
                'user' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'area_id' => $usuario['area_id'],
                    'area_nombre' => $usuario['area_nombre'],
                    'permisos' => $usuario['area_permisos']
                ]
            ], 'Registro exitoso', 201);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener grupos y áreas disponibles para registro
     */
    public function getGruposYAreas() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener grupos
            $sql = "SELECT id, nombre, descripcion FROM grupos WHERE activo = 1 ORDER BY nombre";
            $grupos = Database::getInstance()->fetchAll($sql);
            
            // Obtener áreas
            $sql = "SELECT id, nombre, descripcion FROM areas WHERE activa = 1 ORDER BY nombre";
            $areas = Database::getInstance()->fetchAll($sql);
            
            Response::success([
                'grupos' => $grupos,
                'areas' => $areas
            ], 'Grupos y áreas obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener áreas disponibles para registro (mantener compatibilidad)
     */
    public function getAreas() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Obtener áreas
            $sql = "SELECT id, nombre, descripcion FROM areas WHERE activa = 1 ORDER BY nombre";
            $areas = Database::getInstance()->fetchAll($sql);
            
            Response::success($areas, 'Áreas obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            // Verificar autenticación
            $this->requireAuth();
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Datos inválidos');
                return;
            }
            
            // Validar campos requeridos
            if (empty($input['current_password']) || empty($input['new_password'])) {
                Response::error('Contraseña actual y nueva contraseña son requeridas');
                return;
            }
            
            $userId = $this->session->get('user_id');
            
            // Cambiar contraseña
            $this->usuarioModel->cambiarPassword(
                $userId,
                $input['current_password'],
                $input['new_password']
            );
            
            Response::success(['message' => 'Contraseña cambiada correctamente']);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Verificar estado de la sesión
     */
    public function checkSession() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            $this->session->start();
            
            $isAuthenticated = $this->isAuthenticated();
            $sessionData = [];
            
            if ($isAuthenticated) {
                $sessionData = [
                    'user_id' => $this->session->get('user_id'),
                    'user_email' => $this->session->get('user_email'),
                    'login_time' => $this->session->get('login_time'),
                    'session_id' => session_id()
                ];
            }
            
            Response::success([
                'authenticated' => $isAuthenticated,
                'session' => $sessionData
            ]);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener información del usuario actual
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userId = $this->session->get('user_id');
        return $this->usuarioModel->obtenerPorId($userId);
    }
    
    /**
     * Verificar si el usuario actual tiene un permiso específico
     */
    public function hasPermission($modulo, $accion) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $userId = $this->session->get('user_id');
        return $this->usuarioModel->tienePermiso($userId, $modulo, $accion);
    }
    
    /**
     * Extender sesión
     */
    public function extendSession() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::error('Método no permitido', 405);
                return;
            }
            
            if (!$this->isAuthenticated()) {
                Response::error('No autenticado', 401);
                return;
            }
            
            // Actualizar tiempo de login
            $this->session->set('login_time', time());
            
            Response::success(['message' => 'Sesión extendida correctamente']);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Enviar email de bienvenida al usuario registrado
     */
    private function sendWelcomeEmail($usuario) {
        try {
            // Configurar datos del email
            $to = $usuario['email'];
            $subject = 'Bienvenido al Sistema de Gestión de Producción';
            
            // Crear contenido HTML del email
            $htmlContent = $this->createWelcomeEmailTemplate($usuario);
            
            // Headers del email
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: Sistema de Gestión de Producción <noreply@gestionproduccion.com>',
                'Reply-To: soporte@gestionproduccion.com',
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Enviar email
            $sent = mail($to, $subject, $htmlContent, implode("\r\n", $headers));
            
            if ($sent) {
                error_log("Email de bienvenida enviado a: " . $to);
            } else {
                error_log("Error enviando email de bienvenida a: " . $to);
            }
            
        } catch (Exception $e) {
            error_log("Error en sendWelcomeEmail: " . $e->getMessage());
        }
    }
    
    /**
     * Crear template HTML para email de bienvenida
     */
    private function createWelcomeEmailTemplate($usuario) {
        $fechaRegistro = date('d/m/Y H:i');
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenido al Sistema de Gestión de Producción</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3498db; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .btn { display: inline-block; background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Bienvenido al Sistema de Gestión de Producción!</h1>
                    <p>Tu cuenta ha sido creada exitosamente</p>
                </div>
                
                <div class='content'>
                    <h2>Hola {$usuario['nombre']},</h2>
                    
                    <p>¡Felicidades! Tu cuenta en el Sistema de Gestión de Producción ha sido creada exitosamente.</p>
                    
                    <div class='info-box'>
                        <h3>Detalles de tu cuenta:</h3>
                        <p><strong>Nombre:</strong> {$usuario['nombre']}</p>
                        <p><strong>Email:</strong> {$usuario['email']}</p>
                        <p><strong>Área:</strong> {$usuario['area_nombre']}</p>
                        <p><strong>Fecha de registro:</strong> {$fechaRegistro}</p>
                    </div>
                    
                    <h3>¿Qué puedes hacer en tu área?</h3>
                    " . $this->getAreaSpecificFeatures($usuario['area_nombre']) . "
                    
                    <h3>Funcionalidades generales del sistema:</h3>
                    <ul>
                        <li>✅ Gestionar proyectos de producción audiovisual</li>
                        <li>✅ Asignar y seguir tareas del equipo</li>
                        <li>✅ Programar y consultar la agenda mensual</li>
                        <li>✅ Utilizar el asistente virtual integrado</li>
                        <li>✅ Generar reportes y estadísticas</li>
                        <li>✅ Colaborar con otras áreas de producción</li>
                    </ul>
                    
                    <p>Tu cuenta está activa y lista para usar. Puedes iniciar sesión en cualquier momento con tu email y contraseña.</p>
                    
                    <div style='text-align: center;'>
                        <a href='#' class='btn'>Acceder al Sistema</a>
                    </div>
                    
                    <div class='info-box'>
                        <h4>💡 Consejos para empezar:</h4>
                        <p>1. Explora el dashboard para familiarizarte con las funciones</p>
                        <p>2. Utiliza el chatbot de ayuda si tienes dudas</p>
                        <p>3. Configura tu perfil y preferencias</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Este email fue enviado automáticamente. No respondas a este mensaje.</p>
                    <p>Si tienes preguntas, contacta al administrador del sistema.</p>
                    <p>&copy; " . date('Y') . " Sistema de Gestión de Producción</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Obtener características específicas según el área del usuario
     */
    private function getAreaSpecificFeatures($areaNombre) {
        $features = [
            'Admin' => '<ul>
                <li>🔧 Administrar usuarios y permisos del sistema</li>
                <li>📊 Acceso completo a todos los módulos</li>
                <li>⚙️ Configurar parámetros del sistema</li>
                <li>📈 Generar reportes avanzados</li>
            </ul>',
            
            'Staff' => '<ul>
                <li>👥 Gestionar equipos y asignar tareas</li>
                <li>📋 Crear y supervisar proyectos</li>
                <li>📅 Coordinar la agenda de producción</li>
                <li>📊 Generar reportes de seguimiento</li>
            </ul>',
            
            'Filmmakers' => '<ul>
                <li>🎬 Crear y dirigir proyectos audiovisuales</li>
                <li>📝 Asignar tareas a equipos de producción</li>
                <li>🎯 Supervisar el progreso de filmaciones</li>
                <li>📊 Generar reportes de producción</li>
            </ul>',
            
            'Streaming' => '<ul>
                <li>📡 Gestionar transmisiones en vivo</li>
                <li>⚡ Coordinar eventos de streaming</li>
                <li>📅 Programar horarios de transmisión</li>
                <li>🔧 Supervisar tareas técnicas de streaming</li>
            </ul>',
            
            'Switchers' => '<ul>
                <li>🎛️ Operar equipos de mezcla de video</li>
                <li>📺 Gestionar señales de video en vivo</li>
                <li>🎯 Coordinar cambios de cámara</li>
            </ul>',
            
            'Luces' => '<ul>
                <li>💡 Configurar iluminación de sets</li>
                <li>🎨 Crear ambientaciones visuales</li>
                <li>⚡ Gestionar equipos de iluminación</li>
            </ul>',
            
            'Cámaras' => '<ul>
                <li>📹 Operar equipos de grabación</li>
                <li>🎬 Capturar contenido audiovisual</li>
                <li>📐 Configurar encuadres y movimientos</li>
            </ul>',
            
            'Protocolos' => '<ul>
                <li>🎭 Organizar eventos ceremoniales</li>
                <li>📋 Gestionar protocolos oficiales</li>
                <li>👔 Coordinar aspectos formales</li>
            </ul>'
            
            'Coordinación' => '<ul>
                <li>📋 Coordinar logística de producción</li>
                <li>👥 Gestionar recursos humanos</li>
                <li>📅 Organizar cronogramas de trabajo</li>
                <li>📊 Supervisar el progreso general</li>
            </ul>',
            
            'Visuales' => '<ul>
                <li>🎨 Crear efectos visuales y gráficos</li>
                <li>💻 Gestionar proyectos de postproducción</li>
                <li>🎯 Coordinar entregas de material visual</li>
            </ul>',
            
            'Fotografía' => '<ul>
                <li>📸 Gestionar sesiones fotográficas</li>
                <li>🎯 Coordinar captura de imágenes</li>
                <li>📅 Programar horarios de fotografía</li>
            </ul>',
            
            'Diseño' => '<ul>
                <li>🎨 Crear diseños gráficos y creativos</li>
                <li>💡 Desarrollar conceptos visuales</li>
                <li>📋 Gestionar proyectos de diseño</li>
            </ul>',
            
            'Edición' => '<ul>
                <li>✂️ Editar contenido audiovisual</li>
                <li>🎬 Gestionar proyectos de postproducción</li>
                <li>📅 Coordinar entregas de material editado</li>
            </ul>'
        ];
        
        return $features[$areaNombre] ?? '<ul><li>📋 Gestionar tareas asignadas a tu área</li><li>📅 Consultar agenda y cronogramas</li><li>👥 Colaborar con otros equipos</li></ul>';
    }

    /**
     * Validar acceso de administrador
     */
    private function validarAccesoAdmin($input) {
        // Solo validar si el grupo es Admin (ID 1)
        if (!isset($input['grupo_id']) || $input['grupo_id'] != 1) {
            return; // No es admin, no necesita validación especial
        }
        
        // Verificar código de invitación para administradores
        $codigosValidos = ['ADMIN2024', 'PROD-MASTER', 'DIRECTOR-KEY', 'SUPERVISOR-ACCESS'];
        
        if (!isset($input['admin_code']) || empty($input['admin_code'])) {
            throw new Exception('Código de invitación requerido para administradores');
        }
        
        if (!in_array(strtoupper($input['admin_code']), $codigosValidos)) {
            throw new Exception('Código de invitación inválido');
        }
        
        // Verificar emails autorizados (opcional)
        $emailsAutorizados = [
            'admin@gestionproduccion.com',
            'director@gestionproduccion.com',
            'supervisor@gestionproduccion.com'
        ];
        
        // Si hay emails autorizados configurados, verificar
        if (!empty($emailsAutorizados) && !in_array(strtolower($input['email']), $emailsAutorizados)) {
            // Permitir cualquier email si no hay restricciones específicas
            // throw new Exception('Email no autorizado para registro de administrador');
        }
    }

    /**
     * Método de prueba para verificar áreas
     */
    public function testAreas() {
        try {
            require_once __DIR__ . '/../models/Area.php';
            $areaModel = new Area();
            
            // Obtener todas las áreas
            $areas = $areaModel->obtenerTodas();
            
            Response::success([
                'total_areas' => count($areas),
                'areas' => $areas,
                'database_info' => Database::getInstance()->getInfo()
            ], 'Test de áreas completado');
            
        } catch (Exception $e) {
            Response::error('Error en test: ' . $e->getMessage());
        }
    }

    /**
     * Probar conexión a la base de datos
     */
    public function testConnection() {
        try {
            $db = Database::getInstance();
            $info = $db->getInfo();
            
            Response::success([
                'connection' => 'OK',
                'database_info' => $info,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'Conexión a base de datos exitosa');
            
        } catch (Exception $e) {
            Response::error('Error de conexión: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificar tablas de la base de datos
     */
    public function testTables() {
        try {
            $db = Database::getInstance();
            
            // Verificar tablas principales
            $tablas = ['usuarios', 'grupos', 'areas', 'proyectos', 'tareas', 'agenda_disponibilidad'];
            $resultados = [];
            
            foreach ($tablas as $tabla) {
                try {
                    $sql = "SELECT COUNT(*) as total FROM {$tabla}";
                    $resultado = $db->fetch($sql);
                    $resultados[$tabla] = [
                        'existe' => true,
                        'registros' => $resultado['total']
                    ];
                } catch (Exception $e) {
                    $resultados[$tabla] = [
                        'existe' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            Response::success([
                'tablas' => $resultados,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'Verificación de tablas completada');
            
        } catch (Exception $e) {
            Response::error('Error verificando tablas: ' . $e->getMessage());
        }
    }

    /**
     * Obtener información de la sesión para debugging
     */
    public function getSessionInfo() {
        try {
            // Solo en modo debug
            $config = include __DIR__ . '/../config/config.php';
            if (!$config['app']['debug']) {
                Response::error('No disponible en producción', 403);
                return;
            }
            
            $this->session->start();
            
            Response::success([
                'session_id' => session_id(),
                'session_data' => $_SESSION ?? [],
                'session_status' => session_status(),
                'cookie_params' => session_get_cookie_params()
            ]);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
?>