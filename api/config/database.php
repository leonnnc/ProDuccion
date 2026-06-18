<?php
/**
 * Configuración de Base de Datos
 * Sistema de Gestión de Producción
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuración de la base de datos
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    /**
     * Cargar configuración desde variables de entorno o archivo de configuración
     */
    private function loadConfig() {
        // Intentar cargar desde variables de entorno primero
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->database = $_ENV['DB_NAME'] ?? 'gestion_produccion';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        
        // Si no hay variables de entorno, cargar desde archivo config.php
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            $this->host = $config['database']['host'] ?? $this->host;
            $this->database = $config['database']['name'] ?? $this->database;
            $this->username = $config['database']['user'] ?? $this->username;
            $this->password = $config['database']['password'] ?? $this->password;
        }
    }
    
    /**
     * Establecer conexión con la base de datos
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_PERSISTENT => true // Conexiones persistentes para mejor rendimiento
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos. Verifica la configuración.");
        }
    }
    
    /**
     * Obtener instancia singleton de la base de datos
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        // Verificar si la conexión sigue activa
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconectar si la conexión se perdió
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Ejecutar una consulta preparada
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta SQL: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Error en la consulta a la base de datos.");
        }
    }
    
    /**
     * Obtener un solo registro
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtener múltiples registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insertar registro y obtener ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    /**
     * Actualizar registros
     */
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Eliminar registros
     */
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Verificar si estamos en una transacción
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
    
    /**
     * Obtener información de la base de datos para debugging
     */
    public function getInfo() {
        return [
            'host' => $this->host,
            'database' => $this->database,
            'username' => $this->username,
            'charset' => $this->charset,
            'server_version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
            'connection_status' => $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS)
        ];
    }
    
    /**
     * Prevenir clonación
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar la instancia de Database");
    }
}

/**
 * Función helper para obtener la instancia de la base de datos
 */
function getDB() {
    return Database::getInstance();
}

/**
 * Función helper para obtener la conexión PDO directamente
 */
function getConnection() {
    return Database::getInstance()->getConnection();
}
?>