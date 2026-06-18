<?php
/**
 * API Principal - Sistema de Gestión de Producción
 * Punto de entrada para todas las peticiones de la API
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // Cambiar a 1 solo en desarrollo

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload de clases
spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/controllers/',
        __DIR__ . '/models/',
        __DIR__ . '/utils/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Incluir archivos necesarios
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/Response.php';

// Clase principal del router
class APIRouter {
    private $routes = [];
    
    public function __construct() {
        $this->setupRoutes();
    }
    
    /**
     * Configurar todas las rutas de la API
     */
    private function setupRoutes() {
        // Ruta de prueba
        $this->routes['GET']['/test'] = function() {
            Response::success([
                'status' => 'ok',
                'message' => 'API Test exitoso',
                'timestamp' => date('Y-m-d H:i:s'),
                'server' => $_SERVER['SERVER_NAME'] ?? 'localhost'
            ]);
        };
        
        // Rutas de autenticación
        $this->routes['POST']['/auth/login'] = ['AuthController', 'login'];
        $this->routes['POST']['/auth/register'] = ['AuthController', 'register'];
        $this->routes['GET']['/auth/areas'] = ['AuthController', 'getAreas'];
        $this->routes['GET']['/auth/grupos-areas'] = ['AuthController', 'getGruposYAreas'];
        $this->routes['POST']['/auth/logout'] = ['AuthController', 'logout'];
        $this->routes['GET']['/auth/user'] = ['AuthController', 'getUser'];
        $this->routes['POST']['/auth/change-password'] = ['AuthController', 'changePassword'];
        $this->routes['GET']['/auth/check-session'] = ['AuthController', 'checkSession'];
        $this->routes['POST']['/auth/extend-session'] = ['AuthController', 'extendSession'];
        
        // Rutas de proyectos
        $this->routes['GET']['/projects'] = ['ProjectController', 'index'];
        $this->routes['POST']['/projects'] = ['ProjectController', 'store'];
        $this->routes['GET']['/projects/search'] = ['ProjectController', 'search'];
        $this->routes['GET']['/projects/summary'] = ['ProjectController', 'getSummary'];
        $this->routes['GET']['/projects/my-projects'] = ['ProjectController', 'getMyProjects'];
        $this->routes['GET']['/projects/{id}'] = ['ProjectController', 'show'];
        $this->routes['PUT']['/projects/{id}'] = ['ProjectController', 'update'];
        $this->routes['DELETE']['/projects/{id}'] = ['ProjectController', 'destroy'];
        $this->routes['PATCH']['/projects/{id}/status'] = ['ProjectController', 'changeStatus'];
        $this->routes['GET']['/projects/{id}/tasks'] = ['ProjectController', 'getTasks'];
        $this->routes['GET']['/projects/{id}/statistics'] = ['ProjectController', 'getStatistics'];
        $this->routes['POST']['/projects/{id}/duplicate'] = ['ProjectController', 'duplicate'];
        
        // Rutas de tareas
        $this->routes['GET']['/tasks'] = ['TaskController', 'index'];
        $this->routes['POST']['/tasks'] = ['TaskController', 'store'];
        $this->routes['GET']['/tasks/my-tasks'] = ['TaskController', 'myTasks'];
        $this->routes['GET']['/tasks/dashboard'] = ['TaskController', 'dashboard'];
        $this->routes['GET']['/tasks/search'] = ['TaskController', 'search'];
        $this->routes['GET']['/tasks/statistics'] = ['TaskController', 'statistics'];
        $this->routes['GET']['/tasks/user-statistics'] = ['TaskController', 'userStatistics'];
        $this->routes['GET']['/tasks/overdue'] = ['TaskController', 'overdue'];
        $this->routes['GET']['/tasks/upcoming'] = ['TaskController', 'upcoming'];
        $this->routes['GET']['/tasks/user/{userId}'] = ['TaskController', 'byUser'];
        $this->routes['GET']['/tasks/project/{projectId}'] = ['TaskController', 'byProject'];
        $this->routes['GET']['/tasks/{id}'] = ['TaskController', 'show'];
        $this->routes['PUT']['/tasks/{id}'] = ['TaskController', 'update'];
        $this->routes['DELETE']['/tasks/{id}'] = ['TaskController', 'delete'];
        $this->routes['PATCH']['/tasks/{id}/status'] = ['TaskController', 'changeStatus'];
        $this->routes['POST']['/tasks/{id}/duplicate'] = ['TaskController', 'duplicate'];
        
        // Rutas de calendario/agenda
        $this->routes['GET']['/calendar/{year}/{month}'] = ['CalendarController', 'getMonthly'];
        $this->routes['GET']['/calendar/current'] = ['CalendarController', 'getCurrentMonth'];
        $this->routes['GET']['/calendar/range'] = ['CalendarController', 'getByRange'];
        $this->routes['GET']['/calendar/upcoming'] = ['CalendarController', 'getUpcoming'];
        $this->routes['GET']['/calendar/segments'] = ['CalendarController', 'getSegments'];
        $this->routes['GET']['/calendar/statistics'] = ['CalendarController', 'getStatistics'];
        $this->routes['GET']['/calendar/weekly-summary'] = ['CalendarController', 'getWeeklySummary'];
        $this->routes['GET']['/calendar/search'] = ['CalendarController', 'search'];
        $this->routes['GET']['/calendar/available/{segmento}'] = ['CalendarController', 'getAvailableDays'];
        $this->routes['GET']['/calendar/{fecha}/{segmento}'] = ['CalendarController', 'getByDateSegment'];
        $this->routes['POST']['/calendar'] = ['CalendarController', 'store'];
        $this->routes['POST']['/calendar/multiple'] = ['CalendarController', 'setMultiple'];
        $this->routes['POST']['/calendar/clone-month'] = ['CalendarController', 'cloneMonth'];
        $this->routes['PUT']['/calendar/{id}'] = ['CalendarController', 'update'];
        $this->routes['DELETE']['/calendar/{id}'] = ['CalendarController', 'delete'];
        $this->routes['DELETE']['/calendar/cleanup'] = ['CalendarController', 'cleanup'];
        
        // Rutas de chatbot
        $this->routes['POST']['/chatbot/message'] = ['ChatbotController', 'processMessage'];
        $this->routes['GET']['/chatbot/suggestions'] = ['ChatbotController', 'getSuggestions'];
        $this->routes['GET']['/chatbot/help/{modulo}'] = ['ChatbotController', 'getModuleHelp'];
        $this->routes['POST']['/chatbot/feedback'] = ['ChatbotController', 'submitFeedback'];
        $this->routes['GET']['/chatbot/history'] = ['ChatbotController', 'getHistory'];
        $this->routes['GET']['/chatbot/statistics'] = ['ChatbotController', 'getStatistics'];
        
        // Rutas de usuarios
        $this->routes['GET']['/users'] = ['UserController', 'index'];
        $this->routes['POST']['/users'] = ['UserController', 'store'];
        $this->routes['GET']['/users/{id}'] = ['UserController', 'show'];
        $this->routes['PUT']['/users/{id}'] = ['UserController', 'update'];
        $this->routes['DELETE']['/users/{id}'] = ['UserController', 'destroy'];
        
        // Rutas de áreas
        $this->routes['GET']['/areas'] = ['AreaController', 'index'];
        $this->routes['POST']['/areas'] = ['AreaController', 'store'];
        $this->routes['GET']['/areas/{id}'] = ['AreaController', 'show'];
        $this->routes['PUT']['/areas/{id}'] = ['AreaController', 'update'];
        $this->routes['DELETE']['/areas/{id}'] = ['AreaController', 'destroy'];
        
        // Rutas de prueba y debug
        $this->routes['GET']['/test/areas'] = ['AuthController', 'testAreas'];
        $this->routes['GET']['/test/connection'] = ['AuthController', 'testConnection'];
        $this->routes['GET']['/test/tables'] = ['AuthController', 'testTables'];
        
        // Rutas de dashboard/estadísticas
        $this->routes['GET']['/dashboard/stats'] = ['DashboardController', 'getStats'];
        $this->routes['GET']['/dashboard/recent-activity'] = ['DashboardController', 'getRecentActivity'];
    }
    
    /**
     * Procesar la petición y ejecutar la ruta correspondiente
     */
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = $this->getPath();
            
            // Buscar ruta exacta
            if (isset($this->routes[$method][$path])) {
                $this->executeRoute($this->routes[$method][$path]);
                return;
            }
            
            // Buscar ruta con parámetros
            foreach ($this->routes[$method] ?? [] as $route => $handler) {
                $params = $this->matchRoute($route, $path);
                if ($params !== false) {
                    $this->executeRoute($handler, $params);
                    return;
                }
            }
            
            // Ruta no encontrada
            Response::notFound('Endpoint no encontrado');
            
        } catch (Exception $e) {
            error_log('Error en API: ' . $e->getMessage());
            Response::error('Error interno del servidor', 500);
        }
    }
    
    /**
     * Obtener la ruta de la petición
     */
    private function getPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remover /api del inicio si existe
        if (strpos($path, '/api') === 0) {
            $path = substr($path, 4);
        }
        
        // Asegurar que empiece con /
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        
        return $path;
    }
    
    /**
     * Verificar si una ruta coincide con el patrón y extraer parámetros
     */
    private function matchRoute($pattern, $path) {
        // Convertir patrón a regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $path, $matches)) {
            array_shift($matches); // Remover la coincidencia completa
            
            // Extraer nombres de parámetros del patrón
            preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
            $paramNames = $paramNames[1];
            
            // Combinar nombres con valores
            $params = [];
            for ($i = 0; $i < count($paramNames); $i++) {
                $params[$paramNames[$i]] = $matches[$i] ?? null;
            }
            
            return $params;
        }
        
        return false;
    }
    
    /**
     * Ejecutar el controlador y método correspondiente
     */
    private function executeRoute($handler, $params = []) {
        // Si es una función anónima, ejecutarla directamente
        if (is_callable($handler)) {
            if (!empty($params)) {
                call_user_func_array($handler, array_values($params));
            } else {
                $handler();
            }
            return;
        }
        
        // Si es un array, procesar como controlador y método
        if (is_array($handler) && count($handler) === 2) {
            list($controllerName, $method) = $handler;
            
            // Verificar que la clase del controlador existe
            if (!class_exists($controllerName)) {
                throw new Exception("Controlador {$controllerName} no encontrado");
            }
            
            $controller = new $controllerName();
            
            // Verificar que el método existe
            if (!method_exists($controller, $method)) {
                throw new Exception("Método {$method} no encontrado en {$controllerName}");
            }
            
            // Ejecutar método con parámetros
            if (!empty($params)) {
                call_user_func_array([$controller, $method], array_values($params));
            } else {
                $controller->$method();
            }
            return;
        }
        
        throw new Exception("Handler inválido");
    }
}

// Inicializar y ejecutar router
try {
    $router = new APIRouter();
    $router->handleRequest();
} catch (Exception $e) {
    error_log('Error fatal en API: ' . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}
?>