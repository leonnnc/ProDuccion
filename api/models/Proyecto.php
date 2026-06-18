<?php
/**
 * Modelo Proyecto
 * Gestión de proyectos de producción
 */

require_once __DIR__ . '/../config/database.php';

class Proyecto {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear nuevo proyecto
     */
    public function crear($datos, $creadoPor) {
        // Validar datos requeridos
        $this->validarDatos($datos, ['nombre']);
        
        // Verificar que el responsable existe si se especifica
        if (!empty($datos['responsable_id']) && !$this->existeUsuario($datos['responsable_id'])) {
            throw new Exception('El usuario responsable especificado no existe');
        }
        
        // Validar fechas
        $this->validarFechas($datos);
        
        $sql = "INSERT INTO proyectos (nombre, descripcion, estado, fecha_inicio, fecha_fin, responsable_id, creado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            $datos['estado'] ?? 'planificacion',
            $datos['fecha_inicio'] ?? null,
            $datos['fecha_fin'] ?? null,
            $datos['responsable_id'] ?? null,
            $creadoPor
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Obtener proyecto por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT p.*, 
                       u.nombre as responsable_nombre,
                       c.nombre as creado_por_nombre,
                       COUNT(t.id) as total_tareas,
                       COUNT(CASE WHEN t.estado = 'completada' THEN 1 END) as tareas_completadas
                FROM proyectos p 
                LEFT JOIN usuarios u ON p.responsable_id = u.id 
                LEFT JOIN usuarios c ON p.creado_por = c.id
                LEFT JOIN tareas t ON p.id = t.proyecto_id
                WHERE p.id = ?
                GROUP BY p.id";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Obtener todos los proyectos con filtros
     */
    public function obtenerTodos($filtros = []) {
        $sql = "SELECT p.*, 
                       u.nombre as responsable_nombre,
                       c.nombre as creado_por_nombre,
                       COUNT(t.id) as total_tareas,
                       COUNT(CASE WHEN t.estado = 'completada' THEN 1 END) as tareas_completadas
                FROM proyectos p 
                LEFT JOIN usuarios u ON p.responsable_id = u.id 
                LEFT JOIN usuarios c ON p.creado_por = c.id
                LEFT JOIN tareas t ON p.id = t.proyecto_id
                WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['responsable_id'])) {
            $sql .= " AND p.responsable_id = ?";
            $params[] = $filtros['responsable_id'];
        }
        
