<?php
/**
 * Code Lab - Datenbank-Konfiguration
 *
 * WICHTIG: Vor der Nutzung diese Datei anpassen!
 * 1. Datenbank erstellen: CREATE DATABASE code_lab;
 * 2. Schema importieren: mysql -u username -p code_lab < database/schema.sql
 * 3. Zugangsdaten unten eintragen
 */

// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 1); // Für Entwicklung aktiviert
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// CORS-Header für API-Zugriff
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Preflight-Requests behandeln
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_NAME', 'code_lab');
define('DB_USER', 'root'); // ÄNDERN!
define('DB_PASS', ''); // ÄNDERN!
define('DB_CHARSET', 'utf8mb4');

// Session-Konfiguration
define('SESSION_LIFETIME', 86400); // 24 Stunden in Sekunden
define('SESSION_NAME', 'code_lab_session');

// Sicherheit
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB

// Pfade
define('BASE_URL', 'http://localhost'); // ÄNDERN für Produktion!
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Datenbankverbindung herstellen
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connection->exec("SET NAMES utf8mb4");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error' => 'Datenbankverbindung fehlgeschlagen'
            ]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prepared Statement ausführen
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw $e;
        }
    }

    // Einzelne Zeile abrufen
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    // Alle Zeilen abrufen
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    // Insert/Update/Delete ausführen
    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }

    // Letzte Insert-ID
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Hilfsfunktionen

/**
 * JSON-Antwort senden
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Fehler-Antwort senden
 */
function sendError($message, $statusCode = 400, $details = null) {
    $response = [
        'success' => false,
        'error' => $message
    ];
    if ($details !== null) {
        $response['details'] = $details;
    }
    sendResponse($response, $statusCode);
}

/**
 * Erfolgs-Antwort senden
 */
function sendSuccess($data = [], $message = null) {
    $response = [
        'success' => true,
        'data' => $data
    ];
    if ($message !== null) {
        $response['message'] = $message;
    }
    sendResponse($response, 200);
}

/**
 * POST-Daten validieren
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Ungültige JSON-Daten');
    }

    return $data ?: [];
}

/**
 * Required-Felder prüfen
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        sendError('Fehlende Pflichtfelder: ' . implode(', ', $missing), 400);
    }
}

/**
 * Sicheren Zufalls-String generieren
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes(ceil($length / 2)));
}

/**
 * Passwort hashen
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Passwort verifizieren
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
