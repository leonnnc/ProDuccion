<?php
/**
 * Modelo ChatbotConversacion
 * Gestión de conversaciones del chatbot
 */

require_once __DIR__ . '/../config/database.php';

class ChatbotConversacion {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear nueva conversación
     */
    public function crear($datos) {
        $this->validarDatos($datos, ['usuario_id', 'sesion_id', 'mensaje_usuario', 'respuesta_bot']);
        
        $sql = "INSERT INTO chatbot_conversaciones (usuario_id, sesion_id, mensaje_usuario, respuesta_bot, contexto_modulo, tiempo_respuesta) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['usuario_id'],
            $datos['sesion_id'],
            $datos['mensaje_usuario'],
            $datos['respuesta_bot'],
            $datos['contexto_modulo'] ?? 'general',
            $datos['tiempo_respuesta'] ?? null
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Obtener conversaciones por usuario
     */
    public function obtenerPorUsuario($usuarioId, $limite = 50) {
        $sql = "SELECT * FROM chatbot_conversaciones 
                WHERE usuario_id = ? 
                ORDER BY fecha_creacion DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$usuarioId, $limite]);
    }
    
    /**
     * Obtener conversaciones por sesión
     */
    public function obtenerPorSesion($sesionId, $limite = 20) {
        $sql = "SELECT * FROM chatbot_conversaciones 
                WHERE sesion_id = ? 
                ORDER BY fecha_creacion ASC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$sesionId, $limite]);
    }
    
    /**
     * Registrar feedback de utilidad
     */
    public function registrarFeedback($conversacionId, $utilidad) {
        if ($utilidad < 1 || $utilidad > 5) {
            throw new Exception('La utilidad debe estar entre 1 y 5');
        }
        
        $sql = "UPDATE chatbot_conversaciones SET utilidad_respuesta = ? WHERE id = ?";
        return $this->db->update($sql, [$utilidad, $conversacionId]);
    }
    
    /**
     * Obtener estadísticas de conversaciones
     */
    public function obtenerEstadisticas($fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT 
                    COUNT(*) as total_conversaciones,
                    COUNT(DISTINCT usuario_id) as usuarios_unicos,
                    COUNT(DISTINCT sesion_id) as sesiones_unicas,
                    AVG(utilidad_respuesta) as utilidad_promedio,
                    AVG(tiempo_respuesta) as tiempo_promedio,
                    contexto_modulo,
                    COUNT(*) as conversaciones_por_modulo
                FROM chatbot_conversaciones";
        
        $params = [];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " WHERE fecha_creacion BETWEEN ? AND ?";
            $params = [$fechaInicio, $fechaFin];
        }
        
        $sql .= " GROUP BY contexto_modulo ORDER BY conversaciones_por_modulo DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Limpiar conversaciones antiguas
     */
    public function limpiarAntiguas($diasAtras = 90) {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$diasAtras} days"));
        
        $sql = "DELETE FROM chatbot_conversaciones WHERE fecha_creacion < ?";
        return $this->db->delete($sql, [$fechaLimite]);
    }
    
    /**
     * Obtener conversaciones recientes
     */
    public function obtenerRecientes($limite = 10) {
        $sql = "SELECT cc.*, u.nombre as usuario_nombre
                FROM chatbot_conversaciones cc
                LEFT JOIN usuarios u ON cc.usuario_id = u.id
                ORDER BY cc.fecha_creacion DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limite]);
    }
    
    /**
     * Obtener conversaciones por contexto
     */
    public function obtenerPorContexto($contextoModulo, $limite = 20) {
        $sql = "SELECT cc.*, u.nombre as usuario_nombre
                FROM chatbot_conversaciones cc
                LEFT JOIN usuarios u ON cc.usuario_id = u.id
                WHERE cc.contexto_modulo = ?
                ORDER BY cc.fecha_creacion DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$contextoModulo, $limite]);
    }
    
    /**
     * Buscar conversaciones por texto
     */
    public function buscar($texto, $limite = 20) {
        $sql = "SELECT cc.*, u.nombre as usuario_nombre
                FROM chatbot_conversaciones cc
                LEFT JOIN usuarios u ON cc.usuario_id = u.id
                WHERE cc.mensaje_usuario LIKE ? OR cc.respuesta_bot LIKE ?
                ORDER BY cc.fecha_creacion DESC
                LIMIT ?";
        
        $buscar = '%' . $texto . '%';
        return $this->db->fetchAll($sql, [$buscar, $buscar, $limite]);
    }
    
    /**
     * Obtener estadísticas detalladas
     */
    public function obtenerEstadisticasDetalladas($fechaInicio = null, $fechaFin = null) {
        $whereClause = '';
        $params = [];
        
        if ($fechaInicio && $fechaFin) {
            $whereClause = 'WHERE fecha_creacion BETWEEN ? AND ?';
            $params = [$fechaInicio, $fechaFin];
        }
        
        // Estadísticas generales
        $sqlGeneral = "SELECT 
                        COUNT(*) as total_conversaciones,
                        COUNT(DISTINCT usuario_id) as usuarios_unicos,
                        COUNT(DISTINCT sesion_id) as sesiones_unicas,
                        AVG(utilidad_respuesta) as utilidad_promedio,
                        AVG(tiempo_respuesta) as tiempo_promedio_ms,
                        MIN(fecha_creacion) as primera_conversacion,
                        MAX(fecha_creacion) as ultima_conversacion
                       FROM chatbot_conversaciones {$whereClause}";
        
        $estadisticasGenerales = $this->db->fetch($sqlGeneral, $params);
        
        // Estadísticas por módulo
        $sqlModulo = "SELECT 
                        contexto_modulo,
                        COUNT(*) as conversaciones,
                        AVG(utilidad_respuesta) as utilidad_promedio,
                        AVG(tiempo_respuesta) as tiempo_promedio_ms
                      FROM chatbot_conversaciones {$whereClause}
                      GROUP BY contexto_modulo
                      ORDER BY conversaciones DESC";
        
        $estadisticasPorModulo = $this->db->fetchAll($sqlModulo, $params);
        
        // Estadísticas por día (últimos 30 días)
        $sqlDiario = "SELECT 
                        DATE(fecha_creacion) as fecha,
                        COUNT(*) as conversaciones,
                        COUNT(DISTINCT usuario_id) as usuarios_unicos
                      FROM chatbot_conversaciones 
                      WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      GROUP BY DATE(fecha_creacion)
                      ORDER BY fecha DESC";
        
        $estadisticasDiarias = $this->db->fetchAll($sqlDiario);
        
        // Usuarios más activos
        $sqlUsuarios = "SELECT 
                          u.nombre,
                          COUNT(*) as conversaciones,
                          AVG(cc.utilidad_respuesta) as utilidad_promedio
                        FROM chatbot_conversaciones cc
                        LEFT JOIN usuarios u ON cc.usuario_id = u.id
                        {$whereClause}
                        GROUP BY cc.usuario_id, u.nombre
                        ORDER BY conversaciones DESC
                        LIMIT 10";
        
        $usuariosMasActivos = $this->db->fetchAll($sqlUsuarios, $params);
        
        return [
            'generales' => $estadisticasGenerales,
            'por_modulo' => $estadisticasPorModulo,
            'diarias' => $estadisticasDiarias,
            'usuarios_activos' => $usuariosMasActivos
        ];
    }
    
    /**
     * Exportar conversaciones
     */
    public function exportar($filtros = []) {
        $sql = "SELECT cc.*, u.nombre as usuario_nombre
                FROM chatbot_conversaciones cc
                LEFT JOIN usuarios u ON cc.usuario_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['usuario_id'])) {
            $sql .= " AND cc.usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        
        if (!empty($filtros['contexto_modulo'])) {
            $sql .= " AND cc.contexto_modulo = ?";
            $params[] = $filtros['contexto_modulo'];
        }
        
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND cc.fecha_creacion >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND cc.fecha_creacion <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        
        $sql .= " ORDER BY cc.fecha_creacion DESC";
        
        if (!empty($filtros['limite'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limite'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtener sesiones activas
     */
    public function obtenerSesionesActivas($minutosInactividad = 30) {
        $sql = "SELECT 
                    sesion_id,
                    usuario_id,
                    u.nombre as usuario_nombre,
                    COUNT(*) as mensajes,
                    MAX(fecha_creacion) as ultima_actividad,
                    MIN(fecha_creacion) as inicio_sesion
                FROM chatbot_conversaciones cc
                LEFT JOIN usuarios u ON cc.usuario_id = u.id
                WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                GROUP BY sesion_id, usuario_id, u.nombre
                ORDER BY ultima_actividad DESC";
        
        return $this->db->fetchAll($sql, [$minutosInactividad]);
    }
    
    /**
     * Validar datos de entrada
     */
    private function validarDatos($datos, $camposRequeridos) {
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || empty($datos[$campo])) {
                throw new Exception("El campo {$campo} es requerido");
            }
        }
    }
}
?>