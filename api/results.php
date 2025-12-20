<?php
/**
 * Code Lab - Ergebnisse/Submissions-API
 *
 * Endpunkte:
 * POST /api/results.php - Lösung einreichen (Schüler)
 * GET /api/results.php?task_id=123 - Ergebnisse für Aufgabe abrufen
 * GET /api/results.php?action=statistics&task_id=123 - Statistiken für Aufgabe (Lehrer)
 * GET /api/results.php?action=dashboard - Dashboard-Statistiken (Lehrer)
 * GET /api/results.php?action=student_stats&student_id=123 - Schüler-Statistiken (Lehrer)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$db = Database::getInstance();
$user = requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Routing
switch ($action) {
    case 'statistics':
        getTaskStatistics();
        break;

    case 'dashboard':
        getDashboard();
        break;

    case 'student_stats':
        getStudentStats();
        break;

    default:
        if ($method === 'POST') {
            submitSolution();
        } elseif ($method === 'GET') {
            getResults();
        } else {
            sendError('Ungültige HTTP-Methode', 405);
        }
}

/**
 * Lösung einreichen (Schüler)
 */
function submitSolution() {
    global $db, $user;

    if ($user['role'] !== 'student') {
        sendError('Nur Schüler können Lösungen einreichen', 403);
    }

    $data = getJsonInput();
    validateRequired($data, ['task_id', 'code_solution']);

    $taskId = $data['task_id'];
    $codeSolution = $data['code_solution'];
    $executionOutput = $data['execution_output'] ?? null;
    $timeSpent = $data['time_spent'] ?? 0;

    // Prüfen ob Aufgabe zugewiesen ist
    $assignment = $db->fetchOne(
        "SELECT ta.*, t.max_points, t.expected_output
         FROM task_assignments ta
         JOIN tasks t ON ta.task_id = t.id
         WHERE ta.task_id = ? AND ta.student_id = ?",
        [$taskId, $user['id']]
    );

    if (!$assignment) {
        sendError('Aufgabe nicht zugewiesen', 403);
    }

    // Deadline prüfen (optional)
    if ($assignment['deadline'] && strtotime($assignment['deadline']) < time()) {
        // Warnung, aber trotzdem erlauben
        $pastDeadline = true;
    }

    // Anzahl Versuche ermitteln
    $attempts = $db->fetchOne(
        "SELECT COUNT(*) as count FROM submissions WHERE task_id = ? AND student_id = ?",
        [$taskId, $user['id']]
    )['count'] + 1;

    // Automatische Bewertung (vereinfacht)
    $pointsEarned = 0;
    $isPassed = false;

    if ($assignment['expected_output'] && $executionOutput) {
        // Einfacher String-Vergleich (kann erweitert werden)
        $similarity = similar_text($assignment['expected_output'], $executionOutput, $percent);
        $pointsEarned = round(($percent / 100) * $assignment['max_points']);
        $isPassed = $percent >= 80; // 80% Ähnlichkeit = bestanden
    }

    try {
        $db->execute(
            "INSERT INTO submissions (task_id, student_id, code_solution, execution_output,
                                     points_earned, max_points, time_spent, attempts, is_passed)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$taskId, $user['id'], $codeSolution, $executionOutput, $pointsEarned,
             $assignment['max_points'], $timeSpent, $attempts, $isPassed]
        );

        $submissionId = $db->lastInsertId();

        // Aktivität loggen
        logActivity($user['id'], 'task_submit', $taskId, [
            'submission_id' => $submissionId,
            'points' => $pointsEarned
        ]);

        sendSuccess([
            'submission_id' => $submissionId,
            'points_earned' => $pointsEarned,
            'max_points' => $assignment['max_points'],
            'is_passed' => $isPassed,
            'attempts' => $attempts
        ], 'Lösung erfolgreich eingereicht');

    } catch (Exception $e) {
        error_log("Submission failed: " . $e->getMessage());
        sendError('Einreichung fehlgeschlagen', 500);
    }
}

/**
 * Ergebnisse abrufen
 */
