<?php
/**
 * Code Lab - Aufgaben-API
 *
 * Endpunkte:
 * GET /api/tasks.php - Alle Aufgaben abrufen (Lehrer: eigene, Schüler: zugewiesene)
 * GET /api/tasks.php?id=123 - Einzelne Aufgabe abrufen
 * POST /api/tasks.php - Neue Aufgabe erstellen (nur Lehrer)
 * PUT /api/tasks.php?id=123 - Aufgabe aktualisieren (nur Lehrer)
 * DELETE /api/tasks.php?id=123 - Aufgabe löschen (nur Lehrer)
 * POST /api/tasks.php?action=assign - Aufgabe zuweisen (nur Lehrer)
 * GET /api/tasks.php?action=share&code=abc123 - Aufgabe per Share-Code abrufen
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$db = Database::getInstance();
$user = requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$taskId = $_GET['id'] ?? null;

// Routing
if ($action === 'assign') {
    handleAssign();
} elseif ($action === 'share') {
    handleShare();
} else {
    switch ($method) {
        case 'GET':
            $taskId ? getTask($taskId) : getTasks();
            break;

        case 'POST':
            createTask();
            break;

        case 'PUT':
            updateTask($taskId);
            break;

        case 'DELETE':
            deleteTask($taskId);
            break;

        default:
            sendError('Ungültige HTTP-Methode', 405);
    }
}

/**
 * Alle Aufgaben abrufen
 */
function getTasks() {
    global $db, $user;

    if ($user['role'] === 'teacher') {
        // Lehrer sieht eigene Aufgaben
        $tasks = $db->fetchAll(
            "SELECT t.*,
                    COUNT(DISTINCT ta.student_id) as assigned_students,
                    COUNT(DISTINCT s.id) as total_submissions
             FROM tasks t
             LEFT JOIN task_assignments ta ON t.id = ta.task_id
             LEFT JOIN submissions s ON t.id = s.task_id
             WHERE t.teacher_id = ?
             GROUP BY t.id
             ORDER BY t.created_at DESC",
            [$user['id']]
        );
    } else {
        // Schüler sieht zugewiesene Aufgaben
        $tasks = $db->fetchAll(
            "SELECT t.*,
                    ta.deadline,
                    ta.assigned_at,
                    (SELECT COUNT(*) FROM submissions WHERE task_id = t.id AND student_id = ?) as my_submissions,
                    (SELECT MAX(points_earned) FROM submissions WHERE task_id = t.id AND student_id = ?) as my_best_score
             FROM tasks t
             JOIN task_assignments ta ON t.id = ta.task_id
             WHERE ta.student_id = ? AND t.is_active = 1
             ORDER BY ta.deadline ASC, t.created_at DESC",
            [$user['id'], $user['id'], $user['id']]
        );
    }

    // Hints als Array parsen
    foreach ($tasks as &$task) {
        $task['hints'] = json_decode($task['hints'] ?? '[]', true);
    }

    sendSuccess($tasks);
}

/**
 * Einzelne Aufgabe abrufen
 */
function getTask($taskId) {
    global $db, $user;

    if (!$taskId) {
        sendError('Aufgaben-ID erforderlich', 400);
    }

    // Basis-Abfrage
    $task = $db->fetchOne(
        "SELECT t.* FROM tasks t WHERE t.id = ?",
        [$taskId]
    );

    if (!$task) {
        sendError('Aufgabe nicht gefunden', 404);
    }

    // Zugriffsrechte prüfen
    if ($user['role'] === 'teacher') {
        // Lehrer kann nur eigene Aufgaben sehen
        if ($task['teacher_id'] != $user['id']) {
            sendError('Keine Berechtigung', 403);
        }
    } else {
        // Schüler kann nur zugewiesene Aufgaben sehen
        $assignment = $db->fetchOne(
            "SELECT * FROM task_assignments WHERE task_id = ? AND student_id = ?",
            [$taskId, $user['id']]
        );

        if (!$assignment) {
            sendError('Keine Berechtigung', 403);
        }

        $task['deadline'] = $assignment['deadline'];
        $task['assigned_at'] = $assignment['assigned_at'];

        // Eigene Submissions
        $task['my_submissions'] = $db->fetchAll(
            "SELECT id, points_earned, max_points, submitted_at, status, feedback, time_spent
             FROM submissions
             WHERE task_id = ? AND student_id = ?
             ORDER BY submitted_at DESC",
            [$taskId, $user['id']]
        );
    }

    // Hints parsen
    $task['hints'] = json_decode($task['hints'] ?? '[]', true);

    // Aktivität loggen
    logActivity($user['id'], 'task_view', $taskId);

    sendSuccess($task);
}

/**
 * Neue Aufgabe erstellen (nur Lehrer)
 */
function createTask() {
    global $db, $user;

    if ($user['role'] !== 'teacher') {
        sendError('Nur Lehrer können Aufgaben erstellen', 403);
    }

    $data = getJsonInput();
    validateRequired($data, ['title', 'programming_language', 'task_content']);

    // Share-Code generieren
    $shareCode = generateUniqueShareCode();

    // Hints als JSON speichern
    $hints = json_encode($data['hints'] ?? []);

    try {
        $db->execute(
            "INSERT INTO tasks (teacher_id, title, description, programming_language, task_content,
                               task_type, expected_output, hints, max_points, time_limit, difficulty, share_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $user['id'],
                $data['title'],
                $data['description'] ?? null,
                $data['programming_language'],
                $data['task_content'],
                $data['task_type'] ?? 'plaintext',
                $data['expected_output'] ?? null,
                $hints,
                $data['max_points'] ?? 100,
                $data['time_limit'] ?? null,
                $data['difficulty'] ?? 'beginner',
                $shareCode
            ]
        );

        $taskId = $db->lastInsertId();

        sendSuccess([
            'task_id' => $taskId,
            'share_code' => $shareCode
        ], 'Aufgabe erfolgreich erstellt');

    } catch (Exception $e) {
        error_log("Task creation failed: " . $e->getMessage());
        sendError('Aufgabe konnte nicht erstellt werden', 500);
    }
}

