<?php
/**
 * Script de Instalación del Sistema de Gestión de Producción
 * Este script configura la base de datos y verifica los requisitos del sistema
 */

// Configuración de errores para instalación
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si ya está instalado
if (file_exists('../config/installed.lock')) {
    die('El sistema ya está instalado. Si necesitas reinstalar, elimina el archivo config/installed.lock');
}

class Installer {
    private $config;
    private $pdo;
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        $this->checkRequirements();
    }
    
    /**
     * Verificar requisitos del sistema
     */
    private function checkRequirements() {
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $this->errors[] = 'Se requiere PHP 7.4 o superior. Versión actual: ' . PHP_VERSION;
        }
        
        // Verificar extensiones requeridas
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->errors[] = "Extensión PHP requerida no encontrada: {$ext}";
            }
        }
        
        // Verificar permisos de escritura
        $writablePaths = [
            '../config/',
            '../logs/',
            '../cache/',
            '../uploads/'
        ];
        
        foreach ($writablePaths as $path) {
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    $this->errors[] = "No se pudo crear el directorio: {$path}";
                }
            }
            
            if (!is_writable($path)) {
                $this->errors[] = "El directorio no tiene permisos de escritura: {$path}";
            }
        }
        
        // Verificar configuración de PHP
        if (ini_get('file_uploads') != 1) {
            $this->warnings[] = 'file_uploads está deshabilitado. No se podrán subir archivos.';
        }
        
        if (ini_get('session.auto_start') == 1) {
            $this->warnings[] = 'session.auto_start está habilitado. Esto puede causar problemas.';
        }
    }
    
    /**
     * Ejecutar instalación
     */
    public function install($dbConfig, $adminConfig) {
        try {
            // Verificar errores críticos
            if (!empty($this->errors)) {
                throw new Exception('Errores críticos encontrados: ' . implode(', ', $this->errors));
            }
            
            // Conectar a la base de datos
            $this->connectDatabase($dbConfig);
            
            // Ejecutar script SQL
            $this->executeSQLScript();
            
            // Crear usuario administrador
            $this->createAdminUser($adminConfig);
            
            // Crear archivo de configuración
            $this->createConfigFile($dbConfig);
            
            // Poblar base de conocimiento del chatbot
            $this->populateChatbotKnowledge();
            
            // Crear archivo de bloqueo
            $this->createLockFile();
            
            return [
                'success' => true,
                'message' => 'Instalación completada exitosamente',
                'warnings' => $this->warnings
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $this->errors
            ];
        }
    }
    
    /**
     * Conectar a la base de datos
     */
    private function connectDatabase($config) {
        try {
            $dsn = "mysql:host={$config['host']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Crear base de datos si no existe
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->pdo->exec("USE `{$config['database']}`");
            
        } catch (PDOException $e) {
            throw new Exception('Error conectando a la base de datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Ejecutar script SQL
     */
    private function executeSQLScript() {
        $sqlFile = __DIR__ . '/database.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception('Archivo SQL no encontrado: ' . $sqlFile);
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Dividir en statements individuales
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        foreach ($statements as $statement) {
            if (trim($statement)) {
                try {
                    $this->pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignorar errores de elementos que ya existen
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw new Exception('Error ejecutando SQL: ' . $e->getMessage() . ' | Statement: ' . substr($statement, 0, 100));
                    }
                }
            }
        }
    }
    
    /**
     * Crear usuario administrador
     */
    private function createAdminUser($adminConfig) {
        // Verificar si ya existe un usuario administrador
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$adminConfig['email']]);
        
        if ($stmt->fetchColumn() > 0) {
            // Actualizar usuario existente
            $stmt = $this->pdo->prepare("
                UPDATE usuarios 
                SET nombre = ?, password_hash = ?, area_id = 1, activo = 1 
                WHERE email = ?
            ");
            $stmt->execute([
                $adminConfig['name'],
                password_hash($adminConfig['password'], PASSWORD_DEFAULT),
                $adminConfig['email']
            ]);
        } else {
            // Crear nuevo usuario administrador
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios (nombre, email, password_hash, area_id, activo) 
                VALUES (?, ?, ?, 1, 1)
            ");
            $stmt->execute([
                $adminConfig['name'],
                $adminConfig['email'],
                password_hash($adminConfig['password'], PASSWORD_DEFAULT)
            ]);
        }
    }
    
    /**
     * Crear archivo de configuración
     */
    private function createConfigFile($dbConfig) {
        $configContent = "<?php\n";
        $configContent .= "// Configuración generada automáticamente\n";
        $configContent .= "return [\n";
        $configContent .= "    'database' => [\n";
        $configContent .= "        'host' => '{$dbConfig['host']}',\n";
        $configContent .= "        'name' => '{$dbConfig['database']}',\n";
        $configContent .= "        'user' => '{$dbConfig['username']}',\n";
        $configContent .= "        'password' => '{$dbConfig['password']}',\n";
        $configContent .= "        'charset' => 'utf8mb4'\n";
        $configContent .= "    ],\n";
        $configContent .= "    'app' => [\n";
        $configContent .= "        'name' => 'Sistema de Gestión de Producción',\n";
        $configContent .= "        'version' => '1.0.0',\n";
        $configContent .= "        'debug' => false,\n";
        $configContent .= "        'timezone' => 'America/Mexico_City',\n";
        $configContent .= "        'installed' => true,\n";
        $configContent .= "        'install_date' => '" . date('Y-m-d H:i:s') . "'\n";
        $configContent .= "    ]\n";
        $configContent .= "];\n";
        
        if (!file_put_contents('../config/config.php', $configContent)) {
            throw new Exception('No se pudo crear el archivo de configuración');
        }
    }
    
    /**
     * Poblar base de conocimiento del chatbot
     */
    private function populateChatbotKnowledge() {
        try {
            require_once __DIR__ . '/populate_chatbot_knowledge.php';
            $populator = new ChatbotKnowledgePopulator();
            $populator->populate();
        } catch (Exception $e) {
            $this->warnings[] = 'No se pudo poblar la base de conocimiento del chatbot: ' . $e->getMessage();
        }
    }
    
    /**
     * Crear archivo de bloqueo
     */
    private function createLockFile() {
        $lockContent = "Sistema instalado el " . date('Y-m-d H:i:s') . "\n";
        $lockContent .= "No eliminar este archivo a menos que desees reinstalar el sistema.\n";
        
        if (!file_put_contents('../config/installed.lock', $lockContent)) {
            throw new Exception('No se pudo crear el archivo de bloqueo');
        }
    }
    
    /**
     * Obtener errores
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obtener advertencias
     */
    public function getWarnings() {
        return $this->warnings;
    }
}