function getResults() {
    global $db, $user;

    $taskId = $_GET['task_id'] ?? null;

    if ($user['role'] === 'student') {
        // Schüler sieht nur eigene Ergebnisse
        if (!$taskId) {
            sendError('task_id erforderlich', 400);
        }

        $results = $db->fetchAll(
            "SELECT id, task_id, code_solution, submitted_at, execution_output,
                    points_earned, max_points, status, time_spent, attempts, feedback, is_passed
             FROM submissions
             WHERE task_id = ? AND student_id = ?
             ORDER BY submitted_at DESC",
            [$taskId, $user['id']]
        );

        sendSuccess($results);

    } else {
        // Lehrer sieht alle Ergebnisse
        if (!$taskId) {
            sendError('task_id erforderlich', 400);
        }

        // Prüfen ob Aufgabe dem Lehrer gehört
        $task = $db->fetchOne(
            "SELECT id FROM tasks WHERE id = ? AND teacher_id = ?",
            [$taskId, $user['id']]
        );

        if (!$task) {
            sendError('Keine Berechtigung', 403);
        }

        $results = $db->fetchAll(
            "SELECT s.*, u.username, u.full_name
             FROM submissions s
             JOIN users u ON s.student_id = u.id
             WHERE s.task_id = ?
             ORDER BY s.submitted_at DESC",
            [$taskId]
        );

        sendSuccess($results);
    }
}

/**
 * Statistiken für Aufgabe (Lehrer)
 */
function getTaskStatistics() {
    global $db, $user;

    if ($user['role'] !== 'teacher') {
        sendError('Nur Lehrer können Statistiken abrufen', 403);
    }

    $taskId = $_GET['task_id'] ?? null;
    if (!$taskId) {
        sendError('task_id erforderlich', 400);
    }

    // Prüfen ob Aufgabe dem Lehrer gehört
    $task = $db->fetchOne(
        "SELECT id, title, max_points FROM tasks WHERE id = ? AND teacher_id = ?",
        [$taskId, $user['id']]
    );

    if (!$task) {
        sendError('Keine Berechtigung', 403);
    }

    // Basis-Statistiken
    $stats = $db->fetchOne(
        "SELECT
            COUNT(DISTINCT student_id) as total_students,
            COUNT(*) as total_submissions,
            AVG(points_earned) as avg_points,
            MIN(points_earned) as min_points,
            MAX(points_earned) as max_points,
            AVG(time_spent) as avg_time,
            SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) as passed_count
         FROM submissions
         WHERE task_id = ?",
        [$taskId]
    );

    // Beste und schlechteste Leistung mit Schüler-Details
    $bestSubmission = $db->fetchOne(
        "SELECT s.points_earned, s.submitted_at, u.username, u.full_name
         FROM submissions s
         JOIN users u ON s.student_id = u.id
         WHERE s.task_id = ?
         ORDER BY s.points_earned DESC, s.submitted_at ASC
         LIMIT 1",
        [$taskId]
    );

    $worstSubmission = $db->fetchOne(
        "SELECT s.points_earned, s.submitted_at, u.username, u.full_name
         FROM submissions s
         JOIN users u ON s.student_id = u.id
         WHERE s.task_id = ?
         ORDER BY s.points_earned ASC, s.submitted_at DESC
         LIMIT 1",
        [$taskId]
    );

    // Punkteverteilung für Histogramm
    $distribution = $db->fetchAll(
        "SELECT
            FLOOR(points_earned / 10) * 10 as point_range,
            COUNT(*) as count
         FROM submissions
         WHERE task_id = ?
         GROUP BY point_range
         ORDER BY point_range",
        [$taskId]
    );

    // Submissions über Zeit
    $timeline = $db->fetchAll(
        "SELECT
            DATE(submitted_at) as date,
            COUNT(*) as submission_count,
            AVG(points_earned) as avg_points
         FROM submissions
         WHERE task_id = ?
         GROUP BY DATE(submitted_at)
         ORDER BY date",
        [$taskId]
    );

    // Schüler-Liste mit ihren besten Ergebnissen
    $studentResults = $db->fetchAll(
        "SELECT
            u.id,
            u.username,
            u.full_name,
            MAX(s.points_earned) as best_score,
            COUNT(s.id) as attempts,
            MAX(s.submitted_at) as last_submission,
            MAX(CASE WHEN s.is_passed = 1 THEN 1 ELSE 0 END) as has_passed
         FROM task_assignments ta
         JOIN users u ON ta.student_id = u.id
         LEFT JOIN submissions s ON ta.task_id = s.task_id AND ta.student_id = s.student_id
         WHERE ta.task_id = ?
         GROUP BY u.id, u.username, u.full_name
         ORDER BY best_score DESC, last_submission ASC",
        [$taskId]
    );

    sendSuccess([
        'task' => $task,
        'statistics' => $stats,
        'best_submission' => $bestSubmission,
        'worst_submission' => $worstSubmission,
        'distribution' => $distribution,
        'timeline' => $timeline,
        'student_results' => $studentResults
    ]);
}

/**
 * Dashboard-Statistiken für Lehrer
 */
