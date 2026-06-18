<?php
/**
 * Modelo Tarea
 * Gestión de tareas del staff de producción
 */

require_once __DIR__ . '/../config/database.php';

class Tarea {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear nueva tarea
     */
    public function crear($datos, $creadoPor) {
        // Validar datos requeridos
        $this->validarDatos($datos, ['titulo', 'proyecto_id', 'asignado_a']);
        
        // Verificar que el proyecto existe
        if (!$this->existeProyecto($datos['proyecto_id'])) {
            throw new Exception('El proyecto especificado no existe');
        }
        
        // Verificar que el usuario asignado existe
        if (!$this->existeUsuario($datos['asignado_a'])) {
            throw new Exception('El usuario asignado no existe');
        }
        
        // Validar fecha de vencimiento
        if (!empty($datos['fecha_vencimiento'])) {
            $this->validarFechaVencimiento($datos['fecha_vencimiento']);
        }
        
        $sql = "INSERT INTO tareas (titulo, descripcion, proyecto_id, asignado_a, estado, prioridad, fecha_vencimiento, creado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['titulo'],
            $datos['descripcion'] ?? null,
            $datos['proyecto_id'],
            $datos['asignado_a'],
            $datos['estado'] ?? 'pendiente',
            $datos['prioridad'] ?? 'media',
            $datos['fecha_vencimiento'] ?? null,
            $creadoPor
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Obtener tarea por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT t.*, 
                       p.nombre as proyecto_nombre,
                       u.nombre as asignado_nombre,
                       c.nombre as creado_por_nombre
                FROM tareas t 
                LEFT JOIN proyectos p ON t.proyecto_id = p.id
                LEFT JOIN usuarios u ON t.asignado_a = u.id 
                LEFT JOIN usuarios c ON t.creado_por = c.id
                WHERE t.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Obtener todas las tareas con filtros
     */
    public function obtenerTodas($filtros = []) {
        $sql = "SELECT t.*, 
                       p.nombre as proyecto_nombre,
                       u.nombre as asignado_nombre,
                       c.nombre as creado_por_nombre
                FROM tareas t 
                LEFT JOIN proyectos p ON t.proyecto_id = p.id
                LEFT JOIN usuarios u ON t.asignado_a = u.id 
                LEFT JOIN usuarios c ON t.creado_por = c.id
                WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['prioridad'])) {
            $sql .= " AND t.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }
        
        if (!empty($filtros['asignado_a'])) {
            $sql .= " AND t.asignado_a = ?";
            $params[] = $filtros['asignado_a'];
        }
        
        if (!empty($filtros['proyecto_id'])) {
            $sql .= " AND t.proyecto_id = ?";
            $params[] = $filtros['proyecto_id'];
        }
        
        if (!empty($filtros['creado_por'])) {
            $sql .= " AND t.creado_por = ?";
            $params[] = $filtros['creado_por'];
        }
        
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (t.titulo LIKE ? OR t.descripcion LIKE ?)";
            $buscar = '%' . $filtros['buscar'] . '%';
            $params[] = $buscar;
            $params[] = $buscar;
        }
        
        if (!empty($filtros['fecha_vencimiento_desde'])) {
            $sql .= " AND t.fecha_vencimiento >= ?";
            $params[] = $filtros['fecha_vencimiento_desde'];
        }
        
        if (!empty($filtros['fecha_vencimiento_hasta'])) {
            $sql .= " AND t.fecha_vencimiento <= ?";
            $params[] = $filtros['fecha_vencimiento_hasta'];
        }
        
        // Filtro para tareas vencidas
        if (isset($filtros['vencidas']) && $filtros['vencidas']) {
            $sql .= " AND t.fecha_vencimiento < CURDATE() AND t.estado NOT IN ('completada', 'cancelada')";
        }
        
        // Filtro para tareas próximas a vencer
        if (isset($filtros['proximas_vencer']) && $filtros['proximas_vencer']) {
            $dias = $filtros['dias_vencimiento'] ?? 7;
            $sql .= " AND t.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
            $sql .= " AND t.estado NOT IN ('completada', 'cancelada')";
            $params[] = $dias;
        }
        
        // Ordenamiento
        $ordenamiento = $filtros['orden'] ?? 'fecha_creacion';
        $direccion = $filtros['direccion'] ?? 'DESC';
        
        $ordenamientosValidos = ['titulo', 'estado', 'prioridad', 'fecha_vencimiento', 'fecha_creacion'];
        if (in_array($ordenamiento, $ordenamientosValidos)) {
            $sql .= " ORDER BY t.{$ordenamiento} {$direccion}";
        } else {
            $sql .= " ORDER BY t.prioridad DESC, t.fecha_creacion DESC";
        }
        
