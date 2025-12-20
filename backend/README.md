# Backend - Sistema de Ejercicios Didácticos

Backend PHP con SQLite para el seguimiento de estudiantes y estadísticas de ejercicios.

## Características

- ✅ Base de datos SQLite (sin necesidad de MySQL)
- ✅ API REST para gestión de ejercicios
- ✅ Sistema de autenticación para dashboard
- ✅ Instalador automático para Linux/macOS
- ✅ Dashboard con estadísticas y filtros
- ✅ Modo offline/silent errors en frontend

## Instalación Rápida

### Usar el Instalador Automático (Recomendado)

Desde el directorio raíz del proyecto:

```bash
./install.sh
```

El instalador te preguntará:
- Puerto del servidor backend (default: 8000)
- URL del backend (default: http://localhost:8000)
- Usuario administrador (default: admin)
- Contraseña del administrador

### Instalación Manual

Si prefieres configurar manualmente:

1. **Configurar el backend:**
```bash
cd backend
cp config.php.example config.php
# Editar config.php con tus valores
```

2. **Inicializar base de datos:**
```bash
php init_database.php admin tu_contraseña
```

3. **Configurar frontend:**
```bash
cd ../ejercicios
cat > config.js << EOF
window.BACKEND_API_URL = 'http://localhost:8000/api.php';
window.APP_CONFIG = {
    backendUrl: 'http://localhost:8000',
    apiUrl: 'http://localhost:8000/api.php',
    silentErrors: true
};
EOF
```

4. **Iniciar servidor:**
```bash
cd ../backend
php -S 0.0.0.0:8000
```

## Requisitos

- PHP 7.4 o superior
- Extensión PHP SQLite3 (habilitada por defecto en la mayoría de instalaciones)
- Navegador web moderno

### Verificar Requisitos

```bash
# Verificar PHP
php --version

# Verificar SQLite3
php -m | grep sqlite3
```

## Uso

### Iniciar el Servidor

Después de instalar:

```bash
./start_server.sh
```

O manualmente:

```bash
cd backend
php -S 0.0.0.0:8000
```

### Acceder al Dashboard

1. Abrir en navegador: `http://localhost:8000/dashboard.php`
2. Iniciar sesión con las credenciales configuradas durante la instalación
3. El dashboard requiere autenticación

### Acceder a los Ejercicios

1. Abrir `index.html` en el navegador
2. Los ejercicios se comunican automáticamente con el backend
3. Si el backend no está disponible, funcionan en modo offline

## Estructura de Archivos

```
backend/
├── config.php              # Configuración (generado por instalador)
├── init_database.php       # Script de inicialización de BD
├── api.php                 # API REST para ejercicios
├── auth.php                # API de autenticación
├── dashboard.php           # Dashboard con estadísticas
├── login.php               # Página de login
├── ejercicios.db           # Base de datos SQLite (creada al instalar)
└── README.md               # Este archivo
```

## Endpoints de API

### Ejercicios (api.php)

**POST /api.php?action=register_student**
```json
{
  "nombre": "Juan Pérez"
}
```

**POST /api.php?action=start_exercise**
```json
{
  "estudiante_id": 1,
  "ejercicio_id": "mi-barrio",
  "ejercicio_titulo": "Madrid Abenteuer"
}
```

**POST /api.php?action=complete_exercise**
```json
{
  "estudiante_id": 1,
  "ejercicio_id": "mi-barrio",
  "ejercicio_titulo": "Madrid Abenteuer",
  "resultado": {"puntos": 100, "estrellas": 3},
  "puntuacion": 100,
  "nivel": "A1-A2",
  "tiempo_transcurrido": 300
}
```

### Autenticación (auth.php)

**POST /auth.php?action=login**
```json
{
  "username": "admin",
  "password": "contraseña"
}
```

**POST /auth.php?action=logout**

**GET /auth.php?action=check**

## Configuración

### Cambiar Puerto del Servidor

Editar `backend/config.php`:
```php
define('SERVER_PORT', '8080');
```

Y `ejercicios/config.js`:
```javascript
window.BACKEND_API_URL = 'http://localhost:8080/api.php';
```

### Modo Silent Errors

Por defecto, el frontend no muestra errores de conexión al usuario. Para cambiar esto:

En `ejercicios/config.js`:
```javascript
window.APP_CONFIG = {
    // ...
    silentErrors: false  // Mostrar errores
};
```

### Cambiar Credenciales de Admin

```bash
# Método 1: Reinstalar (elimina todos los datos)
rm backend/ejercicios.db
php backend/init_database.php nuevo_usuario nueva_contraseña

# Método 2: Agregar nuevo admin directamente en SQLite
sqlite3 backend/ejercicios.db
```

## Base de Datos

### Tablas

- **admins** - Usuarios administradores
- **estudiantes** - Registro de estudiantes
- **ejercicios_iniciados** - Tracking de sesiones
- **resultados** - Resultados completados

### Vistas

- **vista_estadisticas** - Estadísticas por ejercicio
- **vista_estudiantes** - Resumen de estudiantes

### Backup

```bash
# Crear backup
cp backend/ejercicios.db backend/ejercicios.db.backup

# Restaurar backup
cp backend/ejercicios.db.backup backend/ejercicios.db
```

## Troubleshooting

**Error: "PHP no está instalado"**
```bash
# macOS
brew install php

# Ubuntu/Debian
sudo apt-get install php php-sqlite3

# Fedora/RHEL
sudo dnf install php php-sqlite3
```

**Error: "Extensión SQLite3 no encontrada"**
```bash
# Verificar
php -m | grep sqlite3

# Habilitar en php.ini (descomentar)
extension=sqlite3
```

**Error: "Permission denied" en ejercicios.db**
```bash
chmod 666 backend/ejercicios.db
```

**Dashboard muestra error 500**
- Verificar que ejercicios.db existe
- Verificar permisos del archivo
- Revisar logs de PHP

**Ejercicios no guardan datos**
- Verificar que el backend esté corriendo
- Verificar URL en ejercicios/config.js
- Abrir consola del navegador para ver errores

## Desarrollo

### Modo Debug

En `config.php`, agregar al inicio:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Logs

Los errores de PHP se muestran en la terminal donde corre el servidor.

### Agregar Nuevo Endpoint

1. Editar `api.php`
2. Agregar nuevo case en el switch
3. Crear función para manejar la acción

## Seguridad

- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Prepared statements para prevenir SQL injection
- ✅ Validación de datos de entrada
- ✅ Sesiones PHP para autenticación
- ✅ CORS configurado

**Recomendaciones para producción:**
- Cambiar contraseña por defecto
- Usar HTTPS
- Configurar CORS para dominios específicos
- Deshabilitar display_errors
- Implementar rate limiting

## Licencia

Este proyecto es de uso educativo.