function getDashboard() {
    global $db, $user;

    if ($user['role'] !== 'teacher') {
        sendError('Nur Lehrer können das Dashboard abrufen', 403);
    }

    // Gesamt-Übersicht
    $overview = $db->fetchOne(
        "SELECT
            COUNT(DISTINCT t.id) as total_tasks,
            COUNT(DISTINCT ta.student_id) as total_students,
            COUNT(DISTINCT s.id) as total_submissions,
            AVG(s.points_earned) as avg_score
         FROM tasks t
         LEFT JOIN task_assignments ta ON t.id = ta.task_id
         LEFT JOIN submissions s ON t.id = s.task_id
         WHERE t.teacher_id = ?",
        [$user['id']]
    );

    // Letzte Aktivitäten
    $recentSubmissions = $db->fetchAll(
        "SELECT s.*, t.title as task_title, u.username, u.full_name
         FROM submissions s
         JOIN tasks t ON s.task_id = t.id
         JOIN users u ON s.student_id = u.id
         WHERE t.teacher_id = ?
         ORDER BY s.submitted_at DESC
         LIMIT 10",
        [$user['id']]
    );

    // Aufgaben mit den meisten Submissions
    $popularTasks = $db->fetchAll(
        "SELECT t.id, t.title, t.programming_language,
                COUNT(s.id) as submission_count,
                AVG(s.points_earned) as avg_score
         FROM tasks t
         LEFT JOIN submissions s ON t.id = s.task_id
         WHERE t.teacher_id = ?
         GROUP BY t.id
         ORDER BY submission_count DESC
         LIMIT 5",
        [$user['id']]
    );

    // Schüler-Rangliste (basierend auf Durchschnittspunkten)
    $topStudents = $db->fetchAll(
        "SELECT u.id, u.username, u.full_name,
                COUNT(DISTINCT s.task_id) as tasks_completed,
                AVG(s.points_earned) as avg_score,
                SUM(s.time_spent) as total_time
         FROM users u
         JOIN submissions s ON u.id = s.student_id
         JOIN tasks t ON s.task_id = t.id
         WHERE t.teacher_id = ? AND u.role = 'student'
         GROUP BY u.id
         ORDER BY avg_score DESC
         LIMIT 10",
        [$user['id']]
    );

    sendSuccess([
        'overview' => $overview,
        'recent_submissions' => $recentSubmissions,
        'popular_tasks' => $popularTasks,
        'top_students' => $topStudents
    ]);
}

/**
 * Statistiken für einzelnen Schüler (Lehrer)
 */
function getStudentStats() {
    global $db, $user;

    if ($user['role'] !== 'teacher') {
        sendError('Nur Lehrer können Schüler-Statistiken abrufen', 403);
    }

    $studentId = $_GET['student_id'] ?? null;
    if (!$studentId) {
        sendError('student_id erforderlich', 400);
    }

    // Schüler-Info
    $student = $db->fetchOne(
        "SELECT id, username, full_name, email, created_at, last_login
         FROM users
         WHERE id = ? AND role = 'student'",
        [$studentId]
    );

    if (!$student) {
        sendError('Schüler nicht gefunden', 404);
    }

    // Statistiken für Aufgaben des Lehrers
    $stats = $db->fetchOne(
        "SELECT
            COUNT(DISTINCT s.task_id) as tasks_attempted,
            COUNT(s.id) as total_submissions,
            AVG(s.points_earned) as avg_score,
            SUM(s.time_spent) as total_time,
            SUM(CASE WHEN s.is_passed = 1 THEN 1 ELSE 0 END) as passed_count
         FROM submissions s
         JOIN tasks t ON s.task_id = t.id
         WHERE s.student_id = ? AND t.teacher_id = ?",
        [$studentId, $user['id']]
    );

    // Aufgaben-Details
    $taskDetails = $db->fetchAll(
        "SELECT
            t.id,
            t.title,
            t.programming_language,
            t.max_points,
            MAX(s.points_earned) as best_score,
            COUNT(s.id) as attempts,
            MAX(s.submitted_at) as last_attempt,
            MAX(CASE WHEN s.is_passed = 1 THEN 1 ELSE 0 END) as has_passed
         FROM tasks t
         LEFT JOIN submissions s ON t.id = s.task_id AND s.student_id = ?
         WHERE t.teacher_id = ?
         GROUP BY t.id
         ORDER BY last_attempt DESC",
        [$studentId, $user['id']]
    );

    sendSuccess([
        'student' => $student,
        'statistics' => $stats,
        'task_details' => $taskDetails
    ]);
}
