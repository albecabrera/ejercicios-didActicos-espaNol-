<?php
/**
 * API para Sistema de Ejercicios Didácticos
 *
 * Endpoints:
 * - POST /api.php?action=register_student - Registrar/obtener estudiante
 * - POST /api.php?action=start_exercise - Registrar inicio de ejercicio
 * - POST /api.php?action=complete_exercise - Registrar ejercicio completado
 * - GET /api.php?action=get_student&id={id} - Obtener datos de estudiante
 */

require_once 'config.php';

// Obtener acción
$action = $_GET['action'] ?? '';

// Obtener datos del cuerpo de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

switch ($action) {
    case 'register_student':
        registerStudent($data);
        break;

    case 'start_exercise':
        startExercise($data);
        break;

    case 'complete_exercise':
        completeExercise($data);
        break;

    case 'get_student':
        getStudent($_GET['id'] ?? null);
        break;

    default:
        sendResponse([
            'success' => false,
            'error' => 'Acción no válida'
        ], 400);
}

/**
 * Registrar o obtener estudiante
 */
function registerStudent($data) {
    $missing = validateRequired($data, ['nombre']);
    if (!empty($missing)) {
        sendResponse([
            'success' => false,
            'error' => 'Faltan campos requeridos: ' . implode(', ', $missing)
        ], 400);
    }

    $pdo = getDBConnection();
    $nombre = trim($data['nombre']);

    // Extraer primer nombre
    $primerNombre = explode(' ', $nombre)[0];

    // Buscar si el estudiante ya existe
    $stmt = $pdo->prepare("SELECT id, nombre, primer_nombre FROM estudiantes WHERE nombre = ?");
    $stmt->execute([$nombre]);
    $estudiante = $stmt->fetch();

    if ($estudiante) {
        // Estudiante ya existe
        sendResponse([
            'success' => true,
            'estudiante' => $estudiante,
            'nuevo' => false
        ]);
    } else {
        // Crear nuevo estudiante
        $stmt = $pdo->prepare("INSERT INTO estudiantes (nombre, primer_nombre) VALUES (?, ?)");
        $stmt->execute([$nombre, $primerNombre]);

        $estudianteId = $pdo->lastInsertId();

        sendResponse([
            'success' => true,
            'estudiante' => [
                'id' => $estudianteId,
                'nombre' => $nombre,
                'primer_nombre' => $primerNombre
            ],
            'nuevo' => true
        ]);
    }
}

/**
 * Registrar inicio de ejercicio
 */
function startExercise($data) {
    $missing = validateRequired($data, ['estudiante_id', 'ejercicio_id', 'ejercicio_titulo']);
    if (!empty($missing)) {
        sendResponse([
            'success' => false,
            'error' => 'Faltan campos requeridos: ' . implode(', ', $missing)
        ], 400);
    }

    $pdo = getDBConnection();

    // Insertar registro de inicio
    $stmt = $pdo->prepare("
        INSERT INTO ejercicios_iniciados (estudiante_id, ejercicio_id, ejercicio_titulo)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $data['estudiante_id'],
        $data['ejercicio_id'],
        $data['ejercicio_titulo']
    ]);

    $inicioId = $pdo->lastInsertId();

    sendResponse([
        'success' => true,
        'inicio_id' => $inicioId,
        'mensaje' => 'Ejercicio iniciado correctamente'
    ]);
}

/**
 * Registrar ejercicio completado
 */
function completeExercise($data) {
    $missing = validateRequired($data, [
        'estudiante_id',
        'ejercicio_id',
        'ejercicio_titulo',
        'resultado'
    ]);

    if (!empty($missing)) {
        sendResponse([
            'success' => false,
            'error' => 'Faltan campos requeridos: ' . implode(', ', $missing)
        ], 400);
    }

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // 1. Eliminar todos los intentos no completados de este ejercicio por este estudiante
        $stmt = $pdo->prepare("
            DELETE FROM ejercicios_iniciados
            WHERE estudiante_id = ?
            AND ejercicio_id = ?
            AND completado = 0
        ");
        $stmt->execute([$data['estudiante_id'], $data['ejercicio_id']]);

        // 2. Insertar resultado del ejercicio completado
        $resultado = is_array($data['resultado']) ? json_encode($data['resultado'], JSON_UNESCAPED_UNICODE) : $data['resultado'];

        $stmt = $pdo->prepare("
            INSERT INTO resultados (
                estudiante_id,
                ejercicio_id,
                ejercicio_titulo,
                resultado,
                puntuacion,
                nivel,
                tiempo_transcurrido
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['estudiante_id'],
            $data['ejercicio_id'],
            $data['ejercicio_titulo'],
            $resultado,
            $data['puntuacion'] ?? null,
            $data['nivel'] ?? null,
            $data['tiempo_transcurrido'] ?? null
        ]);

        // 3. Marcar un ejercicio como completado (crear nuevo registro)
        $stmt = $pdo->prepare("
            INSERT INTO ejercicios_iniciados (
                estudiante_id,
                ejercicio_id,
                ejercicio_titulo,
                completado
            ) VALUES (?, ?, ?, 1)
        ");

        $stmt->execute([
            $data['estudiante_id'],
            $data['ejercicio_id'],
            $data['ejercicio_titulo']
        ]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'mensaje' => 'Ejercicio completado correctamente',
            'resultado_id' => $pdo->lastInsertId()
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        sendResponse([
            'success' => false,
            'error' => 'Error al guardar el resultado',
            'message' => $e->getMessage()
        ], 500);
    }
}

/**
 * Obtener datos de estudiante
 */
function getStudent($id) {
    if (!$id) {
        sendResponse([
            'success' => false,
            'error' => 'ID de estudiante no proporcionado'
        ], 400);
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM vista_estudiantes WHERE id = ?");
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch();

    if ($estudiante) {
        sendResponse([
            'success' => true,
            'estudiante' => $estudiante
        ]);
    } else {
        sendResponse([
            'success' => false,
            'error' => 'Estudiante no encontrado'
        ], 404);
    }
}
