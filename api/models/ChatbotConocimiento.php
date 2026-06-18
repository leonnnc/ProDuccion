<?php
/**
 * Modelo ChatbotConocimiento
 * Gestión de base de conocimiento del chatbot
 */

require_once __DIR__ . '/../config/database.php';

class ChatbotConocimiento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Buscar respuesta por palabras clave
     */
    public function buscarRespuesta($mensaje, $contextoModulo = 'general') {
        // Limpiar y preparar mensaje
        $palabrasClave = $this->extraerPalabrasClave($mensaje);
        
        if (empty($palabrasClave)) {
            return $this->obtenerRespuestaDefault();
        }
        
        // Buscar coincidencias exactas primero
        $respuesta = $this->buscarCoincidenciaExacta($palabrasClave, $contextoModulo);
        if ($respuesta) {
            $this->incrementarUso($respuesta['id']);
            return $respuesta;
        }
        
        // Buscar coincidencias parciales
        $respuesta = $this->buscarCoincidenciaParcial($palabrasClave, $contextoModulo);
        if ($respuesta) {
            $this->incrementarUso($respuesta['id']);
            return $respuesta;
        }
        
        // Buscar en contexto general si no se encontró en el módulo específico
        if ($contextoModulo !== 'general') {
            $respuesta = $this->buscarCoincidenciaParcial($palabrasClave, 'general');
            if ($respuesta) {
                $this->incrementarUso($respuesta['id']);
                return $respuesta;
            }
        }
        
        return $this->obtenerRespuestaDefault();
    }
    
    /**
     * Crear nueva entrada de conocimiento
     */
    public function crear($datos) {
        $this->validarDatos($datos, ['categoria', 'pregunta_clave', 'respuesta']);
        
        $sql = "INSERT INTO chatbot_conocimiento (categoria, pregunta_clave, respuesta, palabras_clave, modulo_relacionado, prioridad) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $palabrasClave = $this->extraerPalabrasClave($datos['pregunta_clave']);
        
        $params = [
            $datos['categoria'],
            $datos['pregunta_clave'],
            $datos['respuesta'],
            json_encode($palabrasClave),
            $datos['modulo_relacionado'] ?? 'general',
            $datos['prioridad'] ?? 1
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Obtener todas las entradas de conocimiento
     */
    public function obtenerTodas($filtros = []) {
        $sql = "SELECT * FROM chatbot_conocimiento WHERE activo = 1";
        $params = [];
        
        if (!empty($filtros['categoria'])) {
            $sql .= " AND categoria = ?";
            $params[] = $filtros['categoria'];
        }
        
        if (!empty($filtros['modulo_relacionado'])) {
            $sql .= " AND modulo_relacionado = ?";
            $params[] = $filtros['modulo_relacionado'];
        }
        
        $sql .= " ORDER BY prioridad ASC, veces_utilizada DESC";
        
        $conocimientos = $this->db->fetchAll($sql, $params);
        
        // Decodificar palabras clave
        foreach ($conocimientos as &$conocimiento) {
            $conocimiento['palabras_clave'] = json_decode($conocimiento['palabras_clave'], true) ?? [];
        }
        
        return $conocimientos;
    }
    
    /**
     * Actualizar entrada de conocimiento
     */
    public function actualizar($id, $datos) {
        $campos = [];
        $params = [];
        
        if (isset($datos['categoria'])) {
            $campos[] = "categoria = ?";
            $params[] = $datos['categoria'];
        }
        
        if (isset($datos['pregunta_clave'])) {
            $campos[] = "pregunta_clave = ?";
            $params[] = $datos['pregunta_clave'];
            
            // Actualizar palabras clave también
            $palabrasClave = $this->extraerPalabrasClave($datos['pregunta_clave']);
            $campos[] = "palabras_clave = ?";
            $params[] = json_encode($palabrasClave);
        }
        
        if (isset($datos['respuesta'])) {
            $campos[] = "respuesta = ?";
            $params[] = $datos['respuesta'];
        }
        
        if (isset($datos['modulo_relacionado'])) {
            $campos[] = "modulo_relacionado = ?";
            $params[] = $datos['modulo_relacionado'];
        }
        
        if (isset($datos['prioridad'])) {
            $campos[] = "prioridad = ?";
            $params[] = $datos['prioridad'];
        }
        
        if (isset($datos['activo'])) {
            $campos[] = "activo = ?";
            $params[] = $datos['activo'] ? 1 : 0;
        }
        
        if (empty($campos)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE chatbot_conocimiento SET " . implode(', ', $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Obtener sugerencias contextuales
     */
    public function obtenerSugerencias($contextoModulo = 'general', $limite = 5) {
        $sql = "SELECT pregunta_clave, respuesta 
                FROM chatbot_conocimiento 
                WHERE modulo_relacionado = ? AND activo = 1 
                ORDER BY prioridad ASC, veces_utilizada DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$contextoModulo, $limite]);
    }
    
    /**
     * Buscar coincidencia exacta
     */
    private function buscarCoincidenciaExacta($palabrasClave, $contextoModulo) {
        $sql = "SELECT * FROM chatbot_conocimiento 
                WHERE modulo_relacionado = ? AND activo = 1";
        
        $conocimientos = $this->db->fetchAll($sql, [$contextoModulo]);
        
        foreach ($conocimientos as $conocimiento) {
            $palabrasConocimiento = json_decode($conocimiento['palabras_clave'], true) ?? [];
            
            // Verificar si todas las palabras clave del mensaje están en el conocimiento
            $coincidencias = array_intersect($palabrasClave, $palabrasConocimiento);
            if (count($coincidencias) >= min(count($palabrasClave), 2)) {
                return $conocimiento;
            }
        }
        
        return null;
    }
    
    /**
     * Buscar coincidencia parcial
     */
    private function buscarCoincidenciaParcial($palabrasClave, $contextoModulo) {
        $sql = "SELECT * FROM chatbot_conocimiento 
                WHERE modulo_relacionado = ? AND activo = 1
                ORDER BY prioridad ASC, veces_utilizada DESC";
        
        $conocimientos = $this->db->fetchAll($sql, [$contextoModulo]);
        
        $mejorCoincidencia = null;
        $maxCoincidencias = 0;
        
        foreach ($conocimientos as $conocimiento) {
            $palabrasConocimiento = json_decode($conocimiento['palabras_clave'], true) ?? [];
            
            // Contar coincidencias
            $coincidencias = count(array_intersect($palabrasClave, $palabrasConocimiento));
            
            if ($coincidencias > $maxCoincidencias) {
                $maxCoincidencias = $coincidencias;
                $mejorCoincidencia = $conocimiento;
            }
        }
        
        return $mejorCoincidencia;
    }
    
    /**
     * Extraer palabras clave de un texto
     */
    private function extraerPalabrasClave($texto) {
        // Convertir a minúsculas y limpiar
        $texto = strtolower($texto);
        $texto = preg_replace('/[^\w\sáéíóúñü]/u', '', $texto);
        
        // Dividir en palabras
        $palabras = explode(' ', $texto);
        
        // Filtrar palabras vacías y muy cortas
        $palabrasVacias = ['el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'como', 'del', 'las', 'los', 'una', 'sus', 'al', 'me', 'si', 'ya', 'o', 'pero', 'más', 'muy', 'qué', 'cómo', 'dónde', 'cuándo'];
        
        $palabrasClave = [];
        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if (strlen($palabra) > 2 && !in_array($palabra, $palabrasVacias)) {
                $palabrasClave[] = $palabra;
            }
        }
        
        return array_unique($palabrasClave);
    }
    
    /**
     * Incrementar contador de uso
     */
    private function incrementarUso($id) {
        $sql = "UPDATE chatbot_conocimiento SET veces_utilizada = veces_utilizada + 1 WHERE id = ?";
        $this->db->update($sql, [$id]);
    }
    
    /**
     * Obtener respuesta por defecto
     */
    private function obtenerRespuestaDefault() {
        return [
            'id' => 0,
            'categoria' => 'default',
            'pregunta_clave' => 'default',
            'respuesta' => 'Lo siento, no entiendo tu pregunta. ¿Podrías reformularla o ser más específico? También puedes usar las opciones de ayuda rápida.',
            'modulo_relacionado' => 'general'
        ];
    }
    
    /**
     * Eliminar entrada de conocimiento
     */
    public function eliminar($id) {
        // Soft delete - marcar como inactivo
        $sql = "UPDATE chatbot_conocimiento SET activo = 0 WHERE id = ?";
        return $this->db->update($sql, [$id]);
    }
    
    /**
     * Obtener por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM chatbot_conocimiento WHERE id = ?";
        $conocimiento = $this->db->fetch($sql, [$id]);
        
        if ($conocimiento) {
            $conocimiento['palabras_clave'] = json_decode($conocimiento['palabras_clave'], true) ?? [];
        }
        
        return $conocimiento;
    }
    
    /**
     * Buscar en base de conocimiento
     */
    public function buscar($texto, $limite = 10) {
        $sql = "SELECT * FROM chatbot_conocimiento 
                WHERE (pregunta_clave LIKE ? OR respuesta LIKE ?) AND activo = 1
                ORDER BY prioridad ASC, veces_utilizada DESC
                LIMIT ?";
        
        $buscar = '%' . $texto . '%';
        $conocimientos = $this->db->fetchAll($sql, [$buscar, $buscar, $limite]);
        
        // Decodificar palabras clave
        foreach ($conocimientos as &$conocimiento) {
            $conocimiento['palabras_clave'] = json_decode($conocimiento['palabras_clave'], true) ?? [];
        }
        
        return $conocimientos;
    }
    
    /**
     * Obtener categorías disponibles
     */
    public function obtenerCategorias() {
        $sql = "SELECT DISTINCT categoria FROM chatbot_conocimiento WHERE activo = 1 ORDER BY categoria";
        return array_column($this->db->fetchAll($sql), 'categoria');
    }
    
    /**
     * Obtener estadísticas de uso
     */
    public function obtenerEstadisticasUso() {
        $sql = "SELECT 
                    categoria,
                    modulo_relacionado,
                    COUNT(*) as total_entradas,
                    SUM(veces_utilizada) as total_usos,
                    AVG(veces_utilizada) as promedio_usos,
                    MAX(veces_utilizada) as max_usos,
                    COUNT(CASE WHEN veces_utilizada = 0 THEN 1 END) as sin_uso
                FROM chatbot_conocimiento 
                WHERE activo = 1
                GROUP BY categoria, modulo_relacionado
                ORDER BY total_usos DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Importar conocimientos desde array
     */
    public function importarConocimientos($conocimientos, $sobreescribir = false) {
        $importados = 0;
        $actualizados = 0;
        $errores = [];
        
        $this->db->beginTransaction();
        
        try {
            foreach ($conocimientos as $index => $conocimiento) {
                try {
                    // Validar datos requeridos
                    if (!isset($conocimiento['pregunta_clave']) || !isset($conocimiento['respuesta'])) {
                        $errores[] = "Entrada {$index}: Faltan campos requeridos";
                        continue;
                    }
                    
                    // Buscar si ya existe
                    $existente = $this->buscarPorPregunta($conocimiento['pregunta_clave']);
                    
                    if ($existente) {
                        if ($sobreescribir) {
                            $this->actualizar($existente['id'], $conocimiento);
                            $actualizados++;
                        }
                    } else {
                        $this->crear($conocimiento);
                        $importados++;
                    }
                    
                } catch (Exception $e) {
                    $errores[] = "Entrada {$index}: " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            
            return [
                'importados' => $importados,
                'actualizados' => $actualizados,
                'errores' => $errores
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Exportar conocimientos
     */
    public function exportarConocimientos($filtros = []) {
        $conocimientos = $this->obtenerTodas($filtros);
        
        return [
            'conocimientos' => $conocimientos,
            'estadisticas' => $this->obtenerEstadisticasUso(),
            'fecha_exportacion' => date('Y-m-d H:i:s'),
            'total_entradas' => count($conocimientos)
        ];
    }
    
    /**
     * Buscar por pregunta específica
     */
    private function buscarPorPregunta($pregunta) {
        $sql = "SELECT * FROM chatbot_conocimiento WHERE pregunta_clave = ? AND activo = 1";
        return $this->db->fetch($sql, [$pregunta]);
    }
    
    /**
     * Obtener respuestas más populares
     */
    public function obtenerMasPopulares($limite = 10) {
        $sql = "SELECT * FROM chatbot_conocimiento 
                WHERE activo = 1 AND veces_utilizada > 0
                ORDER BY veces_utilizada DESC
                LIMIT ?";
        
        $conocimientos = $this->db->fetchAll($sql, [$limite]);
        
        // Decodificar palabras clave
        foreach ($conocimientos as &$conocimiento) {
            $conocimiento['palabras_clave'] = json_decode($conocimiento['palabras_clave'], true) ?? [];
        }
        
        return $conocimientos;
    }
    
    /**
     * Obtener respuestas sin uso
     */
    public function obtenerSinUso() {
        $sql = "SELECT * FROM chatbot_conocimiento 
                WHERE activo = 1 AND veces_utilizada = 0
                ORDER BY fecha_creacion DESC";
        
        $conocimientos = $this->db->fetchAll($sql);
        
        // Decodificar palabras clave
        foreach ($conocimientos as &$conocimiento) {
            $conocimiento['palabras_clave'] = json_decode($conocimiento['palabras_clave'], true) ?? [];
        }
        
        return $conocimientos;
    }
    
    /**
     * Generar respuesta inteligente basada en contexto
     */
    public function generarRespuestaContextual($mensaje, $contextoModulo, $historialConversacion = []) {
        // Buscar respuesta principal
        $respuesta = $this->buscarRespuesta($mensaje, $contextoModulo);
        
        // Agregar sugerencias contextuales si es apropiado
        if ($respuesta && $respuesta['id'] > 0) {
            $sugerencias = $this->obtenerSugerencias($contextoModulo, 3);
            
            if (!empty($sugerencias)) {
                $respuesta['sugerencias'] = array_map(function($sug) {
                    return $sug['pregunta_clave'];
                }, $sugerencias);
            }
        }
        
        return $respuesta;
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