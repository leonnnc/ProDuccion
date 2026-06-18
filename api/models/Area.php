<?php
/**
 * Modelo Area
 * Gestión de áreas y permisos del sistema
 */

require_once __DIR__ . '/../config/database.php';

class Area {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear nueva área
     */
    public function crear($datos) {
        // Validar datos requeridos
        $this->validarDatos($datos, ['nombre']);
        
        // Verificar que el nombre no exista
        if ($this->existeNombre($datos['nombre'])) {
            throw new Exception('Ya existe un área con ese nombre');
        }
        
        // Validar y procesar permisos
        $permisos = $this->procesarPermisos($datos['permisos'] ?? []);
        
        $sql = "INSERT INTO areas (nombre, descripcion, permisos, activa) 
                VALUES (?, ?, ?, ?)";
        
        $params = [
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            json_encode($permisos),
            $datos['activa'] ?? true
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Obtener área por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM areas WHERE id = ? AND activa = 1";
        $area = $this->db->fetch($sql, [$id]);
        
        if ($area) {
            // Decodificar permisos JSON
            $area['permisos'] = json_decode($area['permisos'], true) ?? [];
        }
        
        return $area;
    }
    
    /**
     * Obtener todas las áreas activas
     */
    public function obtenerTodas($incluirInactivas = false) {
        $sql = "SELECT * FROM areas";
        
        if (!$incluirInactivas) {
            $sql .= " WHERE activa = 1";
        }
        
        $sql .= " ORDER BY nombre ASC";
        
        $areas = $this->db->fetchAll($sql);
        
        // Decodificar permisos para cada área
        foreach ($areas as &$area) {
            $area['permisos'] = json_decode($area['permisos'], true) ?? [];
        }
        
        return $areas;
    }
    
    /**
     * Actualizar área
     */
    public function actualizar($id, $datos) {
        // Verificar que el área existe
        $areaExistente = $this->obtenerPorId($id);
        if (!$areaExistente) {
            throw new Exception('Área no encontrada');
        }
        
        // Construir query dinámicamente
        $campos = [];
        $params = [];
        
        if (isset($datos['nombre'])) {
            // Verificar que el nombre no esté en uso por otra área
            if ($this->existeNombre($datos['nombre'], $id)) {
                throw new Exception('Ya existe otra área con ese nombre');
            }
            $campos[] = "nombre = ?";
            $params[] = $datos['nombre'];
        }
        
        if (isset($datos['descripcion'])) {
            $campos[] = "descripcion = ?";
            $params[] = $datos['descripcion'];
        }
        
        if (isset($datos['permisos'])) {
            $permisos = $this->procesarPermisos($datos['permisos']);
            $campos[] = "permisos = ?";
            $params[] = json_encode($permisos);
        }
        
        if (isset($datos['activa'])) {
            $campos[] = "activa = ?";
            $params[] = $datos['activa'] ? 1 : 0;
        }
        
        if (empty($campos)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE areas SET " . implode(', ', $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Eliminar área (soft delete)
     */
    public function eliminar($id) {
        // Verificar que no hay usuarios asignados a esta área
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE area_id = ? AND activo = 1";
        $resultado = $this->db->fetch($sql, [$id]);
        
        if ($resultado['total'] > 0) {
            throw new Exception('No se puede eliminar el área porque tiene usuarios asignados');
        }
        
        $sql = "UPDATE areas SET activa = 0 WHERE id = ?";
        return $this->db->update($sql, [$id]);
    }
    
    /**
     * Obtener permisos disponibles del sistema
     */
    public function obtenerPermisosDisponibles() {
        return [
            'proyectos' => [
                'crear' => 'Crear proyectos',
                'editar' => 'Editar proyectos',
                'eliminar' => 'Eliminar proyectos',
                'ver' => 'Ver proyectos'
            ],
            'tareas' => [
                'crear' => 'Crear tareas',
                'editar' => 'Editar tareas',
                'eliminar' => 'Eliminar tareas',
                'ver' => 'Ver tareas',
                'asignar' => 'Asignar tareas a usuarios'
            ],
            'agenda' => [
                'crear' => 'Crear eventos en agenda',
                'editar' => 'Editar eventos de agenda',
                'eliminar' => 'Eliminar eventos de agenda',
                'ver' => 'Ver agenda'
            ],
            'usuarios' => [
                'crear' => 'Crear usuarios',
                'editar' => 'Editar usuarios',
                'eliminar' => 'Eliminar usuarios',
                'ver' => 'Ver usuarios'
            ],
            'reportes' => [
                'ver' => 'Ver reportes',
                'exportar' => 'Exportar reportes'
            ]
        ];
    }
    
    /**
     * Verificar si un área tiene un permiso específico
     */
    public function tienePermiso($areaId, $modulo, $accion) {
        $area = $this->obtenerPorId($areaId);
        
        if (!$area || !$area['permisos']) {
            return false;
        }
        
        return isset($area['permisos'][$modulo]) && in_array($accion, $area['permisos'][$modulo]);
    }
    
    /**
     * Obtener usuarios por área
     */
    public function obtenerUsuarios($areaId) {
        $sql = "SELECT id, nombre, email, activo, ultimo_acceso, fecha_creacion 
                FROM usuarios 
                WHERE area_id = ? AND activo = 1 
                ORDER BY nombre ASC";
        
        return $this->db->fetchAll($sql, [$areaId]);
    }
    
    /**
     * Clonar permisos de otra área
     */
    public function clonarPermisos($areaOrigenId, $areaDestinoId) {
        $areaOrigen = $this->obtenerPorId($areaOrigenId);
        $areaDestino = $this->obtenerPorId($areaDestinoId);
        
        if (!$areaOrigen || !$areaDestino) {
            throw new Exception('Una o ambas áreas no existen');
        }
        
        $sql = "UPDATE areas SET permisos = ? WHERE id = ?";
        return $this->db->update($sql, [
            json_encode($areaOrigen['permisos']),
            $areaDestinoId
        ]);
    }
    
    /**
     * Obtener estadísticas de áreas
     */
    public function obtenerEstadisticas() {
        $sql = "SELECT 
                    a.id,
                    a.nombre,
                    a.descripcion,
                    COUNT(u.id) as total_usuarios,
                    COUNT(CASE WHEN u.activo = 1 THEN 1 END) as usuarios_activos,
                    COUNT(CASE WHEN u.ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as usuarios_activos_mes
                FROM areas a
                LEFT JOIN usuarios u ON a.id = u.area_id
                WHERE a.activa = 1
                GROUP BY a.id, a.nombre, a.descripcion
                ORDER BY a.nombre ASC";
        
        return $this->db->fetchAll($sql);
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
            if (strlen($datos['nombre']) < 2) {
                throw new Exception('El nombre debe tener al menos 2 caracteres');
            }
            if (strlen($datos['nombre']) > 50) {
                throw new Exception('El nombre no puede tener más de 50 caracteres');
            }
        }
    }
    
    /**
     * Procesar y validar permisos
     */
    private function procesarPermisos($permisos) {
        if (!is_array($permisos)) {
            return [];
        }
        
        $permisosDisponibles = $this->obtenerPermisosDisponibles();
        $permisosValidos = [];
        
        foreach ($permisos as $modulo => $acciones) {
            if (!isset($permisosDisponibles[$modulo])) {
                continue; // Ignorar módulos no válidos
            }
            
            if (!is_array($acciones)) {
                continue;
            }
            
            $accionesValidas = [];
            foreach ($acciones as $accion) {
                if (isset($permisosDisponibles[$modulo][$accion])) {
                    $accionesValidas[] = $accion;
                }
            }
            
            if (!empty($accionesValidas)) {
                $permisosValidos[$modulo] = array_unique($accionesValidas);
            }
        }
        
        return $permisosValidos;
    }
    
    /**
     * Verificar si existe nombre
     */
    private function existeNombre($nombre, $excluirId = null) {
        $sql = "SELECT COUNT(*) FROM areas WHERE nombre = ? AND activa = 1";
        $params = [$nombre];
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        return $this->db->fetch($sql, $params)['COUNT(*)'] > 0;
    }
    
    /**
     * Crear áreas predeterminadas del sistema
     */
    public function crearAreasPredeterminadas() {
        $areasPredeterminadas = [
            // GRUPOS PRINCIPALES
            [
                'nombre' => 'Admin',
                'descripcion' => 'Administradores del sistema con acceso completo',
                'permisos' => [
                    'proyectos' => ['crear', 'editar', 'eliminar', 'ver'],
                    'tareas' => ['crear', 'editar', 'eliminar', 'ver', 'asignar'],
                    'agenda' => ['crear', 'editar', 'eliminar', 'ver'],
                    'usuarios' => ['crear', 'editar', 'eliminar', 'ver'],
                    'reportes' => ['ver', 'exportar'],
                    'configuracion' => ['ver', 'editar']
                ]
            ],
            [
                'nombre' => 'Staff',
                'descripcion' => 'Personal de staff con permisos de gestión',
                'permisos' => [
                    'proyectos' => ['crear', 'editar', 'ver'],
                    'tareas' => ['crear', 'editar', 'ver', 'asignar'],
                    'agenda' => ['crear', 'editar', 'ver'],
                    'usuarios' => ['ver'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Users',
                'descripcion' => 'Usuarios regulares con permisos básicos',
                'permisos' => [
                    'proyectos' => ['ver'],
                    'tareas' => ['editar', 'ver'],
                    'agenda' => ['ver'],
                    'reportes' => ['ver']
                ]
            ],
            
            // ÁREAS ESPECÍFICAS DE PRODUCCIÓN AUDIOVISUAL
            [
                'nombre' => 'Visuales',
                'descripcion' => 'Equipo de efectos visuales y gráficos',
                'permisos' => [
                    'proyectos' => ['ver', 'editar'],
                    'tareas' => ['crear', 'editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Filmmakers',
                'descripcion' => 'Directores y productores de contenido',
                'permisos' => [
                    'proyectos' => ['crear', 'editar', 'ver'],
                    'tareas' => ['crear', 'editar', 'ver', 'asignar'],
                    'agenda' => ['crear', 'editar', 'ver'],
                    'reportes' => ['ver', 'exportar']
                ]
            ],
            [
                'nombre' => 'Fotografía',
                'descripcion' => 'Equipo de fotografía y captura de imagen',
                'permisos' => [
                    'proyectos' => ['ver', 'editar'],
                    'tareas' => ['crear', 'editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Coordinación',
                'descripcion' => 'Coordinadores de producción y logística',
                'permisos' => [
                    'proyectos' => ['crear', 'editar', 'ver'],
                    'tareas' => ['crear', 'editar', 'ver', 'asignar'],
                    'agenda' => ['crear', 'editar', 'ver'],
                    'usuarios' => ['ver'],
                    'reportes' => ['ver', 'exportar']
                ]
            ],
            [
                'nombre' => 'Switchers',
                'descripcion' => 'Operadores de switcher y mezcla de video',
                'permisos' => [
                    'proyectos' => ['ver'],
                    'tareas' => ['editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Streaming',
                'descripcion' => 'Equipo de transmisión en vivo y streaming',
                'permisos' => [
                    'proyectos' => ['ver', 'editar'],
                    'tareas' => ['crear', 'editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Luces',
                'descripcion' => 'Técnicos de iluminación y ambientación',
                'permisos' => [
                    'proyectos' => ['ver'],
                    'tareas' => ['editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Diseño',
                'descripcion' => 'Diseñadores gráficos y creativos',
                'permisos' => [
                    'proyectos' => ['ver', 'editar'],
                    'tareas' => ['crear', 'editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Edición',
                'descripcion' => 'Editores de video y postproducción',
                'permisos' => [
                    'proyectos' => ['ver', 'editar'],
                    'tareas' => ['crear', 'editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Protocolos',
                'descripcion' => 'Encargados de protocolos y ceremonial',
                'permisos' => [
                    'proyectos' => ['ver', 'editar'],
                    'tareas' => ['crear', 'editar', 'ver'],
                    'agenda' => ['crear', 'editar', 'ver'],
                    'reportes' => ['ver']
                ]
            ],
            [
                'nombre' => 'Cámaras',
                'descripcion' => 'Camarógrafos y operadores de cámara',
                'permisos' => [
                    'proyectos' => ['ver'],
                    'tareas' => ['editar', 'ver'],
                    'agenda' => ['ver', 'editar'],
                    'reportes' => ['ver']
                ]
            ]
        ];
        
        $creadas = 0;
        foreach ($areasPredeterminadas as $area) {
            try {
                if (!$this->existeNombre($area['nombre'])) {
                    $this->crear($area);
                    $creadas++;
                }
            } catch (Exception $e) {
                // Continuar con la siguiente área si hay error
                continue;
            }
        }
        
        return $creadas;
    }
}
?>