        if (!empty($filtros['creado_por'])) {
            $sql .= " AND p.creado_por = ?";
            $params[] = $filtros['creado_por'];
        }
        
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $buscar = '%' . $filtros['buscar'] . '%';
            $params[] = $buscar;
            $params[] = $buscar;
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND (p.fecha_inicio >= ? OR p.fecha_inicio IS NULL)";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND (p.fecha_fin <= ? OR p.fecha_fin IS NULL)";
            $params[] = $filtros['fecha_hasta'];
        }
        
        // Solo proyectos activos por defecto
        if (!isset($filtros['incluir_inactivos']) || !$filtros['incluir_inactivos']) {
            $sql .= " AND p.estado != 'cancelado'";
        }
        
        $sql .= " GROUP BY p.id";
        
        // Ordenamiento
        $ordenamiento = $filtros['orden'] ?? 'fecha_creacion';
        $direccion = $filtros['direccion'] ?? 'DESC';
        
        $ordenamientosValidos = ['nombre', 'estado', 'fecha_inicio', 'fecha_fin', 'fecha_creacion'];
        if (in_array($ordenamiento, $ordenamientosValidos)) {
            $sql .= " ORDER BY p.{$ordenamiento} {$direccion}";
        } else {
            $sql .= " ORDER BY p.fecha_creacion DESC";
        }
        
        // Aplicar límite si se especifica
        if (!empty($filtros['limite'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limite'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Actualizar proyecto
     */
    public function actualizar($id, $datos) {
        // Verificar que el proyecto existe
        $proyectoExistente = $this->obtenerPorId($id);
        if (!proyectoExistente) {
            throw new Exception('Proyecto no encontrado');
        }
        
        // Construir query dinámicamente
        $campos = [];
        $params = [];
        
        if (isset($datos['nombre'])) {
            $this->validarNombre($datos['nombre']);
            $campos[] = "nombre = ?";
            $params[] = $datos['nombre'];
        }
        
        if (isset($datos['descripcion'])) {
            $campos[] = "descripcion = ?";
            $params[] = $datos['descripcion'];
        }
        
        if (isset($datos['estado'])) {
            $this->validarEstado($datos['estado']);
            $campos[] = "estado = ?";
            $params[] = $datos['estado'];
        }
        
        if (isset($datos['fecha_inicio'])) {
            $campos[] = "fecha_inicio = ?";
            $params[] = $datos['fecha_inicio'] ?: null;
        }
        
        if (isset($datos['fecha_fin'])) {
            $campos[] = "fecha_fin = ?";
            $params[] = $datos['fecha_fin'] ?: null;
        }
        
        if (isset($datos['responsable_id'])) {
            if (!empty($datos['responsable_id']) && !$this->existeUsuario($datos['responsable_id'])) {
                throw new Exception('El usuario responsable especificado no existe');
            }
            $campos[] = "responsable_id = ?";
            $params[] = $datos['responsable_id'] ?: null;
        }
        
        if (empty($campos)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        // Validar fechas si se proporcionan ambas
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin'])) {
            $this->validarFechas($datos);
        }
        
        $params[] = $id;
        $sql = "UPDATE proyectos SET " . implode(', ', $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Eliminar proyecto
     */
    public function eliminar($id) {
        // Verificar que el proyecto existe
        $proyecto = $this->obtenerPorId($id);
        if (!proyecto) {
            throw new Exception('Proyecto no encontrado');
        }
        
        // Verificar si tiene tareas asociadas
        $sql = "SELECT COUNT(*) as total FROM tareas WHERE proyecto_id = ?";
        $resultado = $this->db->fetch($sql, [$id]);
        
        if ($resultado['total'] > 0) {
            throw new Exception('No se puede eliminar el proyecto porque tiene tareas asociadas');
        }
        
        $sql = "DELETE FROM proyectos WHERE id = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    /**
     * Cambiar estado del proyecto
     */
    public function cambiarEstado($id, $nuevoEstado) {
        $this->validarEstado($nuevoEstado);
        
        $sql = "UPDATE proyectos SET estado = ? WHERE id = ?";
        return $this->db->update($sql, [$nuevoEstado, $id]);
    }
    
    /**
     * Obtener tareas del proyecto
     */
    public function obtenerTareas($proyectoId, $filtros = []) {
        $sql = "SELECT t.*, u.nombre as asignado_nombre
                FROM tareas t
                LEFT JOIN usuarios u ON t.asignado_a = u.id
                WHERE t.proyecto_id = ?";
        
        $params = [$proyectoId];
        
        // Aplicar filtros adicionales
        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['asignado_a'])) {
            $sql .= " AND t.asignado_a = ?";
            $params[] = $filtros['asignado_a'];
        }
        
        $sql .= " ORDER BY t.prioridad DESC, t.fecha_creacion DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtener estadísticas del proyecto
     */
    public function obtenerEstadisticas($proyectoId) {
        $sql = "SELECT 
                    COUNT(*) as total_tareas,
                    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as tareas_pendientes,
                    COUNT(CASE WHEN estado = 'en_progreso' THEN 1 END) as tareas_en_progreso,
                    COUNT(CASE WHEN estado = 'completada' THEN 1 END) as tareas_completadas,
                    COUNT(CASE WHEN estado = 'cancelada' THEN 1 END) as tareas_canceladas,
                    COUNT(CASE WHEN prioridad = 'alta' THEN 1 END) as tareas_alta_prioridad,
                    COUNT(CASE WHEN fecha_vencimiento < CURDATE() AND estado NOT IN ('completada', 'cancelada') THEN 1 END) as tareas_vencidas
                FROM tareas 
                WHERE proyecto_id = ?";
        
        return $this->db->fetch($sql, [$proyectoId]);
    }
    
    /**
     * Obtener proyectos por usuario responsable
     */
    public function obtenerPorResponsable($usuarioId, $filtros = []) {
        $filtros['responsable_id'] = $usuarioId;
        return $this->obtenerTodos($filtros);
    }
    
    /**
     * Obtener proyectos creados por usuario
     */
    public function obtenerPorCreador($usuarioId, $filtros = []) {
        $filtros['creado_por'] = $usuarioId;
        return $this->obtenerTodos($filtros);
    }
    
    /**
     * Duplicar proyecto
     */
    public function duplicar($proyectoId, $nuevoNombre, $creadoPor) {
        $proyectoOriginal = $this->obtenerPorId($proyectoId);
        if (!proyectoOriginal) {
            throw new Exception('Proyecto original no encontrado');
        }
        
        $datosNuevo = [
            'nombre' => $nuevoNombre,
            'descripcion' => $proyectoOriginal['descripcion'],
            'estado' => 'planificacion',
            'responsable_id' => $proyectoOriginal['responsable_id']
        ];
        
        return $this->crear($datosNuevo, $creadoPor);
    }
    
    /**
     * Obtener resumen de proyectos por estado
     */
    public function obtenerResumenPorEstado() {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as cantidad,
                    COUNT(CASE WHEN responsable_id IS NOT NULL THEN 1 END) as con_responsable
                FROM proyectos 
                GROUP BY estado
                ORDER BY 
                    CASE estado 
                        WHEN 'planificacion' THEN 1
                        WHEN 'en_progreso' THEN 2
                        WHEN 'completado' THEN 3
                        WHEN 'cancelado' THEN 4
                    END";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Buscar proyectos por texto
     */
    public function buscar($texto, $limite = 10) {
        $sql = "SELECT p.*, u.nombre as responsable_nombre
                FROM proyectos p
                LEFT JOIN usuarios u ON p.responsable_id = u.id
                WHERE (p.nombre LIKE ? OR p.descripcion LIKE ?)
                AND p.estado != 'cancelado'
                ORDER BY p.nombre ASC
                LIMIT ?";
        
        $buscar = '%' . $texto . '%';
        return $this->db->fetchAll($sql, [$buscar, $buscar, $limite]);
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
        
        // Validar nombre
        if (isset($datos['nombre'])) {
            $this->validarNombre($datos['nombre']);
        }
        
        // Validar estado
        if (isset($datos['estado'])) {
            $this->validarEstado($datos['estado']);
        }
    }
    
    /**
     * Validar nombre del proyecto
     */
    private function validarNombre($nombre) {
        if (strlen($nombre) < 3) {
            throw new Exception('El nombre del proyecto debe tener al menos 3 caracteres');
        }
        
        if (strlen($nombre) > 200) {
            throw new Exception('El nombre del proyecto no puede tener más de 200 caracteres');
        }
    }
    
    /**
     * Validar estado del proyecto
     */
    private function validarEstado($estado) {
        $estadosValidos = ['planificacion', 'en_progreso', 'completado', 'cancelado'];
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception('Estado de proyecto inválido');
        }
    }
    
    /**
     * Validar fechas del proyecto
     */
    private function validarFechas($datos) {
        if (!empty($datos['fecha_inicio']) && !empty($datos['fecha_fin'])) {
            $fechaInicio = new DateTime($datos['fecha_inicio']);
            $fechaFin = new DateTime($datos['fecha_fin']);
            
            if ($fechaFin <= $fechaInicio) {
                throw new Exception('La fecha de fin debe ser posterior a la fecha de inicio');
            }
        }
    }
    
    /**
     * Verificar si existe usuario
     */
    private function existeUsuario($usuarioId) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE id = ? AND activo = 1";
        return $this->db->fetch($sql, [$usuarioId])['COUNT(*)'] > 0;
    }
    
    /**
     * Obtener estadísticas generales de proyectos
     */
    public function obtenerEstadisticasGenerales() {
        $sql = "SELECT 
                    COUNT(*) as total_proyectos,
                    COUNT(CASE WHEN estado = 'planificacion' THEN 1 END) as en_planificacion,
                    COUNT(CASE WHEN estado = 'en_progreso' THEN 1 END) as en_progreso,
                    COUNT(CASE WHEN estado = 'completado' THEN 1 END) as completados,
                    COUNT(CASE WHEN estado = 'cancelado' THEN 1 END) as cancelados,
                    COUNT(CASE WHEN responsable_id IS NULL THEN 1 END) as sin_responsable,
                    AVG(DATEDIFF(COALESCE(fecha_fin, CURDATE()), fecha_inicio)) as duracion_promedio_dias
                FROM proyectos";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Obtener proyectos próximos a vencer
     */
    public function obtenerProximosAVencer($dias = 7) {
        $sql = "SELECT p.*, u.nombre as responsable_nombre
                FROM proyectos p
                LEFT JOIN usuarios u ON p.responsable_id = u.id
                WHERE p.fecha_fin IS NOT NULL 
                AND p.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND p.estado IN ('planificacion', 'en_progreso')
                ORDER BY p.fecha_fin ASC";
        
        return $this->db->fetchAll($sql, [$dias]);
    }
    
    /**
     * Obtener proyectos vencidos
     */
    public function obtenerVencidos() {
        $sql = "SELECT p.*, u.nombre as responsable_nombre
                FROM proyectos p
                LEFT JOIN usuarios u ON p.responsable_id = u.id
                WHERE p.fecha_fin IS NOT NULL 
                AND p.fecha_fin < CURDATE()
                AND p.estado IN ('planificacion', 'en_progreso')
                ORDER BY p.fecha_fin ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Calcular progreso del proyecto basado en tareas
     */
    public function calcularProgreso($proyectoId) {
        $estadisticas = $this->obtenerEstadisticas($proyectoId);
        
        if ($estadisticas['total_tareas'] == 0) {
            return 0;
        }
        
        return round(($estadisticas['tareas_completadas'] / $estadisticas['total_tareas']) * 100, 2);
    }
    
    /**
     * Obtener proyectos activos del usuario
     */
    public function obtenerActivosDelUsuario($usuarioId) {
        $sql = "SELECT DISTINCT p.*, u.nombre as responsable_nombre
                FROM proyectos p
                LEFT JOIN usuarios u ON p.responsable_id = u.id
                LEFT JOIN tareas t ON p.id = t.proyecto_id
                WHERE (p.responsable_id = ? OR t.asignado_a = ? OR p.creado_por = ?)
                AND p.estado IN ('planificacion', 'en_progreso')
                ORDER BY p.fecha_creacion DESC";
        
        return $this->db->fetchAll($sql, [$usuarioId, $usuarioId, $usuarioId]);
    }
    
    /**
     * Archivar proyecto (cambiar a completado)
     */
    public function archivar($proyectoId) {
        // Verificar que todas las tareas estén completadas o canceladas
        $sql = "SELECT COUNT(*) as pendientes 
                FROM tareas 
                WHERE proyecto_id = ? 
                AND estado IN ('pendiente', 'en_progreso')";
        
        $resultado = $this->db->fetch($sql, [$proyectoId]);
        
        if ($resultado['pendientes'] > 0) {
            throw new Exception('No se puede archivar el proyecto porque tiene tareas pendientes');
        }
        
        return $this->cambiarEstado($proyectoId, 'completado');
    }
    
    /**
     * Reactivar proyecto archivado
     */
    public function reactivar($proyectoId) {
        $proyecto = $this->obtenerPorId($proyectoId);
        
        if (!$proyecto) {
            throw new Exception('Proyecto no encontrado');
        }
        
        if ($proyecto['estado'] !== 'completado') {
            throw new Exception('Solo se pueden reactivar proyectos completados');
        }
        
        return $this->cambiarEstado($proyectoId, 'en_progreso');
    }
    
    /**
     * Obtener historial de cambios del proyecto
     */
    public function obtenerHistorial($proyectoId) {
        $sql = "SELECT l.*, u.nombre as usuario_nombre
                FROM logs_sistema l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE JSON_EXTRACT(l.contexto, '$.proyecto_id') = ?
                ORDER BY l.fecha_creacion DESC
                LIMIT 50";
        
        return $this->db->fetchAll($sql, [$proyectoId]);
    }
    
    /**
     * Validar permisos del usuario sobre el proyecto
     */
    public function validarPermisos($proyectoId, $usuarioId, $accion) {
        $proyecto = $this->obtenerPorId($proyectoId);
        
        if (!$proyecto) {
            return false;
        }
        
        // El creador y responsable tienen todos los permisos
        if ($proyecto['creado_por'] == $usuarioId || $proyecto['responsable_id'] == $usuarioId) {
            return true;
        }
        
        // Verificar permisos específicos según el área del usuario
        // Esto se puede expandir según las reglas de negocio
        return false;
    }
    
    /**
     * Exportar datos del proyecto
     */
    public function exportarDatos($proyectoId) {
        $proyecto = $this->obtenerPorId($proyectoId);
        if (!$proyecto) {
            throw new Exception('Proyecto no encontrado');
        }
        
        $tareas = $this->obtenerTareas($proyectoId);
        $estadisticas = $this->obtenerEstadisticas($proyectoId);
        
        return [
            'proyecto' => $proyecto,
            'tareas' => $tareas,
            'estadisticas' => $estadisticas,
            'progreso' => $this->calcularProgreso($proyectoId),
            'fecha_exportacion' => date('Y-m-d H:i:s')
        ];
    }
}
?>