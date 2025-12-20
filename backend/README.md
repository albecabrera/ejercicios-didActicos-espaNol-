# Backend - Sistema de Ejercicios Didácticos

Backend PHP para el seguimiento de estudiantes y estadísticas de ejercicios.

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3 o superior
- Extensión PDO de PHP habilitada
- Servidor web (Apache/Nginx) o PHP built-in server

## Instalación

### 1. Configurar Base de Datos

```bash
# Acceder a MySQL
mysql -u root -p

# Ejecutar el script de creación de base de datos
source database.sql
```

O copiar y pegar el contenido de `database.sql` en phpMyAdmin.

### 2. Configurar Conexión

Editar `config.php` y actualizar las credenciales de la base de datos:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ejercicios_didacticos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 3. Configurar CORS (Opcional)

Si el frontend está en un dominio diferente al backend, actualizar en `config.php`:

```php
header('Access-Control-Allow-Origin: https://tu-dominio.com');
```

### 4. Iniciar Servidor

**Opción A: Servidor integrado de PHP (desarrollo)**
```bash
cd backend
php -S localhost:8000
```

**Opción B: Apache/Nginx (producción)**
Configurar el virtual host apuntando a la carpeta `backend/`.

## Endpoints de API

### POST /api.php?action=register_student

Registrar o obtener un estudiante.

**Request:**
```json
{
  "nombre": "Juan Pérez"
}
```

**Response:**
```json
{
  "success": true,
  "estudiante": {
    "id": 1,
    "nombre": "Juan Pérez",
    "primer_nombre": "Juan"
  },
  "nuevo": true
}
```

### POST /api.php?action=start_exercise

Registrar inicio de un ejercicio.

**Request:**
```json
{
  "estudiante_id": 1,
  "ejercicio_id": "mi-barrio",
  "ejercicio_titulo": "Madrid Abenteuer - Mi Barrio"
}
```

**Response:**
```json
{
  "success": true,
  "inicio_id": 5,
  "mensaje": "Ejercicio iniciado correctamente"
}
```

### POST /api.php?action=complete_exercise

Registrar ejercicio completado.

**Request:**
```json
{
  "estudiante_id": 1,
  "ejercicio_id": "mi-barrio",
  "ejercicio_titulo": "Madrid Abenteuer - Mi Barrio",
  "resultado": {
    "nivel_alcanzado": 10,
    "puntos_totales": 100,
    "estrellas": 3
  },
  "puntuacion": 100,
  "nivel": "A1-A2",
  "tiempo_transcurrido": 300
}
```

**Response:**
```json
{
  "success": true,
  "mensaje": "Ejercicio completado correctamente",
  "resultado_id": 12
}
```

### GET /api.php?action=get_student&id={id}

Obtener datos de un estudiante.

**Response:**
```json
{
  "success": true,
  "estudiante": {
    "id": 1,
    "nombre": "Juan Pérez",
    "primer_nombre": "Juan",
    "fecha_registro": "2025-01-15 10:30:00",
    "ejercicios_completados": 5,
    "ejercicios_iniciados": 7,
    "promedio_general": 85.5
  }
}
```

## Dashboard

Acceder al dashboard en: `http://localhost:8000/dashboard.php`

El dashboard permite:
- Ver estadísticas generales
- Filtrar por ejercicio, estudiante, nivel y fechas
- Ver resultados recientes
- Analizar estadísticas por ejercicio

## Estructura de Base de Datos

### Tabla: estudiantes
- `id`: ID único
- `nombre`: Nombre completo
- `primer_nombre`: Primer nombre extraído
- `fecha_registro`: Fecha de registro

### Tabla: ejercicios_iniciados
- `id`: ID único
- `estudiante_id`: ID del estudiante
- `ejercicio_id`: ID del ejercicio
- `ejercicio_titulo`: Título del ejercicio
- `fecha_inicio`: Fecha de inicio
- `completado`: Si fue completado (0/1)

### Tabla: resultados
- `id`: ID único
- `estudiante_id`: ID del estudiante
- `ejercicio_id`: ID del ejercicio
- `ejercicio_titulo`: Título del ejercicio
- `resultado`: JSON con datos del resultado
- `puntuacion`: Puntuación obtenida
- `nivel`: Nivel del ejercicio
- `fecha_completado`: Fecha de completado
- `tiempo_transcurrido`: Tiempo en segundos

## Seguridad

- Usar HTTPS en producción
- Actualizar credenciales de base de datos
- Configurar CORS apropiadamente
- Validar y sanitizar todas las entradas
- Usar prepared statements (ya implementado)

## Troubleshooting

**Error de conexión a base de datos:**
- Verificar credenciales en `config.php`
- Verificar que MySQL esté corriendo
- Verificar que la base de datos exista

**CORS errors:**
- Actualizar `Access-Control-Allow-Origin` en `config.php`
- En desarrollo, usar `*` para permitir todos los orígenes

**Errores de permisos:**
- Verificar permisos de archivos (644 para archivos, 755 para directorios)
- Verificar que el usuario de MySQL tenga permisos adecuados
