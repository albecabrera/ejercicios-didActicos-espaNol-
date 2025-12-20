<?php
/**
 * Endpoint de autenticación
 */

require_once 'config.php';

startSession();

$action = $_GET['action'] ?? '';

// Obtener datos del cuerpo de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

switch ($action) {
    case 'login':
        login($data);
        break;

    case 'logout':
        logout();
        break;

    case 'check':
        checkAuth();
        break;

    default:
        sendResponse([
            'success' => false,
            'error' => 'Acción no válida'
        ], 400);
}

/**
 * Iniciar sesión
 */
function login($data) {
    $missing = validateRequired($data, ['username', 'password']);
    if (!empty($missing)) {
        sendResponse([
            'success' => false,
            'error' => 'Faltan campos requeridos'
        ], 400);
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute([$data['username']]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($data['password'], $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        sendResponse([
            'success' => true,
            'message' => 'Autenticación exitosa',
            'username' => $admin['username']
        ]);
    } else {
        sendResponse([
            'success' => false,
            'error' => 'Credenciales inválidas'
        ], 401);
    }
}

/**
 * Cerrar sesión
 */
function logout() {
    $_SESSION = [];
    session_destroy();

    sendResponse([
        'success' => true,
        'message' => 'Sesión cerrada'
    ]);
}

/**
 * Verificar autenticación
 */
function checkAuth() {
    if (isAuthenticated()) {
        sendResponse([
            'success' => true,
            'authenticated' => true,
            'username' => $_SESSION['admin_username'] ?? null
        ]);
    } else {
        sendResponse([
            'success' => true,
            'authenticated' => false
        ]);
    }
}
