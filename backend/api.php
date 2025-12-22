<?php
/**
 * API para Sistema de Ejercicios Didácticos
 *
 * Endpoints:
 * - POST /api.php?action=start_exercise - Registrar inicio de ejercicio
 * - POST /api.php?action=complete_exercise - Registrar ejercicio completado
 * - GET /api.php?action=get_student&nombre={nombre} - Obtener datos de estudiante
 */

require_once 'config.php';

// Habilitar reporte de errores para debugging (TEMPORAL)
ini_set('display_errors', 0); // No mostrar en pantalla
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error fatal de PHP',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ], JSON_UNESCAPED_UNICODE);
    }
});

// Obtener acción
$action = $_GET['action'] ?? '';

// Obtener datos del cuerpo de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

switch ($action) {
    case 'start_exercise':
        startExercise($data);
        break;

    case 'complete_exercise':
        completeExercise($data);
        break;

    case 'get_student':
        getStudent($_GET['nombre'] ?? null);
        break;

    default:
        sendResponse([
            'success' => false,
            'error' => 'Acción no válida'
        ], 400);
}

/**
 * Obtener o crear estudiante por nombre
 */
function getOrCreateStudent($nombre) {
    $pdo = getDBConnection();
    $nombre = trim($nombre);
    $primerNombre = explode(' ', $nombre)[0];

    // Buscar si el estudiante ya existe
    $stmt = $pdo->prepare("SELECT id, nombre, primer_nombre FROM estudiantes WHERE nombre = ?");
    $stmt->execute([$nombre]);
    $estudiante = $stmt->fetch();

    if ($estudiante) {
        return $estudiante;
    }

    // Crear nuevo estudiante
    $stmt = $pdo->prepare("INSERT INTO estudiantes (nombre, primer_nombre) VALUES (?, ?)");
    $stmt->execute([$nombre, $primerNombre]);

    return [
        'id' => $pdo->lastInsertId(),
        'nombre' => $nombre,
        'primer_nombre' => $primerNombre
    ];
}

/**
 * Registrar inicio de ejercicio
 */
function startExercise($data) {
    $missing = validateRequired($data, ['estudiante_nombre', 'ejercicio_id', 'ejercicio_titulo']);
    if (!empty($missing)) {
        sendResponse([
            'success' => false,
            'error' => 'Faltan campos requeridos: ' . implode(', ', $missing)
        ], 400);
    }

    $pdo = getDBConnection();

    // Obtener o crear estudiante
    $estudiante = getOrCreateStudent($data['estudiante_nombre']);

    // Insertar registro de inicio
    $stmt = $pdo->prepare("
        INSERT INTO ejercicios_iniciados (estudiante_id, ejercicio_id, ejercicio_titulo)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $estudiante['id'],
        $data['ejercicio_id'],
        $data['ejercicio_titulo']
    ]);

    $inicioId = $pdo->lastInsertId();

    sendResponse([
        'success' => true,
        'inicio_id' => $inicioId,
        'estudiante_nombre' => $estudiante['nombre'],
        'mensaje' => 'Ejercicio iniciado correctamente'
    ]);
}

/**
 * Registrar ejercicio completado
 */
function completeExercise($data) {
    $missing = validateRequired($data, [
        'estudiante_nombre',
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

        // Obtener o crear estudiante
        $estudiante = getOrCreateStudent($data['estudiante_nombre']);

        // 1. Eliminar todos los intentos no completados de este ejercicio por este estudiante
        $stmt = $pdo->prepare("
            DELETE FROM ejercicios_iniciados
            WHERE estudiante_id = ?
            AND ejercicio_id = ?
            AND completado = 0
        ");
        $stmt->execute([$estudiante['id'], $data['ejercicio_id']]);

        // 2. Insertar resultado del ejercicio completado
        if (is_array($data['resultado'])) {
            $resultado = json_encode($data['resultado'], JSON_UNESCAPED_UNICODE);
            if ($resultado === false) {
                throw new Exception('Error al codificar resultado como JSON: ' . json_last_error_msg());
            }
        } else {
            $resultado = $data['resultado'];
        }

        // Preparar valores con conversión de tipos apropiada
        $puntuacion = isset($data['puntuacion']) ? (int)$data['puntuacion'] : null;
        $tiempoTranscurrido = isset($data['tiempo_transcurrido']) ? (int)$data['tiempo_transcurrido'] : null;

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
            $estudiante['id'],
            $data['ejercicio_id'],
            $data['ejercicio_titulo'],
            $resultado,
            $puntuacion,
            $data['nivel'] ?? null,
            $tiempoTranscurrido
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
            $estudiante['id'],
            $data['ejercicio_id'],
            $data['ejercicio_titulo']
        ]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'mensaje' => 'Ejercicio completado correctamente',
            'estudiante_nombre' => $estudiante['nombre'],
            'resultado_id' => $pdo->lastInsertId()
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Log del error para debugging
        error_log('Error PDO en completeExercise: ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());

        sendResponse([
            'success' => false,
            'error' => 'Error de base de datos al guardar el resultado',
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'details' => [
                'estudiante' => $data['estudiante_nombre'] ?? 'N/A',
                'ejercicio_id' => $data['ejercicio_id'] ?? 'N/A',
                'sql_state' => $e->getCode()
            ]
        ], 500);
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Log del error para debugging
        error_log('Error en completeExercise: ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());

        sendResponse([
            'success' => false,
            'error' => 'Error al guardar el resultado',
            'message' => $e->getMessage(),
            'type' => get_class($e),
            'details' => [
                'estudiante' => $data['estudiante_nombre'] ?? 'N/A',
                'ejercicio_id' => $data['ejercicio_id'] ?? 'N/A'
            ]
        ], 500);
    }
}

/**
 * Obtener datos de estudiante por nombre
 */
function getStudent($nombre) {
    if (!$nombre) {
        sendResponse([
            'success' => false,
            'error' => 'Nombre de estudiante no proporcionado'
        ], 400);
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM vista_estudiantes WHERE nombre = ?");
    $stmt->execute([$nombre]);
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