// Procesar instalación si se envió el formulario
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installer = new Installer();
    
    $dbConfig = [
        'host' => $_POST['db_host'] ?? 'localhost',
        'database' => $_POST['db_name'] ?? 'gestion_produccion',
        'username' => $_POST['db_user'] ?? 'root',
        'password' => $_POST['db_pass'] ?? ''
    ];
    
    $adminConfig = [
        'name' => $_POST['admin_name'] ?? 'Administrador',
        'email' => $_POST['admin_email'] ?? 'admin@gestionproduccion.com',
        'password' => $_POST['admin_password'] ?? ''
    ];
    
    // Validar datos
    if (empty($adminConfig['password'])) {
        $result = [
            'success' => false,
            'message' => 'La contraseña del administrador es requerida'
        ];
    } else {
        $result = $installer->install($dbConfig, $adminConfig);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Gestión de Producción</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .content {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .requirements {
            font-size: 0.9rem;
        }
        
        .requirement {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .requirement:last-child {
            border-bottom: none;
        }
        
        .status-ok {
            color: #27ae60;
            font-weight: 600;
        }
        
        .status-error {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .status-warning {
            color: #f39c12;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistema de Gestión de Producción</h1>
            <p>Instalación y Configuración</p>
        </div>
        
        <div class="content">
            <?php if ($result): ?>
                <?php if ($result['success']): ?>
                    <div class="alert alert-success">
                        <h3>¡Instalación Completada!</h3>
                        <p><?php echo $result['message']; ?></p>
                        <?php if (!empty($result['warnings'])): ?>
                            <h4>Advertencias:</h4>
                            <ul>
                                <?php foreach ($result['warnings'] as $warning): ?>
                                    <li><?php echo htmlspecialchars($warning); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <p><strong>Próximos pasos:</strong></p>
                        <ol>
                            <li>Elimina o renombra este archivo de instalación por seguridad</li>
                            <li>Accede al sistema con las credenciales del administrador</li>
                            <li>Configura usuarios adicionales según sea necesario</li>
                        </ol>
                        <p><a href="../index.html" class="btn">Acceder al Sistema</a></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        <h3>Error en la Instalación</h3>
                        <p><?php echo htmlspecialchars($result['message']); ?></p>
                        <?php if (!empty($result['errors'])): ?>
                            <h4>Errores:</h4>
                            <ul>
                                <?php foreach ($result['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (!$result || !$result['success']): ?>
                <form method="POST">
                    <div class="section">
                        <h2 class="section-title">Configuración de Base de Datos</h2>
                        
                        <div class="form-group">
                            <label class="form-label" for="db_host">Servidor de Base de Datos:</label>
                            <input type="text" id="db_host" name="db_host" class="form-input" 
                                   value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="db_name">Nombre de la Base de Datos:</label>
                            <input type="text" id="db_name" name="db_name" class="form-input" 
                                   value="<?php echo $_POST['db_name'] ?? 'gestion_produccion'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="db_user">Usuario de Base de Datos:</label>
                            <input type="text" id="db_user" name="db_user" class="form-input" 
                                   value="<?php echo $_POST['db_user'] ?? 'root'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="db_pass">Contraseña de Base de Datos:</label>
                            <input type="password" id="db_pass" name="db_pass" class="form-input" 
                                   value="<?php echo $_POST['db_pass'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="section">
                        <h2 class="section-title">Usuario Administrador</h2>
                        
                        <div class="form-group">
                            <label class="form-label" for="admin_name">Nombre Completo:</label>
                            <input type="text" id="admin_name" name="admin_name" class="form-input" 
                                   value="<?php echo $_POST['admin_name'] ?? 'Administrador'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="admin_email">Email:</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-input" 
                                   value="<?php echo $_POST['admin_email'] ?? 'admin@gestionproduccion.com'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="admin_password">Contraseña:</label>
                            <input type="password" id="admin_password" name="admin_password" class="form-input" 
                                   placeholder="Mínimo 6 caracteres" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Instalar Sistema</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>