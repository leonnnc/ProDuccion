<?php
/**
 * Controlador de Calendario/Agenda
 * Maneja las operaciones de disponibilidad mensual
 */

require_once __DIR__ . '/../models/AgendaDisponibilidad.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/AuthController.php';

class CalendarController {
    private $agendaModel;
    private $authController;
    
    public function __construct() {
        $this->agendaModel = new AgendaDisponibilidad();
        $this->authController = new AuthController();
    }
    
    /**
     * Obtener calendario mensual
     */
    public function getMonthly($year, $month) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para ver la agenda');
                return;
            }
            
            // Validar parámetros
            if (!$this->validarAño($year) || !$this->validarMes($month)) {
                Response::badRequest('Año o mes inválido');
                return;
            }
            
            // Obtener calendario estructurado
            $calendario = $this->agendaModel->obtenerCalendarioMensual($year, $month);
            
            // Obtener estadísticas del mes
            $estadisticas = $this->agendaModel->obtenerEstadisticas($year, $month);
            
            Response::success([
                'calendario' => $calendario,
                'estadisticas' => $estadisticas,
                'año' => (int)$year,
                'mes' => (int)$month,
                'nombre_mes' => $this->getNombreMes($month)
            ], 'Calendario obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener disponibilidad por rango de fechas
     */
    public function getByRange() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para ver la agenda');
                return;
            }
            
            // Obtener parámetros
            $fechaInicio = $_GET['fecha_inicio'] ?? '';
            $fechaFin = $_GET['fecha_fin'] ?? '';
            
            if (empty($fechaInicio) || empty($fechaFin)) {
                Response::badRequest('Fecha de inicio y fin son requeridas');
                return;
            }
            
            // Filtros opcionales
            $filtros = [
                'segmento' => $_GET['segmento'] ?? '',
                'disponible' => isset($_GET['disponible']) ? (bool)$_GET['disponible'] : null,
                'creado_por' => $_GET['creado_por'] ?? ''
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, function($value) {
                return $value !== '' && $value !== null;
            });
            
            $disponibilidades = $this->agendaModel->obtenerPorRango($fechaInicio, $fechaFin, $filtros);
            
            Response::success($disponibilidades, 'Disponibilidades obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Crear nueva disponibilidad
     */
    public function store() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'crear')) {
                Response::forbidden('No tienes permisos para crear disponibilidad');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Datos inválidos');
                return;
            }
            
            // Validar campos requeridos
            $errors = Response::validateInput($input, [
                'fecha' => ['required' => true, 'type' => 'date'],
                'segmento' => ['required' => true, 'in' => ['proyectos', 'tareas', 'agenda']]
            ]);
            
            if (!empty($errors)) {
                Response::validation($errors);
                return;
            }
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Crear disponibilidad
            $disponibilidadId = $this->agendaModel->crear($input, $usuarioActual['id']);
            
            // Obtener disponibilidad creada
            $disponibilidad = $this->agendaModel->obtenerPorId($disponibilidadId);
            
            Response::success($disponibilidad, 'Disponibilidad creada correctamente', 201);
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Establecer disponibilidad múltiple
     */
    public function setMultiple() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'crear')) {
                Response::forbidden('No tienes permisos para establecer disponibilidad');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Datos inválidos');
                return;
            }
            
            // Validar campos requeridos
            if (empty($input['fechas']) || !is_array($input['fechas'])) {
                Response::badRequest('Array de fechas requerido');
                return;
            }
            
            if (empty($input['segmento'])) {
                Response::badRequest('Segmento requerido');
                return;
            }
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Establecer disponibilidad múltiple
            $resultados = $this->agendaModel->establecerMultiple(
                $input['fechas'],
                $input['segmento'],
                $input['disponible'] ?? true,
                $input['notas'] ?? null,
                $usuarioActual['id']
            );
            
            Response::success($resultados, 'Disponibilidad establecida correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Actualizar disponibilidad
     */
    public function update($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'editar')) {
                Response::forbidden('No tienes permisos para editar disponibilidad');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Datos inválidos');
                return;
            }
            
            // Validar campos si están presentes
            $validationRules = [];
            if (isset($input['fecha'])) {
                $validationRules['fecha'] = ['type' => 'date'];
            }
            if (isset($input['segmento'])) {
                $validationRules['segmento'] = ['in' => ['proyectos', 'tareas', 'agenda']];
            }
            
            if (!empty($validationRules)) {
                $errors = Response::validateInput($input, $validationRules);
                if (!empty($errors)) {
                    Response::validation($errors);
                    return;
                }
            }
            
            // Actualizar disponibilidad
            $filasAfectadas = $this->agendaModel->actualizar($id, $input);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Disponibilidad no encontrada');
                return;
            }
            
            // Obtener disponibilidad actualizada
            $disponibilidad = $this->agendaModel->obtenerPorId($id);
            
            Response::success($disponibilidad, 'Disponibilidad actualizada correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Eliminar disponibilidad
     */
    public function delete($id) {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'eliminar')) {
                Response::forbidden('No tienes permisos para eliminar disponibilidad');
                return;
            }
            
            // Eliminar disponibilidad
            $filasAfectadas = $this->agendaModel->eliminar($id);
            
            if ($filasAfectadas === 0) {
                Response::notFound('Disponibilidad no encontrada');
                return;
            }
            
            Response::success(null, 'Disponibilidad eliminada correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener disponibilidad por fecha y segmento
     */
    public function getByDateSegment($fecha, $segmento) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para ver la agenda');
                return;
            }
            
            $disponibilidad = $this->agendaModel->obtenerPorFechaSegmento($fecha, $segmento);
            
            if (!$disponibilidad) {
                Response::notFound('Disponibilidad no encontrada');
                return;
            }
            
            Response::success($disponibilidad, 'Disponibilidad obtenida correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener días disponibles por segmento
     */
    public function getAvailableDays($segmento) {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para ver la agenda');
                return;
            }
            
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            
            $diasDisponibles = $this->agendaModel->obtenerDiasDisponibles($segmento, $fechaInicio, $fechaFin);
            
            Response::success($diasDisponibles, 'Días disponibles obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de disponibilidad
     */
    public function getStatistics() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para ver estadísticas de agenda');
                return;
            }
            
            $año = $_GET['año'] ?? null;
            $mes = $_GET['mes'] ?? null;
            
            $estadisticas = $this->agendaModel->obtenerEstadisticas($año, $mes);
            
            Response::success($estadisticas, 'Estadísticas obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Buscar disponibilidad por notas
     */
    public function search() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para buscar en la agenda');
                return;
            }
            
            $query = $_GET['q'] ?? '';
            $limite = $_GET['limit'] ?? 10;
            
            if (empty($query)) {
                Response::badRequest('Parámetro de búsqueda requerido');
                return;
            }
            
            $resultados = $this->agendaModel->buscarPorNotas($query, $limite);
            
            Response::success($resultados, 'Búsqueda completada');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Clonar disponibilidad de un mes a otro
     */
    public function cloneMonth() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'crear')) {
                Response::forbidden('No tienes permisos para clonar disponibilidad');
                return;
            }
            
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Datos inválidos');
                return;
            }
            
            // Validar campos requeridos
            $errors = Response::validateInput($input, [
                'año_origen' => ['required' => true, 'type' => 'integer'],
                'mes_origen' => ['required' => true, 'type' => 'integer', 'min' => 1, 'max' => 12],
                'año_destino' => ['required' => true, 'type' => 'integer'],
                'mes_destino' => ['required' => true, 'type' => 'integer', 'min' => 1, 'max' => 12]
            ]);
            
            if (!empty($errors)) {
                Response::validation($errors);
                return;
            }
            
            // Obtener usuario actual
            $usuarioActual = $this->authController->getCurrentUser();
            
            // Clonar mes
            $clonadas = $this->agendaModel->clonarMes(
                $input['año_origen'],
                $input['mes_origen'],
                $input['año_destino'],
                $input['mes_destino'],
                $usuarioActual['id']
            );
            
            Response::success([
                'disponibilidades_clonadas' => $clonadas,
                'mes_origen' => $this->getNombreMes($input['mes_origen']) . ' ' . $input['año_origen'],
                'mes_destino' => $this->getNombreMes($input['mes_destino']) . ' ' . $input['año_destino']
            ], 'Mes clonado correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener segmentos disponibles
     */
    public function getSegments() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            $segmentos = $this->agendaModel->obtenerSegmentosDisponibles();
            
            Response::success($segmentos, 'Segmentos obtenidos correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener resumen semanal
     */
    public function getWeeklySummary() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para ver resumen de agenda');
                return;
            }
            
            $año = $_GET['año'] ?? null;
            $mes = $_GET['mes'] ?? null;
            
            $resumen = $this->agendaModel->obtenerResumenSemanal($año, $mes);
            
            Response::success($resumen, 'Resumen semanal obtenido correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Limpiar disponibilidades antiguas
     */
    public function cleanup() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                Response::methodNotAllowed();
                return;
            }
            
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos de administrador
            if (!$this->authController->hasPermission('agenda', 'eliminar')) {
                Response::forbidden('No tienes permisos para limpiar la agenda');
                return;
            }
            
            $mesesAtras = $_GET['meses'] ?? 12;
            
            $eliminadas = $this->agendaModel->limpiarAntiguas($mesesAtras);
            
            Response::success([
                'disponibilidades_eliminadas' => $eliminadas,
                'meses_atras' => $mesesAtras
            ], 'Limpieza completada correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Obtener vista de calendario actual (mes actual)
     */
    public function getCurrentMonth() {
        $año = date('Y');
        $mes = date('n');
        $this->getMonthly($año, $mes);
    }
    
    /**
     * Obtener próximos eventos/disponibilidades
     */
    public function getUpcoming() {
        try {
            // Verificar autenticación
            $this->authController->requireAuth();
            
            // Verificar permisos
            if (!$this->authController->hasPermission('agenda', 'ver')) {
                Response::forbidden('No tienes permisos para ver la agenda');
                return;
            }
            
            $dias = $_GET['days'] ?? 30;
            $segmento = $_GET['segmento'] ?? '';
            
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d', strtotime("+{$dias} days"));
            
            $filtros = ['disponible' => true];
            if (!empty($segmento)) {
                $filtros['segmento'] = $segmento;
            }
            
            $proximasDisponibilidades = $this->agendaModel->obtenerPorRango($fechaInicio, $fechaFin, $filtros);
            
            Response::success($proximasDisponibilidades, 'Próximas disponibilidades obtenidas correctamente');
            
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
    
    /**
     * Validar año
     */
    private function validarAño($año) {
        $añoInt = (int)$año;
        return $añoInt >= 2020 && $añoInt <= 2030;
    }
    
    /**
     * Validar mes
     */
    private function validarMes($mes) {
        $mesInt = (int)$mes;
        return $mesInt >= 1 && $mesInt <= 12;
    }
    
    /**
     * Obtener nombre del mes
     */
    private function getNombreMes($mes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $meses[(int)$mes] ?? 'Mes inválido';
    }
}
?>