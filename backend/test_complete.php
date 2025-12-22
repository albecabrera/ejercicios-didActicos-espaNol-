<?php
/**
 * Script de prueba para diagnosticar el error en complete_exercise
 * Ejecutar: php test_complete.php
 * O acceder vía web: http://backend.patchamama.com/ejercicios-didacticos/backend/test_complete.php
 */

// Habilitar todos los errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Datos de prueba (los mismos que envía el frontend)
$testData = [
    'estudiante_nombre' => 'Albe',
    'ejercicio_id' => 'mi-barrio',
    'ejercicio_titulo' => 'Madrid Abenteuer - Mi Barrio',
    'resultado' => [
        'nivel_alcanzado' => 10,
        'puntos_totales' => 600,
        'estrellas' => 6,
        'porcentaje' => 60
    ],
    'puntuacion' => 600,
    'nivel' => 'A1-A2',
    'tiempo_transcurrido' => 16
];

echo "<h1>Test de complete_exercise</h1>\n";
echo "<h2>Datos de entrada:</h2>\n";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";

echo "<h2>Proceso:</h2>\n";
echo "<ol>\n";

try {
    // 1. Verificar conexión a base de datos
    echo "<li>Verificando conexión a base de datos...\n";
    $pdo = getDBConnection();
    echo "✓ Conexión exitosa</li>\n";

    // 2. Validar campos requeridos
    echo "<li>Validando campos requeridos...\n";
    $missing = validateRequired($testData, [
        'estudiante_nombre',
        'ejercicio_id',
        'ejercicio_titulo',
        'resultado'
    ]);

    if (!empty($missing)) {
        throw new Exception('Faltan campos: ' . implode(', ', $missing));
    }
    echo "✓ Todos los campos presentes</li>\n";

    // 3. Obtener o crear estudiante
    echo "<li>Obteniendo o creando estudiante...\n";
    $nombre = trim($testData['estudiante_nombre']);
    $primerNombre = explode(' ', $nombre)[0];

    $stmt = $pdo->prepare("SELECT id, nombre, primer_nombre FROM estudiantes WHERE nombre = ?");
    $stmt->execute([$nombre]);
    $estudiante = $stmt->fetch();

    if (!$estudiante) {
        $stmt = $pdo->prepare("INSERT INTO estudiantes (nombre, primer_nombre) VALUES (?, ?)");
        $stmt->execute([$nombre, $primerNombre]);
        $estudiante = [
            'id' => $pdo->lastInsertId(),
            'nombre' => $nombre,
            'primer_nombre' => $primerNombre
        ];
        echo "✓ Estudiante creado: ID " . $estudiante['id'] . "</li>\n";
    } else {
        echo "✓ Estudiante encontrado: ID " . $estudiante['id'] . "</li>\n";
    }

    // 4. Iniciar transacción
    echo "<li>Iniciando transacción...\n";
    $pdo->beginTransaction();
    echo "✓ Transacción iniciada</li>\n";

    // 5. Eliminar intentos no completados
    echo "<li>Eliminando intentos no completados...\n";
    $stmt = $pdo->prepare("
        DELETE FROM ejercicios_iniciados
        WHERE estudiante_id = ?
        AND ejercicio_id = ?
        AND completado = 0
    ");
    $deleted = $stmt->execute([$estudiante['id'], $testData['ejercicio_id']]);
    echo "✓ Intentos eliminados: " . $stmt->rowCount() . "</li>\n";

    // 6. Codificar resultado
    echo "<li>Codificando resultado...\n";
    if (is_array($testData['resultado'])) {
        $resultado = json_encode($testData['resultado'], JSON_UNESCAPED_UNICODE);
        if ($resultado === false) {
            throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
        }
        echo "✓ Resultado codificado: " . htmlspecialchars($resultado) . "</li>\n";
    } else {
        $resultado = $testData['resultado'];
        echo "✓ Resultado: " . htmlspecialchars($resultado) . "</li>\n";
    }

    // 7. Preparar valores
    echo "<li>Preparando valores...\n";
    $puntuacion = isset($testData['puntuacion']) ? (int)$testData['puntuacion'] : null;
    $tiempoTranscurrido = isset($testData['tiempo_transcurrido']) ? (int)$testData['tiempo_transcurrido'] : null;
    echo "✓ Puntuación: $puntuacion, Tiempo: $tiempoTranscurrido segundos</li>\n";

    // 8. Insertar en resultados
    echo "<li>Insertando en tabla resultados...\n";
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

    $params = [
        $estudiante['id'],
        $testData['ejercicio_id'],
        $testData['ejercicio_titulo'],
        $resultado,
        $puntuacion,
        $testData['nivel'] ?? null,
        $tiempoTranscurrido
    ];

    echo "Parámetros: <pre>" . print_r($params, true) . "</pre>\n";

    $stmt->execute($params);
    $resultadoId = $pdo->lastInsertId();
    echo "✓ Resultado insertado con ID: $resultadoId</li>\n";

    // 9. Marcar como completado
    echo "<li>Marcando ejercicio como completado...\n";
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
        $testData['ejercicio_id'],
        $testData['ejercicio_titulo']
    ]);
    echo "✓ Ejercicio marcado como completado</li>\n";

    // 10. Commit
    echo "<li>Haciendo commit...\n";
    $pdo->commit();
    echo "✓ Commit exitoso</li>\n";

    echo "</ol>\n";
    echo "<h2 style='color: green;'>✓ ÉXITO: El proceso completo funcionó correctamente</h2>\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "<li style='color: red;'>✗ Rollback realizado</li>\n";
    }
    echo "</ol>\n";
    echo "<h2 style='color: red;'>✗ ERROR PDO:</h2>\n";
    echo "<pre style='background: #fee; padding: 15px; border: 1px solid red;'>\n";
    echo "Mensaje: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre>\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "<li style='color: red;'>✗ Rollback realizado</li>\n";
    }
    echo "</ol>\n";
    echo "<h2 style='color: red;'>✗ ERROR:</h2>\n";
    echo "<pre style='background: #fee; padding: 15px; border: 1px solid red;'>\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensaje: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre>\n";
}
