<?php
/**
 * Modelo Usuario
 * Gestión de usuarios del sistema
 */

require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear nuevo usuario
     */
    public function crear($datos) {
        // Validar datos requeridos
        $this->validarDatos($datos, ['nombre', 'email', 'password', 'grupo_id', 'area_id']);
        
        // Verificar que el email no exista
        if ($this->existeEmail($datos['email'])) {
            throw new Exception('El email ya está registrado en el sistema');
        }
        
        // Verificar que el grupo existe
        if (!$this->existeGrupo($datos['grupo_id'])) {
            throw new Exception('El grupo especificado no existe');
        }
        
        // Verificar que el área existe
        if (!$this->existeArea($datos['area_id'])) {
            throw new Exception('El área especificada no existe');
        }
        
        // Validar formato de email adicional
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El formato del email no es válido');
        }
        
        // Hash de la contraseña
        $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre, email, password_hash, grupo_id, area_id, activo) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            trim($datos['nombre']),
            strtolower(trim($datos['email'])),
            $passwordHash,
            $datos['grupo_id'],
            $datos['area_id'],
            $datos['activo'] ?? true
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT u.*, 
                       g.nombre as grupo_nombre, g.permisos as grupo_permisos,
                       a.nombre as area_nombre
                FROM usuarios u 
                LEFT JOIN grupos g ON u.grupo_id = g.id
                LEFT JOIN areas a ON u.area_id = a.id 
                WHERE u.id = ? AND u.activo = 1";
        
        $usuario = $this->db->fetch($sql, [$id]);
        
        if ($usuario) {
            // Decodificar permisos JSON del grupo
            $usuario['grupo_permisos'] = json_decode($usuario['grupo_permisos'], true);
            // No incluir el hash de contraseña en la respuesta
            unset($usuario['password_hash']);
        }
        
        return $usuario;
    }
    
    /**
     * Obtener usuario por email
     */
    public function obtenerPorEmail($email) {
        $sql = "SELECT u.*, a.nombre as area_nombre, a.permisos as area_permisos
                FROM usuarios u 
                LEFT JOIN areas a ON u.area_id = a.id 
                WHERE u.email = ? AND u.activo = 1";
        
        $usuario = $this->db->fetch($sql, [$email]);
        
        if ($usuario) {
            $usuario['area_permisos'] = json_decode($usuario['area_permisos'], true);
        }
        
        return $usuario;
    }
    
    /**
     * Obtener todos los usuarios activos
     */
    public function obtenerTodos($filtros = []) {
        $sql = "SELECT u.id, u.nombre, u.email, u.area_id, u.activo, u.ultimo_acceso, u.fecha_creacion,
                       a.nombre as area_nombre
                FROM usuarios u 
                LEFT JOIN areas a ON u.area_id = a.id 
                WHERE u.activo = 1";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filtros['area_id'])) {
            $sql .= " AND u.area_id = ?";
            $params[] = $filtros['area_id'];
        }
        
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (u.nombre LIKE ? OR u.email LIKE ?)";
            $buscar = '%' . $filtros['buscar'] . '%';
            $params[] = $buscar;
            $params[] = $buscar;
        }
        
        $sql .= " ORDER BY u.nombre ASC";
        
        // Aplicar límite si se especifica
        if (!empty($filtros['limite'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limite'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Actualizar usuario
     */
    public function actualizar($id, $datos) {
        // Verificar que el usuario existe
        $usuarioExistente = $this->obtenerPorId($id);
        if (!$usuarioExistente) {
            throw new Exception('Usuario no encontrado');
        }
        
        // Construir query dinámicamente
        $campos = [];
        $params = [];
        
        if (isset($datos['nombre'])) {
            $campos[] = "nombre = ?";
            $params[] = $datos['nombre'];
        }
        
        if (isset($datos['email'])) {
            // Verificar que el email no esté en uso por otro usuario
            if ($this->existeEmail($datos['email'], $id)) {
                throw new Exception('El email ya está registrado por otro usuario');
            }
            $campos[] = "email = ?";
            $params[] = $datos['email'];
        }
        
        if (isset($datos['password']) && !empty($datos['password'])) {
            $campos[] = "password_hash = ?";
            $params[] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($datos['area_id'])) {
            if (!$this->existeArea($datos['area_id'])) {
                throw new Exception('El área especificada no existe');
            }
            $campos[] = "area_id = ?";
            $params[] = $datos['area_id'];
        }
        
        if (isset($datos['activo'])) {
            $campos[] = "activo = ?";
            $params[] = $datos['activo'] ? 1 : 0;
        }
        
        if (empty($campos)) {
            throw new Exception('No hay datos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Eliminar usuario (soft delete)
     */
    public function eliminar($id) {
        $sql = "UPDATE usuarios SET activo = 0 WHERE id = ?";
        return $this->db->update($sql, [$id]);
    }
    
    /**
     * Autenticar usuario
     */
    public function autenticar($email, $password) {
        // Verificar intentos de login
        $this->verificarIntentosLogin($email);
        
        $sql = "SELECT * FROM usuarios WHERE email = ? AND activo = 1";
        $usuario = $this->db->fetch($sql, [$email]);
        
        if (!$usuario) {
            $this->registrarIntentoFallido($email);
            throw new Exception('Credenciales inválidas');
        }
        
        // Verificar si está bloqueado
        if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
            throw new Exception('Usuario bloqueado temporalmente. Intenta más tarde.');
        }
        
        // Verificar contraseña
        if (!password_verify($password, $usuario['password_hash'])) {
            $this->registrarIntentoFallido($email);
            throw new Exception('Credenciales inválidas');
        }
        
        // Limpiar intentos fallidos y actualizar último acceso
        $this->limpiarIntentosFallidos($usuario['id']);
        $this->actualizarUltimoAcceso($usuario['id']);
        
        // Obtener datos completos del usuario
        return $this->obtenerPorId($usuario['id']);
    }
    
    /**
     * Verificar permisos del usuario
     */
    public function tienePermiso($usuarioId, $modulo, $accion) {
        $usuario = $this->obtenerPorId($usuarioId);
        
        if (!$usuario || !$usuario['area_permisos']) {
            return false;
        }
        
        $permisos = $usuario['area_permisos'];
        
        return isset($permisos[$modulo]) && in_array($accion, $permisos[$modulo]);
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($id, $passwordActual, $passwordNueva) {
        $sql = "SELECT password_hash FROM usuarios WHERE id = ? AND activo = 1";
        $usuario = $this->db->fetch($sql, [$id]);
        
        if (!$usuario) {
            throw new Exception('Usuario no encontrado');
        }
        
        if (!password_verify($passwordActual, $usuario['password_hash'])) {
            throw new Exception('Contraseña actual incorrecta');
        }
        
        $this->validarPassword($passwordNueva);
        
        $sql = "UPDATE usuarios SET password_hash = ? WHERE id = ?";
        $passwordHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
        
        return $this->db->update($sql, [$passwordHash, $id]);
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
        
        // Validar email
        if (isset($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Formato de email inválido');
        }
        
        // Validar contraseña
        if (isset($datos['password'])) {
            $this->validarPassword($datos['password']);
        }
        
        // Validar nombre
        if (isset($datos['nombre']) && strlen($datos['nombre']) < 2) {
            throw new Exception('El nombre debe tener al menos 2 caracteres');
        }
    }
    
    /**
     * Validar contraseña
     */
    private function validarPassword($password) {
        if (strlen($password) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres');
        }
        
        // Opcional: agregar más validaciones de seguridad
        // if (!preg_match('/[A-Z]/', $password)) {
        //     throw new Exception('La contraseña debe contener al menos una mayúscula');
        // }
    }
    
    /**
     * Verificar si existe email
     */
    private function existeEmail($email, $excluirId = null) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ? AND activo = 1";
        $params = [$email];
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        return $this->db->fetch($sql, $params)['COUNT(*)'] > 0;
    }
    
    /**
     * Verificar si existe grupo
     */
    private function existeGrupo($grupoId) {
        $sql = "SELECT COUNT(*) FROM grupos WHERE id = ? AND activo = 1";
        return $this->db->fetch($sql, [$grupoId])['COUNT(*)'] > 0;
    }
    
    /**
     * Validar acceso de administrador
     */
    public function validarAccesoAdmin($email, $codigoInvitacion) {
        // Obtener configuración del sistema
        $sql = "SELECT valor FROM configuracion_sistema WHERE clave = ?";
        
        // Verificar código de invitación
        $codigoValido = $this->db->fetch($sql, ['codigo_invitacion_admin']);
        if (!$codigoValido || $codigoValido['valor'] !== $codigoInvitacion) {
            throw new Exception('Código de invitación inválido');
        }
        
        // Verificar email autorizado
        $emailsAutorizados = $this->db->fetch($sql, ['emails_autorizados_admin']);
        if ($emailsAutorizados) {
            $emails = json_decode($emailsAutorizados['valor'], true);
            if (!in_array($email, $emails)) {
                throw new Exception('Email no autorizado para registro de administrador');
            }
        }
        
        return true;
    }
    
    /**
     * Obtener configuración del sistema
     */
    public function obtenerConfiguracion($clave) {
        $sql = "SELECT valor FROM configuracion_sistema WHERE clave = ?";
        $resultado = $this->db->fetch($sql, [$clave]);
        return $resultado ? $resultado['valor'] : null;
    }
    
    /**
     * Verificar si existe área
     */
    private function existeArea($areaId) {
        $sql = "SELECT COUNT(*) FROM areas WHERE id = ? AND activa = 1";
        return $this->db->fetch($sql, [$areaId])['COUNT(*)'] > 0;
    }
    
    /**
     * Verificar intentos de login
     */
    private function verificarIntentosLogin($email) {
        $sql = "SELECT intentos_login, bloqueado_hasta FROM usuarios WHERE email = ?";
        $usuario = $this->db->fetch($sql, [$email]);
        
        if ($usuario && $usuario['intentos_login'] >= 5) {
            // Bloquear por 15 minutos
            $sql = "UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?";
            $this->db->update($sql, [$email]);
            throw new Exception('Demasiados intentos fallidos. Usuario bloqueado temporalmente.');
        }
    }
    
    /**
     * Registrar intento fallido
     */
    private function registrarIntentoFallido($email) {
        $sql = "UPDATE usuarios SET intentos_login = intentos_login + 1 WHERE email = ?";
        $this->db->update($sql, [$email]);
    }
    
    /**
     * Limpiar intentos fallidos
     */
    private function limpiarIntentosFallidos($id) {
        $sql = "UPDATE usuarios SET intentos_login = 0, bloqueado_hasta = NULL WHERE id = ?";
        $this->db->update($sql, [$id]);
    }
    
    /**
     * Actualizar último acceso
     */
    private function actualizarUltimoAcceso($id) {
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
        $this->db->update($sql, [$id]);
    }
    
    /**
     * Obtener estadísticas de usuarios
     */
    public function obtenerEstadisticas() {
        $sql = "SELECT 
                    COUNT(*) as total_usuarios,
                    COUNT(CASE WHEN activo = 1 THEN 1 END) as usuarios_activos,
                    COUNT(CASE WHEN ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as usuarios_activos_mes,
                    COUNT(CASE WHEN ultimo_acceso >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as usuarios_activos_semana
                FROM usuarios";
        
        return $this->db->fetch($sql);
    }
}
?>