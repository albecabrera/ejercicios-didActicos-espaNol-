<?php
/**
 * Configuración de base de datos SQLite
 * Generado automáticamente por install.sh
 */

// Ruta a la base de datos SQLite
define('DB_PATH', __DIR__ . '/ejercicios.db');

// Puerto del servidor
define('SERVER_PORT', '8000');

// URL del backend
define('BACKEND_URL', 'http://localhost:8000');

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de CORS (permitir peticiones desde el frontend)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Función para obtener conexión a la base de datos
 */
function getDBConnection() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Habilitar foreign keys en SQLite
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error de conexión a la base de datos',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

/**
 * Función para enviar respuesta JSON
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función para validar datos requeridos
 */
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        // Verificar si el campo existe
        if (!isset($data[$field]) || $data[$field] === null) {
            $missing[] = $field;
            continue;
        }

        // Validar según el tipo
        $value = $data[$field];

        // String vacío
        if (is_string($value) && trim($value) === '') {
            $missing[] = $field;
        }
        // Array vacío
        elseif (is_array($value) && empty($value)) {
            $missing[] = $field;
        }
        // Booleano false (podría indicar un error)
        elseif (is_bool($value) && $value === false) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Iniciar sesión
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    startSession();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Requerir autenticación
 */
function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No autorizado'
        ]);
        exit();
    }
}
