-- Base de datos para Sistema de Ejercicios Didácticos
-- Ejecutar este script en MySQL para crear las tablas necesarias

CREATE DATABASE IF NOT EXISTS ejercicios_didacticos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ejercicios_didacticos;

-- Tabla de estudiantes
CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    primer_nombre VARCHAR(50) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_primer_nombre (primer_nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de ejercicios iniciados (tracking de sesiones)
CREATE TABLE IF NOT EXISTS ejercicios_iniciados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    ejercicio_id VARCHAR(50) NOT NULL,
    ejercicio_titulo VARCHAR(200) NOT NULL,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    completado TINYINT(1) DEFAULT 0,
    INDEX idx_estudiante (estudiante_id),
    INDEX idx_ejercicio (ejercicio_id),
    INDEX idx_completado (completado),
    INDEX idx_fecha (fecha_inicio),
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de resultados de ejercicios completados
CREATE TABLE IF NOT EXISTS resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    ejercicio_id VARCHAR(50) NOT NULL,
    ejercicio_titulo VARCHAR(200) NOT NULL,
    resultado JSON NOT NULL,
    puntuacion INT,
    nivel VARCHAR(20),
    fecha_completado DATETIME DEFAULT CURRENT_TIMESTAMP,
    tiempo_transcurrido INT COMMENT 'Tiempo en segundos',
    INDEX idx_estudiante (estudiante_id),
    INDEX idx_ejercicio (ejercicio_id),
    INDEX idx_fecha (fecha_completado),
    INDEX idx_puntuacion (puntuacion),
    INDEX idx_nivel (nivel),
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vista para estadísticas rápidas
CREATE OR REPLACE VIEW vista_estadisticas AS
SELECT
    e.id as ejercicio_id,
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
GROUP BY e.ejercicio_id, e.ejercicio_titulo;

-- Vista para seguimiento de estudiantes
CREATE OR REPLACE VIEW vista_estudiantes AS
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
GROUP BY est.id, est.nombre, est.primer_nombre, est.fecha_registro;
