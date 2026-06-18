<?php
/**
 * Archivo de Configuración Principal
 * Sistema de Gestión de Producción
 */

return [
    // Configuración de la base de datos
    'database' => [
        'host' => 'localhost',
        'name' => 'gestion_produccion',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    
    // Configuración de la aplicación
    'app' => [
        'name' => 'Sistema de Gestión de Producción',
        'version' => '1.0.0',
        'debug' => true, // Cambiar a false en producción
        'timezone' => 'America/Mexico_City',
        'locale' => 'es_ES'
    ],
    
    // Configuración de seguridad
    'security' => [
        'session_name' => 'SGP_SESSION',
        'session_lifetime' => 3600, // 1 hora
        'password_min_length' => 6,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
        'csrf_token_name' => 'csrf_token'
    ],
    
    // Configuración de API
    'api' => [
        'base_url' => '/api',
        'version' => 'v1',
        'rate_limit' => 100, // requests per minute
        'cors_enabled' => true,
        'cors_origins' => ['*'] // Cambiar en producción
    ],
    
    // Configuración del chatbot
    'chatbot' => [
        'enabled' => true,
        'max_message_length' => 500,
        'response_delay' => 1000, // milisegundos
        'fallback_responses' => [
            'Lo siento, no entendí tu pregunta. ¿Podrías reformularla?',
            'No tengo información sobre eso. ¿Hay algo más en lo que pueda ayudarte?',
            'Intenta ser más específico con tu pregunta.'
        ]
    ],
    
    // Configuración de archivos y uploads
    'files' => [
        'upload_path' => 'uploads/',
        'max_file_size' => 5242880, // 5MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'image_max_width' => 1920,
        'image_max_height' => 1080
    ],
    
    // Configuración de logging
    'logging' => [
        'enabled' => true,
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'file' => 'logs/app.log',
        'max_file_size' => 10485760, // 10MB
        'rotate_files' => true
    ],
    
    // Configuración de cache
    'cache' => [
        'enabled' => true,
        'type' => 'file', // file, redis, memcached
        'path' => 'cache/',
        'default_ttl' => 3600 // 1 hora
    ],
    
    // Configuración de email (para notificaciones futuras)
    'email' => [
        'enabled' => false,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'from_email' => 'noreply@gestionproduccion.com',
        'from_name' => 'Sistema de Gestión de Producción'
    ],
    
    // URLs y rutas
    'paths' => [
        'base_url' => '/',
        'assets_url' => '/assets',
        'api_url' => '/api',
        'uploads_url' => '/uploads'
    ]
];
?>