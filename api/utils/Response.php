<?php
/**
 * Utilidad para manejo de respuestas HTTP
 * Estandariza las respuestas de la API
 */

class Response {
    
    /**
     * Enviar respuesta exitosa
     */
    public static function success($data = null, $message = 'Operación exitosa', $code = 200) {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    /**
     * Enviar respuesta de error
     */
    public static function error($message = 'Error interno del servidor', $code = 400, $details = null) {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'error' => $details
        ], $code);
    }
    
    /**
     * Enviar respuesta de validación
     */
    public static function validation($errors, $message = 'Errores de validación') {
        self::sendResponse([
            'success' => false,
            'message' => $message,
            'validation_errors' => $errors
        ], 422);
    }
    
    /**
     * Enviar respuesta no autorizada
     */
    public static function unauthorized($message = 'No autorizado') {
        self::sendResponse([
            'success' => false,
            'message' => $message
        ], 401);
    }
    
    /**
     * Enviar respuesta prohibida
     */
    public static function forbidden($message = 'Acceso prohibido') {
        self::sendResponse([
            'success' => false,
            'message' => $message
        ], 403);
    }
    
    /**
     * Enviar respuesta no encontrado
     */
    public static function notFound($message = 'Recurso no encontrado') {
        self::sendResponse([
            'success' => false,
            'message' => $message
        ], 404);
    }
    
    /**
     * Enviar respuesta de método no permitido
     */
    public static function methodNotAllowed($message = 'Método no permitido') {
        self::sendResponse([
            'success' => false,
            'message' => $message
        ], 405);
    }
    
    /**
     * Enviar respuesta con paginación
     */
    public static function paginated($data, $pagination, $message = 'Datos obtenidos correctamente') {
        self::sendResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination
        ], 200);
    }
    
    /**
     * Enviar respuesta JSON
     */
    private static function sendResponse($data, $code = 200) {
        // Establecer headers
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Manejar preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Establecer código de respuesta
        http_response_code($code);
        
        // Agregar metadata
        $response = array_merge($data, [
            'timestamp' => date('c'),
            'status_code' => $code
        ]);
        
        // Enviar respuesta
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Obtener código de estado HTTP por nombre
     */
    public static function getStatusCode($status) {
        $codes = [
            'ok' => 200,
            'created' => 201,
            'accepted' => 202,
            'no_content' => 204,
            'bad_request' => 400,
            'unauthorized' => 401,
            'forbidden' => 403,
            'not_found' => 404,
            'method_not_allowed' => 405,
            'conflict' => 409,
            'unprocessable_entity' => 422,
            'internal_server_error' => 500,
            'not_implemented' => 501,
            'service_unavailable' => 503
        ];
        
        return $codes[$status] ?? 500;
    }
    
    /**
     * Validar datos de entrada
     */
    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Campo requerido
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field][] = "El campo {$field} es requerido";
                continue;
            }
            
            // Si el campo está vacío y no es requerido, continuar
            if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
                continue;
            }
            
            // Validar tipo
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "El campo {$field} debe ser un email válido";
                        }
                        break;
                    case 'integer':
                        if (!is_numeric($value) || (int)$value != $value) {
                            $errors[$field][] = "El campo {$field} debe ser un número entero";
                        }
                        break;
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = "El campo {$field} debe ser una cadena de texto";
                        }
                        break;
                    case 'date':
                        if (!strtotime($value)) {
                            $errors[$field][] = "El campo {$field} debe ser una fecha válida";
                        }
                        break;
                }
            }
            
            // Validar longitud mínima
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field][] = "El campo {$field} debe tener al menos {$rule['min_length']} caracteres";
            }
            
            // Validar longitud máxima
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field][] = "El campo {$field} no puede tener más de {$rule['max_length']} caracteres";
            }
            
            // Validar valor mínimo
            if (isset($rule['min']) && is_numeric($value) && $value < $rule['min']) {
                $errors[$field][] = "El campo {$field} debe ser mayor o igual a {$rule['min']}";
            }
            
            // Validar valor máximo
            if (isset($rule['max']) && is_numeric($value) && $value > $rule['max']) {
                $errors[$field][] = "El campo {$field} debe ser menor o igual a {$rule['max']}";
            }
            
            // Validar opciones permitidas
            if (isset($rule['in']) && !in_array($value, $rule['in'])) {
                $options = implode(', ', $rule['in']);
                $errors[$field][] = "El campo {$field} debe ser uno de: {$options}";
            }
            
            // Validar expresión regular
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $message = $rule['pattern_message'] ?? "El campo {$field} no tiene el formato correcto";
                $errors[$field][] = $message;
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitizar datos de entrada
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        if (is_string($data)) {
            // Remover espacios al inicio y final
            $data = trim($data);
            // Convertir caracteres especiales a entidades HTML
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            // Remover tags HTML
            $data = strip_tags($data);
        }
        
        return $data;
    }
    
    /**
     * Crear respuesta de paginación
     */
    public static function createPagination($page, $limit, $total) {
        $totalPages = ceil($total / $limit);
        
        return [
            'current_page' => (int)$page,
            'per_page' => (int)$limit,
            'total' => (int)$total,
            'total_pages' => (int)$totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1,
            'next_page' => $page < $totalPages ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null
        ];
    }
}
?>