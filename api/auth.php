<?php
/**
 * Code Lab - Authentifizierungs-API
 *
 * Endpunkte:
 * POST /api/auth.php?action=register - Registrierung
 * POST /api/auth.php?action=login - Login
 * POST /api/auth.php?action=logout - Logout
 * GET /api/auth.php?action=verify - Session verifizieren
 * GET /api/auth.php?action=profile - Benutzerprofil abrufen
 */

require_once __DIR__ . '/config.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;

    case 'login':
        handleLogin();
        break;

    case 'logout':
        handleLogout();
        break;

    case 'verify':
        handleVerify();
        break;

    case 'profile':
        handleProfile();
        break;

    default:
        sendError('Ungültige Aktion', 400);
}

/**
 * Benutzer registrieren
 */
function handleRegister() {
    global $db;

    $data = getJsonInput();
    validateRequired($data, ['username', 'email', 'password', 'role', 'full_name']);

    // Validierung
    if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        sendError('Passwort muss mindestens ' . PASSWORD_MIN_LENGTH . ' Zeichen lang sein');
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        sendError('Ungültige E-Mail-Adresse');
    }

    if (!in_array($data['role'], ['student', 'teacher'])) {
        sendError('Ungültige Rolle');
    }

    // Prüfen ob Benutzer bereits existiert
    $existing = $db->fetchOne(
        "SELECT id FROM users WHERE username = ? OR email = ?",
        [$data['username'], $data['email']]
    );

    if ($existing) {
        sendError('Benutzername oder E-Mail bereits vergeben', 409);
    }

    // Benutzer erstellen
    $passwordHash = hashPassword($data['password']);
    $preferredLanguage = $data['preferred_language'] ?? 'de';

    try {
        $db->execute(
            "INSERT INTO users (username, email, password_hash, role, full_name, preferred_language)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$data['username'], $data['email'], $passwordHash, $data['role'], $data['full_name'], $preferredLanguage]
        );

        $userId = $db->lastInsertId();

        // Session erstellen
        $sessionToken = createSession($userId);

        sendSuccess([
            'user_id' => $userId,
            'username' => $data['username'],
            'role' => $data['role'],
            'session_token' => $sessionToken
        ], 'Registrierung erfolgreich');

    } catch (Exception $e) {
        error_log("Registration failed: " . $e->getMessage());
        sendError('Registrierung fehlgeschlagen', 500);
    }
}

/**
 * Benutzer einloggen
 */
function handleLogin() {
    global $db;

    $data = getJsonInput();
    validateRequired($data, ['username', 'password']);

    // Benutzer abrufen
    $user = $db->fetchOne(
        "SELECT id, username, email, password_hash, role, full_name, preferred_language
         FROM users
         WHERE username = ? OR email = ?",
        [$data['username'], $data['username']]
    );

    if (!$user || !verifyPassword($data['password'], $user['password_hash'])) {
        // Aktivität loggen (fehlgeschlagener Login)
        if ($user) {
            logActivity($user['id'], 'login_failed');
        }
        sendError('Ungültige Anmeldedaten', 401);
    }

    // Session erstellen
    $sessionToken = createSession($user['id']);

    // Last login aktualisieren
    $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

    // Aktivität loggen
    logActivity($user['id'], 'login');

    sendSuccess([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'full_name' => $user['full_name'],
        'preferred_language' => $user['preferred_language'],
        'session_token' => $sessionToken
    ], 'Login erfolgreich');
}

/**
 * Benutzer ausloggen
 */
function handleLogout() {
    global $db;

    $sessionToken = getAuthToken();
    if (!$sessionToken) {
        sendError('Keine Session gefunden', 401);
    }

    $session = $db->fetchOne(
        "SELECT user_id FROM sessions WHERE session_token = ?",
        [$sessionToken]
    );

    if ($session) {
        // Aktivität loggen
        logActivity($session['user_id'], 'logout');

        // Session löschen
        $db->execute("DELETE FROM sessions WHERE session_token = ?", [$sessionToken]);
    }

    sendSuccess([], 'Logout erfolgreich');
}

/**
 * Session verifizieren
 */
function handleVerify() {
    $user = requireAuth();
    sendSuccess([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ]);
}

/**
 * Benutzerprofil abrufen
 */
function handleProfile() {
    global $db;
    $user = requireAuth();

    $profile = $db->fetchOne(
        "SELECT id, username, email, role, full_name, preferred_language, created_at, last_login
         FROM users
         WHERE id = ?",
        [$user['id']]
    );

    // Statistiken für Schüler
    if ($user['role'] === 'student') {
        $stats = $db->fetchOne(
            "SELECT
                COUNT(*) as total_submissions,
                AVG(points_earned) as avg_points,
                SUM(time_spent) as total_time,
                COUNT(DISTINCT task_id) as tasks_attempted
             FROM submissions
             WHERE student_id = ?",
            [$user['id']]
        );

        $profile['statistics'] = $stats;
    }

    // Statistiken für Lehrer
    if ($user['role'] === 'teacher') {
        $stats = $db->fetchOne(
            "SELECT
                COUNT(DISTINCT t.id) as total_tasks,
                COUNT(DISTINCT ta.student_id) as total_students,
                COUNT(DISTINCT s.id) as total_submissions
             FROM tasks t
             LEFT JOIN task_assignments ta ON t.id = ta.task_id
             LEFT JOIN submissions s ON t.id = s.task_id
             WHERE t.teacher_id = ?",
            [$user['id']]
        );

        $profile['statistics'] = $stats;
    }

    sendSuccess($profile);
}

/**
 * Session erstellen
 */
function createSession($userId) {
    global $db;

    $sessionToken = generateRandomString(32);
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $db->execute(
        "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at)
         VALUES (?, ?, ?, ?, ?)",
        [$userId, $sessionToken, $ipAddress, $userAgent, $expiresAt]
    );

    // Alte Sessions aufräumen
    $db->execute("DELETE FROM sessions WHERE expires_at < NOW()");

    return $sessionToken;
}

/**
 * Auth-Token aus Header/Cookie holen
 */
function getAuthToken() {
    // Authorization Header prüfen
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }

    // Cookie prüfen
    if (isset($_COOKIE['session_token'])) {
        return $_COOKIE['session_token'];
    }

    return null;
}

/**
 * Authentifizierung erzwingen
 */
function requireAuth() {
    global $db;

    $sessionToken = getAuthToken();
    if (!$sessionToken) {
        sendError('Nicht authentifiziert', 401);
    }

    $session = $db->fetchOne(
        "SELECT s.user_id, s.expires_at, u.username, u.role
         FROM sessions s
         JOIN users u ON s.user_id = u.id
         WHERE s.session_token = ? AND s.expires_at > NOW()",
        [$sessionToken]
    );

    if (!$session) {
        sendError('Session ungültig oder abgelaufen', 401);
    }

    return [
        'id' => $session['user_id'],
        'username' => $session['username'],
        'role' => $session['role']
    ];
}

/**
 * Aktivität loggen
 */
function logActivity($userId, $activityType, $taskId = null, $details = null) {
    global $db;

    $detailsJson = $details ? json_encode($details) : null;

    try {
        $db->execute(
            "INSERT INTO activity_log (user_id, activity_type, task_id, details)
             VALUES (?, ?, ?, ?)",
            [$userId, $activityType, $taskId, $detailsJson]
        );
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