/**
 * Aufgabe aktualisieren (nur Lehrer)
 */
function updateTask($taskId) {
    global $db, $user;

    if ($user['role'] !== 'teacher') {
        sendError('Nur Lehrer können Aufgaben aktualisieren', 403);
    }

    if (!$taskId) {
        sendError('Aufgaben-ID erforderlich', 400);
    }

    // Prüfen ob Aufgabe dem Lehrer gehört
    $task = $db->fetchOne(
        "SELECT id FROM tasks WHERE id = ? AND teacher_id = ?",
        [$taskId, $user['id']]
    );

    if (!$task) {
        sendError('Aufgabe nicht gefunden oder keine Berechtigung', 404);
    }

    $data = getJsonInput();

    // Update-Felder dynamisch erstellen
    $updates = [];
    $params = [];

    $allowedFields = ['title', 'description', 'programming_language', 'task_content',
                      'task_type', 'expected_output', 'max_points', 'time_limit',
                      'difficulty', 'is_active'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (isset($data['hints'])) {
        $updates[] = "hints = ?";
        $params[] = json_encode($data['hints']);
    }

    if (empty($updates)) {
        sendError('Keine Felder zum Aktualisieren', 400);
    }

    $params[] = $taskId;

    try {
        $db->execute(
            "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );

        sendSuccess([], 'Aufgabe erfolgreich aktualisiert');

    } catch (Exception $e) {
        error_log("Task update failed: " . $e->getMessage());
        sendError('Aufgabe konnte nicht aktualisiert werden', 500);
    }
}

/**
 * Aufgabe löschen (nur Lehrer)
 */
function deleteTask($taskId) {
    global $db, $user;

    if ($user['role'] !== 'teacher') {
        sendError('Nur Lehrer können Aufgaben löschen', 403);
    }

    if (!$taskId) {
        sendError('Aufgaben-ID erforderlich', 400);
    }

    // Prüfen ob Aufgabe dem Lehrer gehört
    $task = $db->fetchOne(
        "SELECT id FROM tasks WHERE id = ? AND teacher_id = ?",
        [$taskId, $user['id']]
    );

    if (!$task) {
        sendError('Aufgabe nicht gefunden oder keine Berechtigung', 404);
    }

    try {
        $db->execute("DELETE FROM tasks WHERE id = ?", [$taskId]);
        sendSuccess([], 'Aufgabe erfolgreich gelöscht');

    } catch (Exception $e) {
        error_log("Task deletion failed: " . $e->getMessage());
        sendError('Aufgabe konnte nicht gelöscht werden', 500);
    }
}

/**
 * Aufgabe Schülern zuweisen (nur Lehrer)
 */
function handleAssign() {
    global $db, $user;

    if ($user['role'] !== 'teacher') {
        sendError('Nur Lehrer können Aufgaben zuweisen', 403);
    }

    $data = getJsonInput();
    validateRequired($data, ['task_id', 'student_ids']);

    $taskId = $data['task_id'];
    $studentIds = $data['student_ids']; // Array von Student-IDs
    $deadline = $data['deadline'] ?? null;

    // Prüfen ob Aufgabe dem Lehrer gehört
    $task = $db->fetchOne(
        "SELECT id FROM tasks WHERE id = ? AND teacher_id = ?",
        [$taskId, $user['id']]
    );

    if (!$task) {
        sendError('Aufgabe nicht gefunden oder keine Berechtigung', 404);
    }

    try {
        $assigned = 0;
        foreach ($studentIds as $studentId) {
            // Prüfen ob Student existiert
            $student = $db->fetchOne(
                "SELECT id FROM users WHERE id = ? AND role = 'student'",
                [$studentId]
            );

            if (!$student) {
                continue;
            }

            // Zuweisen (ON DUPLICATE KEY UPDATE für bereits zugewiesene)
            $db->execute(
                "INSERT INTO task_assignments (task_id, student_id, deadline)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE deadline = ?",
                [$taskId, $studentId, $deadline, $deadline]
            );

            $assigned++;
        }

        sendSuccess([
            'assigned_count' => $assigned
        ], "$assigned Schüler(n) zugewiesen");

    } catch (Exception $e) {
        error_log("Task assignment failed: " . $e->getMessage());
        sendError('Zuweisung fehlgeschlagen', 500);
    }
}

/**
 * Aufgabe per Share-Code abrufen
 */
function handleShare() {
    global $db;

    $shareCode = $_GET['code'] ?? '';

    if (!$shareCode) {
        sendError('Share-Code erforderlich', 400);
    }

    $task = $db->fetchOne(
        "SELECT id, title, description, programming_language, difficulty, max_points, time_limit
         FROM tasks
         WHERE share_code = ? AND is_active = 1",
        [$shareCode]
    );

    if (!$task) {
        sendError('Aufgabe nicht gefunden', 404);
    }

    sendSuccess($task);
}

/**
 * Eindeutigen Share-Code generieren
 */
function generateUniqueShareCode() {
    global $db;

    do {
        $code = strtoupper(substr(generateRandomString(10), 0, 8));
        $exists = $db->fetchOne("SELECT id FROM tasks WHERE share_code = ?", [$code]);
    } while ($exists);

    return $code;
}
