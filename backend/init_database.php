<?php
/**
 * Script de inicialización de base de datos SQLite
 * Crea todas las tablas, índices y el usuario administrador
 */

require_once 'config.php';

function initDatabase($adminUsername = 'admin', $adminPassword = 'admin123') {
    try {
        $pdo = getDBConnection();

        // Crear tabla de administradores
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Crear tabla de estudiantes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS estudiantes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                primer_nombre TEXT NOT NULL,
                fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Crear índices para estudiantes
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_estudiantes_nombre ON estudiantes(nombre)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_estudiantes_primer_nombre ON estudiantes(primer_nombre)");

        // Crear tabla de ejercicios iniciados
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ejercicios_iniciados (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                estudiante_id INTEGER NOT NULL,
                ejercicio_id TEXT NOT NULL,
                ejercicio_titulo TEXT NOT NULL,
                fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
                completado INTEGER DEFAULT 0,
                FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
            )
        ");

        // Crear índices para ejercicios_iniciados
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ejercicios_iniciados_estudiante ON ejercicios_iniciados(estudiante_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ejercicios_iniciados_ejercicio ON ejercicios_iniciados(ejercicio_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ejercicios_iniciados_completado ON ejercicios_iniciados(completado)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ejercicios_iniciados_fecha ON ejercicios_iniciados(fecha_inicio)");

        // Crear tabla de resultados
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS resultados (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                estudiante_id INTEGER NOT NULL,
                ejercicio_id TEXT NOT NULL,
                ejercicio_titulo TEXT NOT NULL,
                resultado TEXT NOT NULL,
                puntuacion INTEGER,
                nivel TEXT,
                fecha_completado DATETIME DEFAULT CURRENT_TIMESTAMP,
                tiempo_transcurrido INTEGER,
                FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
            )
        ");

        // Crear índices para resultados
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_resultados_estudiante ON resultados(estudiante_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_resultados_ejercicio ON resultados(ejercicio_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_resultados_fecha ON resultados(fecha_completado)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_resultados_puntuacion ON resultados(puntuacion)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_resultados_nivel ON resultados(nivel)");

        // Crear vista de estadísticas
        $pdo->exec("DROP VIEW IF EXISTS vista_estadisticas");
        $pdo->exec("
            CREATE VIEW vista_estadisticas AS
            SELECT
                e.ejercicio_id,
                e.ejercicio_titulo,
                COUNT(DISTINCT r.estudiante_id) as total_estudiantes,
                COUNT(r.id) as total_completados,
                AVG(r.puntuacion) as promedio_puntuacion,
                MAX(r.puntuacion) as mejor_puntuacion,
                MIN(r.puntuacion) as peor_puntuacion,
                AVG(r.tiempo_transcurrido) as tiempo_promedio
            FROM ejercicios_iniciados e
            LEFT JOIN resultados r ON e.ejercicio_id = r.ejercicio_id
            WHERE e.completado = 1
            GROUP BY e.ejercicio_id, e.ejercicio_titulo
        ");

        // Crear vista de estudiantes
        $pdo->exec("DROP VIEW IF EXISTS vista_estudiantes");
        $pdo->exec("
            CREATE VIEW vista_estudiantes AS
            SELECT
                est.id,
                est.nombre,
                est.primer_nombre,
                est.fecha_registro,
                COUNT(DISTINCT r.ejercicio_id) as ejercicios_completados,
                COUNT(ei.id) as ejercicios_iniciados,
                COALESCE(AVG(r.puntuacion), 0) as promedio_general
            FROM estudiantes est
            LEFT JOIN ejercicios_iniciados ei ON est.id = ei.estudiante_id
            LEFT JOIN resultados r ON est.id = r.estudiante_id
            GROUP BY est.id, est.nombre, est.primer_nombre, est.fecha_registro
        ");

        // Insertar usuario administrador
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

        // Verificar si ya existe el admin
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$adminUsername]);

        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$adminUsername, $hashedPassword]);
            echo "Usuario administrador creado: $adminUsername\n";
        } else {
            echo "Usuario administrador ya existe: $adminUsername\n";
        }

        echo "Base de datos inicializada correctamente.\n";
        return true;

    } catch (PDOException $e) {
        echo "Error al inicializar la base de datos: " . $e->getMessage() . "\n";
        return false;
    }
}

// Si se ejecuta directamente desde línea de comandos
if (php_sapi_name() === 'cli') {
    $username = $argv[1] ?? 'admin';
    $password = $argv[2] ?? 'admin123';

    echo "Inicializando base de datos...\n";
    echo "Usuario: $username\n";

    if (initDatabase($username, $password)) {
        echo "✓ Base de datos creada exitosamente.\n";
        exit(0);
    } else {
        echo "✗ Error al crear la base de datos.\n";
        exit(1);
    }
}
