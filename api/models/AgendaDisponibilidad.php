<?php
/**
 * Modelo AgendaDisponibilidad
 * Gestión de disponibilidad de agenda mensual
 */

require_once __DIR__ . '/../config/database.php';

class AgendaDisponibilidad {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear nueva disponibilidad
     */
    public function crear($datos, $creadoPor) {
        // Validar datos requeridos
        $this->validarDatos($datos, ['fecha', 'segmento']);
        
        // Validar fecha
        $this->validarFecha($datos['fecha']);
        
        // Validar segmento
        $this->validarSegmento($datos['segmento']);
        
        // Verificar si ya existe disponibilidad para esa fecha y segmento
        if ($this->existeDisponibilidad($datos['fecha'], $datos['segmento'])) {
            throw new Exception('Ya existe disponibilidad para esa fecha y segmento');
        }
        
        $sql = "INSERT INTO agenda_disponibilidad (fecha, segmento, disponible, notas, creado_por) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $datos['fecha'],
            $datos['segmento'],
            $datos['disponible'] ?? true,
            $datos['notas'] ?? null,
            $creadoPor
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Obtener disponibilidad por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT ad.*, 
                       u.nombre as creado_por_nombre
                FROM agenda_disponibilidad ad 
                LEFT JOIN usuarios u ON ad.creado_por = u.id
                WHERE ad.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Obtener disponibilidad por fecha y segmento
     */
    public function obtenerPorFechaSegmento($fecha, $segmento) {
        $sql = "SELECT ad.*, 
                       u.nombre as creado_por_nombre
                FROM agenda_disponibilidad ad 
                LEFT JOIN usuarios u ON ad.creado_por = u.id
                WHERE ad.fecha = ? AND ad.segmento = ?";
        
        return $this->db->fetch($sql, [$fecha, $segmento]);
    }
    
    /**
     * Obtener disponibilidad mensual
     */
    public function obtenerMensual($año, $mes) {
        $sql = "SELECT ad.*, 
                       u.nombre as creado_por_nombre
                FROM agenda_disponibilidad ad 
                LEFT JOIN usuarios u ON ad.creado_por = u.id
                WHERE YEAR(ad.fecha) = ? AND MONTH(ad.fecha) = ?
                ORDER BY ad.fecha ASC, ad.segmento ASC";
        
        return $this->db->fetchAll($sql, [$año, $mes]);
    }
    
    /**
     * Obtener disponibilidad por rango de fechas
     */
    public function obtenerPorRango($fechaInicio, $fechaFin, $filtros = []) {
        $sql = "SELECT ad.*, 
                       u.nombre as creado_por_nombre
                FROM agenda_disponibilidad ad 
                LEFT JOIN usuarios u ON ad.creado_por = u.id
                WHERE ad.fecha BETWEEN ? AND ?";
        
        $params = [$fechaInicio, $fechaFin];
        
        // Aplicar filtros
        if (!empty($filtros['segmento'])) {
            $sql .= " AND ad.segmento = ?";
            $params[] = $filtros['segmento'];
        }
        
        if (isset($filtros['disponible'])) {
            $sql .= " AND ad.disponible = ?";
            $params[] = $filtros['disponible'] ? 1 : 0;
        }
        
        if (!empty($filtros['creado_por'])) {
            $sql .= " AND ad.creado_por = ?";
            $params[] = $filtros['creado_por'];
        }
        
        $sql .= " ORDER BY ad.fecha ASC, ad.segmento ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Actualizar disponibilidad
     */
    public function actualizar($id, $datos) {
        // Verificar que la disponibilidad existe
        $disponibilidadExistente = $this->obtenerPorId($id);
        if (!$disponibilidadExistente) {
            throw new Exception('Disponibilidad no encontrada');
        }
        
        // Construir query dinámicamente
        $campos = [];
        $params = [];
        
        if (isset($datos['fecha'])) {
            $this->validarFecha($datos['fecha']);
            
            // Verificar que no exista otra disponibilidad para la nueva fecha y segmento
            $segmentoActual = $datos['segmento'] ?? $disponibilidadExistente['segmento'];
            if ($this->existeDisponibilidad($datos['fecha'], $segmentoActual, $id)) {
                throw new Exception('Ya existe disponibilidad para esa fecha y segmento');
            }
            
            $campos[] = "fecha = ?";
            $params[] = $datos['fecha'];
        }
        
        if (isset($datos['segmento'])) {
            $this->validarSegmento($datos['segmento']);
            
            // Verificar que no exista otra disponibilidad para la fecha y nuevo segmento
            $fechaActual = $datos['fecha'] ?? $disponibilidadExistente['fecha'];
            if ($this->existeDisponibilidad($fechaActual, $datos['segmento'], $id)) {
                throw new Exception('Ya existe disponibilidad para esa fecha y segmento');
            }
            
            $campos[] = "segmento = ?";
            $params[] = $datos['segmento'];
        }
        
        if (isset($datos['disponible'])) {
            $campos[] = "disponible = ?";
            $params[] = $datos['disponible'] ? 1 : 0;
        }
        
        if (isset($datos['notas'])) {
            $campos[] = "notas = ?";
            $params[] = $datos['notas'];
        }
        
        if (empty($campos)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE agenda_disponibilidad SET " . implode(', ', $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Eliminar disponibilidad
     */
    public function eliminar($id) {
        // Verificar que la disponibilidad existe
        $disponibilidad = $this->obtenerPorId($id);
        if (!$disponibilidad) {
            throw new Exception('Disponibilidad no encontrada');
        }
        
        $sql = "DELETE FROM agenda_disponibilidad WHERE id = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    /**
     * Establecer disponibilidad múltiple
     */
    public function establecerMultiple($fechas, $segmento, $disponible, $notas, $creadoPor) {
        $this->validarSegmento($segmento);
        
        $this->db->beginTransaction();
        
        try {
            $resultados = [];
            
            foreach ($fechas as $fecha) {
                $this->validarFecha($fecha);
                
                // Verificar si ya existe
                $existente = $this->obtenerPorFechaSegmento($fecha, $segmento);
                
                if ($existente) {
                    // Actualizar existente
                    $this->actualizar($existente['id'], [
                        'disponible' => $disponible,
                        'notas' => $notas
                    ]);
                    $resultados[] = ['fecha' => $fecha, 'accion' => 'actualizado', 'id' => $existente['id']];
                } else {
                    // Crear nuevo
                    $id = $this->crear([
                        'fecha' => $fecha,
                        'segmento' => $segmento,
                        'disponible' => $disponible,
                        'notas' => $notas
                    ], $creadoPor);
                    $resultados[] = ['fecha' => $fecha, 'accion' => 'creado', 'id' => $id];
                }
            }
            
            $this->db->commit();
            return $resultados;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtener calendario mensual estructurado
     */
    public function obtenerCalendarioMensual($año, $mes) {
        // Obtener disponibilidades del mes
        $disponibilidades = $this->obtenerMensual($año, $mes);
        
        // Crear estructura de calendario
        $calendario = [];
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $año);
        
        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $año, $mes, $dia);
            $calendario[$fecha] = [
                'fecha' => $fecha,
                'dia' => $dia,
                'segmentos' => [
                    'proyectos' => null,
                    'tareas' => null,
                    'agenda' => null
                ]
            ];
        }
        
        // Llenar con disponibilidades existentes
        foreach ($disponibilidades as $disp) {
            if (isset($calendario[$disp['fecha']])) {
                $calendario[$disp['fecha']]['segmentos'][$disp['segmento']] = $disp;
            }
        }
        
        return array_values($calendario);
    }
    
    /**
     * Obtener estadísticas de disponibilidad
     */
    public function obtenerEstadisticas($año = null, $mes = null) {
        $sql = "SELECT 
                    segmento,
                    COUNT(*) as total_dias,
                    COUNT(CASE WHEN disponible = 1 THEN 1 END) as dias_disponibles,
                    COUNT(CASE WHEN disponible = 0 THEN 1 END) as dias_no_disponibles
                FROM agenda_disponibilidad";
        
        $params = [];
        
        if ($año && $mes) {
            $sql .= " WHERE YEAR(fecha) = ? AND MONTH(fecha) = ?";
            $params = [$año, $mes];
        } elseif ($año) {
            $sql .= " WHERE YEAR(fecha) = ?";
            $params = [$año];
        }
        
        $sql .= " GROUP BY segmento ORDER BY segmento";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obtener días disponibles por segmento
     */
    public function obtenerDiasDisponibles($segmento, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT fecha, notas 
                FROM agenda_disponibilidad 
                WHERE segmento = ? AND disponible = 1";
        
        $params = [$segmento];
        
        if ($fechaInicio) {
            $sql .= " AND fecha >= ?";
            $params[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $sql .= " AND fecha <= ?";
            $params[] = $fechaFin;
        }
        
        $sql .= " ORDER BY fecha ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Buscar disponibilidad por notas
     */
    public function buscarPorNotas($texto, $limite = 10) {
        $sql = "SELECT ad.*, 
                       u.nombre as creado_por_nombre
                FROM agenda_disponibilidad ad
                LEFT JOIN usuarios u ON ad.creado_por = u.id
                WHERE ad.notas LIKE ?
                ORDER BY ad.fecha DESC
                LIMIT ?";
        
        $buscar = '%' . $texto . '%';
        return $this->db->fetchAll($sql, [$buscar, $limite]);
    }
    
    /**
     * Clonar disponibilidad de un mes a otro
     */
    public function clonarMes($añoOrigen, $mesOrigen, $añoDestino, $mesDestino, $creadoPor) {
        // Obtener disponibilidades del mes origen
        $disponibilidadesOrigen = $this->obtenerMensual($añoOrigen, $mesOrigen);
        
        if (empty($disponibilidadesOrigen)) {
            throw new Exception('No hay disponibilidades en el mes origen');
        }
        
        $this->db->beginTransaction();
        
        try {
            $clonadas = 0;
            
            foreach ($disponibilidadesOrigen as $disp) {
                // Calcular nueva fecha
                $fechaOrigen = new DateTime($disp['fecha']);
                $diaOrigen = $fechaOrigen->format('d');
                
                // Verificar que el día existe en el mes destino
                $diasEnMesDestino = cal_days_in_month(CAL_GREGORIAN, $mesDestino, $añoDestino);
                if ($diaOrigen > $diasEnMesDestino) {
                    continue; // Saltar días que no existen en el mes destino
                }
                
                $fechaDestino = sprintf('%04d-%02d-%02d', $añoDestino, $mesDestino, $diaOrigen);
                
                // Verificar si ya existe
                if (!$this->existeDisponibilidad($fechaDestino, $disp['segmento'])) {
                    $this->crear([
                        'fecha' => $fechaDestino,
                        'segmento' => $disp['segmento'],
                        'disponible' => $disp['disponible'],
                        'notas' => $disp['notas']
                    ], $creadoPor);
                    $clonadas++;
                }
            }
            
            $this->db->commit();
            return $clonadas;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
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
    
    /**
     * Validar fecha
     */
    private function validarFecha($fecha) {
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
            throw new Exception('Formato de fecha inválido. Use YYYY-MM-DD');
        }
        
        // Opcional: validar que no sea muy antigua
        $fechaMinima = new DateTime('-1 year');
        if ($fechaObj < $fechaMinima) {
            throw new Exception('La fecha no puede ser anterior a un año');
        }
    }
    
    /**
     * Validar segmento
     */
    private function validarSegmento($segmento) {
        $segmentosValidos = ['proyectos', 'tareas', 'agenda'];
        if (!in_array($segmento, $segmentosValidos)) {
            throw new Exception('Segmento inválido. Debe ser: proyectos, tareas o agenda');
        }
    }
    
    /**
     * Verificar si existe disponibilidad
     */
    private function existeDisponibilidad($fecha, $segmento, $excluirId = null) {
        $sql = "SELECT COUNT(*) FROM agenda_disponibilidad WHERE fecha = ? AND segmento = ?";
        $params = [$fecha, $segmento];
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        return $this->db->fetch($sql, $params)['COUNT(*)'] > 0;
    }
    
    /**
     * Obtener segmentos disponibles
     */
    public function obtenerSegmentosDisponibles() {
        return [
            'proyectos' => 'Proyectos',
            'tareas' => 'Tareas',
            'agenda' => 'Agenda'
        ];
    }
    
    /**
     * Limpiar disponibilidades antiguas
     */
    public function limpiarAntiguas($mesesAtras = 12) {
        $fechaLimite = date('Y-m-d', strtotime("-{$mesesAtras} months"));
        
        $sql = "DELETE FROM agenda_disponibilidad WHERE fecha < ?";
        return $this->db->delete($sql, [$fechaLimite]);
    }
    
    /**
     * Obtener resumen de disponibilidad por día de la semana
     */
    public function obtenerResumenSemanal($año = null, $mes = null) {
        $sql = "SELECT 
                    DAYOFWEEK(fecha) as dia_semana,
                    DAYNAME(fecha) as nombre_dia,
                    segmento,
                    COUNT(*) as total,
                    COUNT(CASE WHEN disponible = 1 THEN 1 END) as disponibles
                FROM agenda_disponibilidad";
        
        $params = [];
        
        if ($año && $mes) {
            $sql .= " WHERE YEAR(fecha) = ? AND MONTH(fecha) = ?";
            $params = [$año, $mes];
        } elseif ($año) {
            $sql .= " WHERE YEAR(fecha) = ?";
            $params = [$año];
        }
        
        $sql .= " GROUP BY DAYOFWEEK(fecha), segmento 
                  ORDER BY DAYOFWEEK(fecha), segmento";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Exportar disponibilidades
     */
    public function exportarDatos($filtros = []) {
        $sql = "SELECT ad.*, 
                       u.nombre as creado_por_nombre
                FROM agenda_disponibilidad ad 
                LEFT JOIN usuarios u ON ad.creado_por = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND ad.fecha >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND ad.fecha <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        
        if (!empty($filtros['segmento'])) {
            $sql .= " AND ad.segmento = ?";
            $params[] = $filtros['segmento'];
        }
        
        if (isset($filtros['disponible'])) {
            $sql .= " AND ad.disponible = ?";
            $params[] = $filtros['disponible'] ? 1 : 0;
        }
        
        $sql .= " ORDER BY ad.fecha ASC, ad.segmento ASC";
        
        $disponibilidades = $this->db->fetchAll($sql, $params);
        
        return [
            'disponibilidades' => $disponibilidades,
            'estadisticas' => $this->obtenerEstadisticas(),
            'fecha_exportacion' => date('Y-m-d H:i:s'),
            'filtros_aplicados' => $filtros
        ];
    }
    
    /**
     * Importar disponibilidades desde array
     */
    public function importarDatos($datos, $creadoPor, $opciones = []) {
        $sobreescribir = $opciones['sobreescribir'] ?? false;
        $validarFechas = $opciones['validar_fechas'] ?? true;
        
        $this->db->beginTransaction();
        
        try {
            $importadas = 0;
            $actualizadas = 0;
            $errores = [];
            
            foreach ($datos as $index => $item) {
                try {
                    // Validar datos requeridos
                    if (!isset($item['fecha']) || !isset($item['segmento'])) {
                        $errores[] = "Fila {$index}: Faltan campos requeridos (fecha, segmento)";
                        continue;
                    }
                    
                    if ($validarFechas) {
                        $this->validarFecha($item['fecha']);
                    }
                    
                    $this->validarSegmento($item['segmento']);
                    
                    // Verificar si existe
                    $existente = $this->obtenerPorFechaSegmento($item['fecha'], $item['segmento']);
                    
                    if ($existente) {
                        if ($sobreescribir) {
                            $this->actualizar($existente['id'], [
                                'disponible' => $item['disponible'] ?? true,
                                'notas' => $item['notas'] ?? null
                            ]);
                            $actualizadas++;
                        }
                    } else {
                        $this->crear([
                            'fecha' => $item['fecha'],
                            'segmento' => $item['segmento'],
                            'disponible' => $item['disponible'] ?? true,
                            'notas' => $item['notas'] ?? null
                        ], $creadoPor);
                        $importadas++;
                    }
                    
                } catch (Exception $e) {
                    $errores[] = "Fila {$index}: " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            return [
                'importadas' => $importadas,
                'actualizadas' => $actualizadas,
                'errores' => $errores,
                'total_procesadas' => $importadas + $actualizadas
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Generar plantilla de disponibilidad para un mes
     */
    public function generarPlantillaMes($año, $mes, $segmentos = null, $disponiblePorDefecto = true) {
        if ($segmentos === null) {
            $segmentos = array_keys($this->obtenerSegmentosDisponibles());
        }
        
        $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $mes, $año);
        $plantilla = [];
        
        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $año, $mes, $dia);
            
            foreach ($segmentos as $segmento) {
                // Solo agregar si no existe ya
                if (!$this->existeDisponibilidad($fecha, $segmento)) {
                    $plantilla[] = [
                        'fecha' => $fecha,
                        'segmento' => $segmento,
                        'disponible' => $disponiblePorDefecto,
                        'notas' => null
                    ];
                }
            }
        }
        
        return $plantilla;
    }
    
    /**
     * Aplicar plantilla de disponibilidad
     */
    public function aplicarPlantilla($plantilla, $creadoPor) {
        $this->db->beginTransaction();
        
        try {
            $aplicadas = 0;
            
            foreach ($plantilla as $item) {
                if (!$this->existeDisponibilidad($item['fecha'], $item['segmento'])) {
                    $this->crear($item, $creadoPor);
                    $aplicadas++;
                }
            }
            
            $this->db->commit();
            return $aplicadas;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Obtener conflictos de disponibilidad
     */
    public function obtenerConflictos($fechaInicio, $fechaFin) {
        $sql = "SELECT 
                    fecha,
                    COUNT(*) as total_segmentos,
                    COUNT(CASE WHEN disponible = 0 THEN 1 END) as segmentos_no_disponibles,
                    GROUP_CONCAT(
                        CASE WHEN disponible = 0 
                        THEN segmento 
                        END
                    ) as segmentos_conflicto
                FROM agenda_disponibilidad 
                WHERE fecha BETWEEN ? AND ?
                GROUP BY fecha
                HAVING segmentos_no_disponibles > 0
                ORDER BY fecha ASC";
        
        return $this->db->fetchAll($sql, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Obtener días con mayor disponibilidad
     */
    public function obtenerDiasMayorDisponibilidad($fechaInicio, $fechaFin, $limite = 10) {
        $sql = "SELECT 
                    fecha,
                    COUNT(*) as total_segmentos,
                    COUNT(CASE WHEN disponible = 1 THEN 1 END) as segmentos_disponibles,
                    ROUND(
                        (COUNT(CASE WHEN disponible = 1 THEN 1 END) / COUNT(*)) * 100, 2
                    ) as porcentaje_disponibilidad
                FROM agenda_disponibilidad 
                WHERE fecha BETWEEN ? AND ?
                GROUP BY fecha
                ORDER BY porcentaje_disponibilidad DESC, segmentos_disponibles DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$fechaInicio, $fechaFin, $limite]);
    }
    
    /**
     * Sincronizar con eventos externos (opcional)
     */
    public function sincronizarEventos($eventos, $creadoPor) {
        $this->db->beginTransaction();
        
        try {
            $sincronizados = 0;
            
            foreach ($eventos as $evento) {
                // Marcar como no disponible los días del evento
                $fechaInicio = new DateTime($evento['fecha_inicio']);
                $fechaFin = new DateTime($evento['fecha_fin']);
                
                while ($fechaInicio <= $fechaFin) {
                    $fecha = $fechaInicio->format('Y-m-d');
                    $segmento = $evento['segmento'] ?? 'agenda';
                    
                    $existente = $this->obtenerPorFechaSegmento($fecha, $segmento);
                    
                    if ($existente) {
                        $this->actualizar($existente['id'], [
                            'disponible' => false,
                            'notas' => $evento['titulo'] ?? 'Evento sincronizado'
                        ]);
                    } else {
                        $this->crear([
                            'fecha' => $fecha,
                            'segmento' => $segmento,
                            'disponible' => false,
                            'notas' => $evento['titulo'] ?? 'Evento sincronizado'
                        ], $creadoPor);
                    }
                    
                    $sincronizados++;
                    $fechaInicio->add(new DateInterval('P1D'));
                }
            }
            
            $this->db->commit();
            return $sincronizados;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
?>