        // Aplicar límite si se especifica
        if (!empty($filtros['limite'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limite'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Actualizar tarea
     */
    public function actualizar($id, $datos) {
        // Verificar que la tarea existe
        $tareaExistente = $this->obtenerPorId($id);
        if (!$tareaExistente) {
            throw new Exception('Tarea no encontrada');
        }
        
        // Construir query dinámicamente
        $campos = [];
        $params = [];
        
        if (isset($datos['titulo'])) {
            $this->validarTitulo($datos['titulo']);
            $campos[] = "titulo = ?";
            $params[] = $datos['titulo'];
        }
        
        if (isset($datos['descripcion'])) {
            $campos[] = "descripcion = ?";
            $params[] = $datos['descripcion'];
        }
        
        if (isset($datos['proyecto_id'])) {
            if (!$this->existeProyecto($datos['proyecto_id'])) {
                throw new Exception('El proyecto especificado no existe');
            }
            $campos[] = "proyecto_id = ?";
            $params[] = $datos['proyecto_id'];
        }
        
        if (isset($datos['asignado_a'])) {
            if (!$this->existeUsuario($datos['asignado_a'])) {
                throw new Exception('El usuario asignado no existe');
            }
            $campos[] = "asignado_a = ?";
            $params[] = $datos['asignado_a'];
        }
        
        if (isset($datos['estado'])) {
            $this->validarEstado($datos['estado']);
            $campos[] = "estado = ?";
            $params[] = $datos['estado'];
            
            // Si se marca como completada, establecer fecha de completado
            if ($datos['estado'] === 'completada') {
                $campos[] = "fecha_completada = NOW()";
            }
        }
        
        if (isset($datos['prioridad'])) {
            $this->validarPrioridad($datos['prioridad']);
            $campos[] = "prioridad = ?";
            $params[] = $datos['prioridad'];
        }
        
        if (isset($datos['fecha_vencimiento'])) {
            if (!empty($datos['fecha_vencimiento'])) {
                $this->validarFechaVencimiento($datos['fecha_vencimiento']);
            }
            $campos[] = "fecha_vencimiento = ?";
            $params[] = $datos['fecha_vencimiento'] ?: null;
        }
        
        if (empty($campos)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE tareas SET " . implode(', ', $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Eliminar tarea
     */
    public function eliminar($id) {
        // Verificar que la tarea existe
        $tarea = $this->obtenerPorId($id);
        if (!$tarea) {
            throw new Exception('Tarea no encontrada');
        }
        
        $sql = "DELETE FROM tareas WHERE id = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    /**
     * Cambiar estado de la tarea
     */
    public function cambiarEstado($id, $nuevoEstado) {
        $this->validarEstado($nuevoEstado);
        
        $campos = ["estado = ?"];
        $params = [$nuevoEstado];
        
        // Si se marca como completada, establecer fecha de completado
        if ($nuevoEstado === 'completada') {
            $campos[] = "fecha_completada = NOW()";
        } else {
            $campos[] = "fecha_completada = NULL";
        }
        
        $params[] = $id;
        $sql = "UPDATE tareas SET " . implode(', ', $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Obtener tareas por usuario
     */
    public function obtenerPorUsuario($usuarioId, $filtros = []) {
        $filtros['asignado_a'] = $usuarioId;
        return $this->obtenerTodas($filtros);
    }
    
    /**
     * Obtener tareas por proyecto
     */
    public function obtenerPorProyecto($proyectoId, $filtros = []) {
        $filtros['proyecto_id'] = $proyectoId;
        return $this->obtenerTodas($filtros);
    }
    
    /**
     * Obtener tareas vencidas
     */
    public function obtenerVencidas($usuarioId = null) {
        $filtros = ['vencidas' => true];
        if ($usuarioId) {
            $filtros['asignado_a'] = $usuarioId;
        }
        return $this->obtenerTodas($filtros);
    }
    
    /**
     * Obtener tareas próximas a vencer
     */
    public function obtenerProximasVencer($dias = 7, $usuarioId = null) {
        $filtros = [
            'proximas_vencer' => true,
            'dias_vencimiento' => $dias
        ];
        if ($usuarioId) {
            $filtros['asignado_a'] = $usuarioId;
        }
        return $this->obtenerTodas($filtros);
    }
    
    /**
     * Obtener estadísticas de tareas
     */
    public function obtenerEstadisticas($filtros = []) {
        $sql = "SELECT 
                    COUNT(*) as total_tareas,
                    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
                    COUNT(CASE WHEN estado = 'en_progreso' THEN 1 END) as en_progreso,
                    COUNT(CASE WHEN estado = 'completada' THEN 1 END) as completadas,
                    COUNT(CASE WHEN estado = 'cancelada' THEN 1 END) as canceladas,
                    COUNT(CASE WHEN prioridad = 'alta' THEN 1 END) as alta_prioridad,
                    COUNT(CASE WHEN prioridad = 'media' THEN 1 END) as media_prioridad,
                    COUNT(CASE WHEN prioridad = 'baja' THEN 1 END) as baja_prioridad,
                    COUNT(CASE WHEN fecha_vencimiento < CURDATE() AND estado NOT IN ('completada', 'cancelada') THEN 1 END) as vencidas
                FROM tareas t
                WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros si se proporcionan
        if (!empty($filtros['asignado_a'])) {
            $sql .= " AND t.asignado_a = ?";
            $params[] = $filtros['asignado_a'];
        }
        
        if (!empty($filtros['proyecto_id'])) {
            $sql .= " AND t.proyecto_id = ?";
            $params[] = $filtros['proyecto_id'];
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * Obtener estadísticas por usuario
     */
    public function obtenerEstadisticasPorUsuario() {
        $sql = "SELECT 
                    u.id,
                    u.nombre,
                    COUNT(t.id) as total_tareas,
                    COUNT(CASE WHEN t.estado = 'pendiente' THEN 1 END) as pendientes,
                    COUNT(CASE WHEN t.estado = 'en_progreso' THEN 1 END) as en_progreso,
                    COUNT(CASE WHEN t.estado = 'completada' THEN 1 END) as completadas,
                    COUNT(CASE WHEN t.fecha_vencimiento < CURDATE() AND t.estado NOT IN ('completada', 'cancelada') THEN 1 END) as vencidas
                FROM usuarios u
                LEFT JOIN tareas t ON u.id = t.asignado_a
                WHERE u.activo = 1
                GROUP BY u.id, u.nombre
                ORDER BY u.nombre";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Buscar tareas por texto
     */
    public function buscar($texto, $limite = 10) {
        $sql = "SELECT t.*, 
                       p.nombre as proyecto_nombre,
                       u.nombre as asignado_nombre
                FROM tareas t
                LEFT JOIN proyectos p ON t.proyecto_id = p.id
                LEFT JOIN usuarios u ON t.asignado_a = u.id
                WHERE (t.titulo LIKE ? OR t.descripcion LIKE ?)
                ORDER BY t.prioridad DESC, t.fecha_creacion DESC
                LIMIT ?";
        
        $buscar = '%' . $texto . '%';
        return $this->db->fetchAll($sql, [$buscar, $buscar, $limite]);
    }
    
    /**
     * Duplicar tarea
     */
    public function duplicar($tareaId, $nuevoTitulo, $creadoPor) {
        $tareaOriginal = $this->obtenerPorId($tareaId);
        if (!$tareaOriginal) {
            throw new Exception('Tarea original no encontrada');
        }
        
        $datosNueva = [
            'titulo' => $nuevoTitulo,
            'descripcion' => $tareaOriginal['descripcion'],
            'proyecto_id' => $tareaOriginal['proyecto_id'],
            'asignado_a' => $tareaOriginal['asignado_a'],
            'estado' => 'pendiente',
            'prioridad' => $tareaOriginal['prioridad'],
            'fecha_vencimiento' => $tareaOriginal['fecha_vencimiento']
        ];
        
        return $this->crear($datosNueva, $creadoPor);
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
        
        // Validar título
        if (isset($datos['titulo'])) {
            $this->validarTitulo($datos['titulo']);
        }
        
        // Validar estado
        if (isset($datos['estado'])) {
            $this->validarEstado($datos['estado']);
        }
        
        // Validar prioridad
        if (isset($datos['prioridad'])) {
            $this->validarPrioridad($datos['prioridad']);
        }
    }
    
    /**
     * Validar título de la tarea
     */
    private function validarTitulo($titulo) {
        if (strlen($titulo) < 3) {
            throw new Exception('El título de la tarea debe tener al menos 3 caracteres');
        }
        
        if (strlen($titulo) > 200) {
            throw new Exception('El título de la tarea no puede tener más de 200 caracteres');
        }
    }
    
    /**
     * Validar estado de la tarea
     */
    private function validarEstado($estado) {
        $estadosValidos = ['pendiente', 'en_progreso', 'completada', 'cancelada'];
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception('Estado de tarea inválido');
        }
    }
    
    /**
     * Validar prioridad de la tarea
     */
    private function validarPrioridad($prioridad) {
        $prioridadesValidas = ['baja', 'media', 'alta'];
        if (!in_array($prioridad, $prioridadesValidas)) {
            throw new Exception('Prioridad de tarea inválida');
        }
    }
    
    /**
     * Validar fecha de vencimiento
     */
    private function validarFechaVencimiento($fecha) {
        $fechaVencimiento = new DateTime($fecha);
        $hoy = new DateTime();
        
        // Permitir fechas pasadas para flexibilidad, pero advertir
        if ($fechaVencimiento < $hoy) {
            // No lanzar excepción, solo registrar advertencia
            error_log("Advertencia: Fecha de vencimiento en el pasado para tarea");
        }
    }
    
    /**
     * Verificar si existe proyecto
     */
    private function existeProyecto($proyectoId) {
        $sql = "SELECT COUNT(*) FROM proyectos WHERE id = ?";
        return $this->db->fetch($sql, [$proyectoId])['COUNT(*)'] > 0;
    }
    
    /**
     * Verificar si existe usuario
     */
    private function existeUsuario($usuarioId) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE id = ? AND activo = 1";
        return $this->db->fetch($sql, [$usuarioId])['COUNT(*)'] > 0;
    }
}
?>