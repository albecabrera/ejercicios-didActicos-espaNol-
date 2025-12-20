<?php
/**
 * Dashboard de Estadísticas
 * Panel de control para profesores y administradores
 */

require_once 'config.php';

// Desactivar CORS headers para páginas HTML
header_remove('Access-Control-Allow-Origin');

$pdo = getDBConnection();

// Obtener filtros
$filtroEjercicio = $_GET['ejercicio'] ?? '';
$filtroEstudiante = $_GET['estudiante'] ?? '';
$filtroNivel = $_GET['nivel'] ?? '';
$filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
$filtroFechaFin = $_GET['fecha_fin'] ?? '';

// Construir query base
$whereConditions = [];
$params = [];

if ($filtroEjercicio) {
    $whereConditions[] = "r.ejercicio_id = ?";
    $params[] = $filtroEjercicio;
}

if ($filtroEstudiante) {
    $whereConditions[] = "(e.nombre LIKE ? OR e.primer_nombre LIKE ?)";
    $params[] = "%$filtroEstudiante%";
    $params[] = "%$filtroEstudiante%";
}

if ($filtroNivel) {
    $whereConditions[] = "r.nivel = ?";
    $params[] = $filtroNivel;
}

if ($filtroFechaInicio) {
    $whereConditions[] = "DATE(r.fecha_completado) >= ?";
    $params[] = $filtroFechaInicio;
}

if ($filtroFechaFin) {
    $whereConditions[] = "DATE(r.fecha_completado) <= ?";
    $params[] = $filtroFechaFin;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Obtener resultados filtrados
$queryResultados = "
    SELECT
        r.*,
        e.nombre as estudiante_nombre,
        e.primer_nombre as estudiante_primer_nombre
    FROM resultados r
    INNER JOIN estudiantes e ON r.estudiante_id = e.id
    $whereClause
    ORDER BY r.fecha_completado DESC
    LIMIT 100
";

$stmt = $pdo->prepare($queryResultados);
$stmt->execute($params);
$resultados = $stmt->fetchAll();

// Estadísticas generales
$statsQuery = "
    SELECT
        COUNT(DISTINCT e.id) as total_estudiantes,
        COUNT(DISTINCT r.ejercicio_id) as total_ejercicios_diferentes,
        COUNT(r.id) as total_completados,
        AVG(r.puntuacion) as promedio_puntuacion,
        SUM(CASE WHEN ei.completado = 0 THEN 1 ELSE 0 END) as ejercicios_abandonados
    FROM estudiantes e
    LEFT JOIN resultados r ON e.id = r.estudiante_id
    LEFT JOIN ejercicios_iniciados ei ON e.id = ei.estudiante_id
    $whereClause
";

$stmt = $pdo->prepare($statsQuery);
$stmt->execute($params);
$stats = $stmt->fetch();

// Obtener listas para filtros
$ejercicios = $pdo->query("SELECT DISTINCT ejercicio_id, ejercicio_titulo FROM resultados ORDER BY ejercicio_titulo")->fetchAll();
$niveles = $pdo->query("SELECT DISTINCT nivel FROM resultados WHERE nivel IS NOT NULL ORDER BY nivel")->fetchAll();

// Estadísticas por ejercicio
$statsPorEjercicio = $pdo->query("SELECT * FROM vista_estadisticas ORDER BY total_completados DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ejercicios Didácticos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            color: #6c757d;
            font-size: 1.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 1em;
            font-weight: 600;
        }

        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .filters-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            color: #6c757d;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1em;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e9ecef;
            color: #6c757d;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        .table-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow-x: auto;
        }

        .table-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        th:first-child {
            border-radius: 10px 0 0 0;
        }

        th:last-child {
            border-radius: 0 10px 0 0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.9em;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Dashboard de Estadísticas</h1>
            <p>Panel de control para seguimiento de ejercicios y estudiantes</p>
        </div>

        <!-- Estadísticas generales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Estudiantes</div>
                <div class="stat-value"><?= number_format($stats['total_estudiantes']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Ejercicios Completados</div>
                <div class="stat-value"><?= number_format($stats['total_completados']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Promedio de Puntuación</div>
                <div class="stat-value"><?= number_format($stats['promedio_puntuacion'], 1) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Ejercicios Abandonados</div>
                <div class="stat-value"><?= number_format($stats['ejercicios_abandonados']) ?></div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <h2>🔍 Filtros</h2>
            <form method="GET">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Ejercicio:</label>
                        <select name="ejercicio">
                            <option value="">Todos</option>
                            <?php foreach ($ejercicios as $ej): ?>
                                <option value="<?= htmlspecialchars($ej['ejercicio_id']) ?>"
                                    <?= $filtroEjercicio === $ej['ejercicio_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ej['ejercicio_titulo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Estudiante:</label>
                        <input type="text" name="estudiante" value="<?= htmlspecialchars($filtroEstudiante) ?>"
                            placeholder="Nombre del estudiante">
                    </div>
                    <div class="filter-group">
                        <label>Nivel:</label>
                        <select name="nivel">
                            <option value="">Todos</option>
                            <?php foreach ($niveles as $niv): ?>
                                <option value="<?= htmlspecialchars($niv['nivel']) ?>"
                                    <?= $filtroNivel === $niv['nivel'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($niv['nivel']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Fecha Inicio:</label>
                        <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($filtroFechaInicio) ?>">
                    </div>
                    <div class="filter-group">
                        <label>Fecha Fin:</label>
                        <input type="date" name="fecha_fin" value="<?= htmlspecialchars($filtroFechaFin) ?>">
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    <a href="dashboard.php" class="btn btn-secondary">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <!-- Estadísticas por ejercicio -->
        <div class="table-section">
            <h2>📈 Estadísticas por Ejercicio</h2>
            <?php if (empty($statsPorEjercicio)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>No hay datos disponibles</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ejercicio</th>
                            <th>Estudiantes</th>
                            <th>Completados</th>
                            <th>Promedio</th>
                            <th>Mejor</th>
                            <th>Peor</th>
                            <th>Tiempo Prom.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statsPorEjercicio as $stat): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($stat['ejercicio_titulo']) ?></strong></td>
                                <td><?= number_format($stat['total_estudiantes']) ?></td>
                                <td><span class="badge badge-success"><?= number_format($stat['total_completados']) ?></span></td>
                                <td><?= number_format($stat['promedio_puntuacion'], 1) ?></td>
                                <td><?= number_format($stat['mejor_puntuacion']) ?></td>
                                <td><?= number_format($stat['peor_puntuacion']) ?></td>
                                <td><?= $stat['tiempo_promedio'] ? gmdate("i:s", $stat['tiempo_promedio']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Resultados recientes -->
        <div class="table-section">
            <h2>📝 Resultados Recientes (últimos 100)</h2>
            <?php if (empty($resultados)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>No se encontraron resultados con los filtros aplicados</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estudiante</th>
                            <th>Ejercicio</th>
                            <th>Nivel</th>
                            <th>Puntuación</th>
                            <th>Tiempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $res): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($res['fecha_completado'])) ?></td>
                                <td><strong><?= htmlspecialchars($res['estudiante_nombre']) ?></strong></td>
                                <td><?= htmlspecialchars($res['ejercicio_titulo']) ?></td>
                                <td><span class="badge badge-info"><?= htmlspecialchars($res['nivel'] ?? 'N/A') ?></span></td>
                                <td><span class="badge badge-warning"><?= number_format($res['puntuacion']) ?> pts</span></td>
                                <td><?= $res['tiempo_transcurrido'] ? gmdate("i:s", $res['tiempo_transcurrido']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
