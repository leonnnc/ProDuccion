<?php
/**
 * Utilidad para manejo de sesiones
 * Proporciona una interfaz segura para el manejo de sesiones PHP
 */

class Session {
    private $started = false;
    private $config;
    
    public function __construct() {
        $this->config = include __DIR__ . '/../config/config.php';
        $this->configureSession();
    }
    
    /**
     * Configurar parámetros de sesión
     */
    private function configureSession() {
        // Configurar nombre de sesión
        $sessionName = $this->config['security']['session_name'] ?? 'SGP_SESSION';
        session_name($sessionName);
        
        // Configurar parámetros de cookie
        $cookieParams = [
            'lifetime' => $this->config['security']['session_lifetime'] ?? 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        session_set_cookie_params($cookieParams);
        
        // Configurar directorio de sesiones si es necesario
        $sessionPath = __DIR__ . '/../sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }
        
        // Configurar garbage collection
        ini_set('session.gc_maxlifetime', $cookieParams['lifetime']);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        
        // Configurar seguridad adicional
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        
        if ($cookieParams['secure']) {
            ini_set('session.cookie_secure', 1);
        }
    }
    
    /**
     * Iniciar sesión
     */
    public function start() {
        if ($this->started) {
            return true;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            if (!session_start()) {
                throw new Exception('No se pudo iniciar la sesión');
            }
        }
        
        $this->started = true;
        
        // Verificar y regenerar ID de sesión periódicamente
        $this->checkSessionSecurity();
        
        return true;
    }
    
    /**
     * Verificar seguridad de la sesión
     */
    private function checkSessionSecurity() {
        // Verificar IP del usuario (opcional, puede causar problemas con proxies)
        if (isset($_SESSION['_ip']) && $_SESSION['_ip'] !== $this->getClientIP()) {
            $this->destroy();
            throw new Exception('Sesión inválida por cambio de IP');
        }
        
        // Establecer IP en la primera visita
        if (!isset($_SESSION['_ip'])) {
            $_SESSION['_ip'] = $this->getClientIP();
        }
        
        // Verificar User-Agent
        if (isset($_SESSION['_user_agent']) && $_SESSION['_user_agent'] !== $this->getUserAgent()) {
            $this->destroy();
            throw new Exception('Sesión inválida por cambio de User-Agent');
        }
        
        // Establecer User-Agent en la primera visita
        if (!isset($_SESSION['_user_agent'])) {
            $_SESSION['_user_agent'] = $this->getUserAgent();
        }
        
        // Regenerar ID de sesión cada 30 minutos
        $regenerateInterval = 1800; // 30 minutos
        if (!isset($_SESSION['_last_regenerate']) || 
            (time() - $_SESSION['_last_regenerate']) > $regenerateInterval) {
            $this->regenerateId();
        }
    }
    
    /**
     * Obtener valor de sesión
     */
    public function get($key, $default = null) {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Establecer valor de sesión
     */
    public function set($key, $value) {
        $this->start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Verificar si existe una clave en la sesión
     */
    public function has($key) {
        $this->start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Eliminar valor de sesión
     */
    public function remove($key) {
        $this->start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Limpiar toda la sesión
     */
    public function clear() {
        $this->start();
        $_SESSION = [];
    }
    
    /**
     * Destruir sesión completamente
     */
    public function destroy() {
        $this->start();
        
        // Limpiar variables de sesión
        $_SESSION = [];
        
        // Eliminar cookie de sesión
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destruir sesión
        session_destroy();
        $this->started = false;
    }
    
    /**
     * Regenerar ID de sesión
     */
    public function regenerateId($deleteOldSession = true) {
        $this->start();
        
        if (!session_regenerate_id($deleteOldSession)) {
            throw new Exception('No se pudo regenerar el ID de sesión');
        }
        
        $_SESSION['_last_regenerate'] = time();
    }
    
    /**
     * Obtener ID de sesión actual
     */
    public function getId() {
        $this->start();
        return session_id();
    }
    
    /**
     * Obtener todos los datos de sesión
     */
    public function all() {
        $this->start();
        return $_SESSION;
    }
    
    /**
     * Verificar si la sesión está activa
     */
    public function isActive() {
        return $this->started && session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Obtener tiempo de vida restante de la sesión
     */
    public function getTimeRemaining() {
        if (!$this->isActive()) {
            return 0;
        }
        
        $loginTime = $this->get('login_time', time());
        $sessionLifetime = $this->config['security']['session_lifetime'] ?? 3600;
        
        return max(0, $sessionLifetime - (time() - $loginTime));
    }
    
    /**
     * Extender tiempo de vida de la sesión
     */
    public function extend() {
        $this->set('login_time', time());
    }
    
    /**
     * Obtener información de la sesión
     */
    public function getInfo() {
        return [
            'id' => $this->getId(),
            'status' => session_status(),
            'name' => session_name(),
            'cookie_params' => session_get_cookie_params(),
            'save_path' => session_save_path(),
            'cache_limiter' => session_cache_limiter(),
            'cache_expire' => session_cache_expire(),
            'module_name' => session_module_name()
        ];
    }
    
    /**
     * Implementar flash messages
     */
    public function flash($key, $message = null) {
        if ($message === null) {
            // Obtener y eliminar mensaje flash
            $message = $this->get("_flash_{$key}");
            $this->remove("_flash_{$key}");
            return $message;
        } else {
            // Establecer mensaje flash
            $this->set("_flash_{$key}", $message);
        }
    }
    
    /**
     * Obtener IP del cliente
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Obtener User-Agent del cliente
     */
    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Generar token CSRF
     */
    public function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $this->set('csrf_token', $token);
        return $token;
    }
    
    /**
     * Verificar token CSRF
     */
    public function verifyCSRFToken($token) {
        $sessionToken = $this->get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Limpiar sesiones expiradas (para uso en cron jobs)
     */
    public static function cleanExpiredSessions() {
        $sessionPath = session_save_path();
        if (empty($sessionPath)) {
            $sessionPath = sys_get_temp_dir();
        }
        
        $files = glob($sessionPath . '/sess_*');
        $now = time();
        $sessionLifetime = 3600; // 1 hora por defecto
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $sessionLifetime) {
                unlink($file);
            }
        }
    }
}
?>