#!/bin/bash

# Script de instalación para Sistema de Ejercicios Didácticos
# Compatible con Linux y macOS

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Banner
echo -e "${BLUE}"
cat << "EOF"
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║     📚 Sistema de Ejercicios Didácticos de Español     ║
║              Instalación y Configuración                ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

# Función para mostrar mensajes
info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

success() {
    echo -e "${GREEN}✓${NC} $1"
}

error() {
    echo -e "${RED}✗${NC} $1"
}

warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Verificar si estamos en el directorio correcto
if [ ! -d "backend" ] || [ ! -f "index.html" ]; then
    error "Este script debe ejecutarse desde el directorio raíz del proyecto"
    exit 1
fi

# Verificar requisitos
info "Verificando requisitos del sistema..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    error "PHP no está instalado. Por favor, instala PHP 7.4 o superior."
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
info "PHP detectado: versión $PHP_VERSION"

# Verificar extensión SQLite
if ! php -m | grep -q "sqlite3"; then
    error "La extensión PHP SQLite3 no está instalada."
    error "Por favor, instala php-sqlite3 (Linux) o asegúrate de que esté habilitado (macOS)."
    exit 1
fi

success "Todos los requisitos están instalados"
echo ""

# Solicitar configuración
info "Configuración del sistema"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Puerto del backend
read -p "$(echo -e ${BLUE}?${NC}) Puerto del servidor backend [8000]: " BACKEND_PORT
BACKEND_PORT=${BACKEND_PORT:-8000}

# URL del backend
DEFAULT_BACKEND_URL="http://localhost:$BACKEND_PORT"
read -p "$(echo -e ${BLUE}?${NC}) URL completa del backend [$DEFAULT_BACKEND_URL]: " BACKEND_URL
BACKEND_URL=${BACKEND_URL:-$DEFAULT_BACKEND_URL}

# Credenciales de administrador
echo ""
info "Credenciales del administrador"
read -p "$(echo -e ${BLUE}?${NC}) Usuario administrador [admin]: " ADMIN_USERNAME
ADMIN_USERNAME=${ADMIN_USERNAME:-admin}

while true; do
    read -s -p "$(echo -e ${BLUE}?${NC}) Contraseña administrador: " ADMIN_PASSWORD
    echo ""
    read -s -p "$(echo -e ${BLUE}?${NC}) Confirmar contraseña: " ADMIN_PASSWORD_CONFIRM
    echo ""

    if [ "$ADMIN_PASSWORD" = "$ADMIN_PASSWORD_CONFIRM" ]; then
        if [ ${#ADMIN_PASSWORD} -lt 6 ]; then
            warning "La contraseña debe tener al menos 6 caracteres"
        else
            break
        fi
    else
        warning "Las contraseñas no coinciden. Intenta de nuevo."
    fi
done

echo ""
info "Configuración recibida:"
echo "  - Puerto: $BACKEND_PORT"
echo "  - URL Backend: $BACKEND_URL"
echo "  - Usuario Admin: $ADMIN_USERNAME"
echo ""

read -p "$(echo -e ${YELLOW}?${NC}) ¿Continuar con la instalación? (s/n): " CONFIRM
if [[ ! $CONFIRM =~ ^[Ss]$ ]]; then
    warning "Instalación cancelada"
    exit 0
fi

echo ""
info "Iniciando instalación..."
echo ""

# 1. Crear archivo de configuración del backend
info "Configurando backend..."

cat > backend/config.php << EOF
<?php
/**
 * Configuración de base de datos SQLite
 * Generado automáticamente por install.sh
 */

// Ruta a la base de datos SQLite
define('DB_PATH', __DIR__ . '/ejercicios.db');

// Puerto del servidor
define('SERVER_PORT', '$BACKEND_PORT');

// URL del backend
define('BACKEND_URL', '$BACKEND_URL');

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de CORS (permitir peticiones desde el frontend)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Función para obtener conexión a la base de datos
 */
function getDBConnection() {
    try {
        \$pdo = new PDO('sqlite:' . DB_PATH);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Habilitar foreign keys en SQLite
        \$pdo->exec('PRAGMA foreign_keys = ON');

        return \$pdo;
    } catch (PDOException \$e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error de conexión a la base de datos',
            'message' => \$e->getMessage()
        ]);
        exit();
    }
}

/**
 * Función para enviar respuesta JSON
 */
function sendResponse(\$data, \$statusCode = 200) {
    http_response_code(\$statusCode);
    echo json_encode(\$data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función para validar datos requeridos
 */
function validateRequired(\$data, \$fields) {
    \$missing = [];
    foreach (\$fields as \$field) {
        if (!isset(\$data[\$field]) || trim(\$data[\$field]) === '') {
            \$missing[] = \$field;
        }
    }
    return \$missing;
}

/**
 * Iniciar sesión
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    startSession();
    return isset(\$_SESSION['admin_logged_in']) && \$_SESSION['admin_logged_in'] === true;
}

/**
 * Requerir autenticación
 */
function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No autorizado'
        ]);
        exit();
    }
}
EOF

success "Archivo de configuración del backend creado"

# 2. Crear archivo de configuración del frontend
info "Configurando frontend..."

cat > ejercicios/config.js << EOF
/**
 * Configuración del frontend
 * Generado automáticamente por install.sh
 */

// URL del backend API
window.BACKEND_API_URL = '$BACKEND_URL/api.php';

// Configuración global
window.APP_CONFIG = {
    backendUrl: '$BACKEND_URL',
    apiUrl: '$BACKEND_URL/api.php',
    silentErrors: true
};
EOF

success "Archivo de configuración del frontend creado"

# 3. Inicializar base de datos
info "Inicializando base de datos..."

# Eliminar base de datos existente si existe
if [ -f "backend/ejercicios.db" ]; then
    warning "Base de datos existente encontrada"
    read -p "$(echo -e ${YELLOW}?${NC}) ¿Deseas eliminarla y crear una nueva? (s/n): " DELETE_DB
    if [[ $DELETE_DB =~ ^[Ss]$ ]]; then
        rm backend/ejercicios.db
        info "Base de datos anterior eliminada"
    fi
fi

# Ejecutar script de inicialización
php backend/init_database.php "$ADMIN_USERNAME" "$ADMIN_PASSWORD"

if [ $? -eq 0 ]; then
    success "Base de datos inicializada correctamente"
else
    error "Error al inicializar la base de datos"
    exit 1
fi

# 4. Configurar permisos
info "Configurando permisos..."

chmod 644 backend/config.php
chmod 644 backend/*.php
chmod 666 backend/ejercicios.db 2>/dev/null || true

success "Permisos configurados"

# 5. Crear script de inicio
info "Creando script de inicio..."

cat > start_server.sh << EOF
#!/bin/bash

# Script para iniciar el servidor backend

cd backend
echo "Iniciando servidor backend en puerto $BACKEND_PORT..."
echo "URL: $BACKEND_URL"
echo ""
echo "Presiona Ctrl+C para detener el servidor"
echo ""

php -S 0.0.0.0:$BACKEND_PORT
EOF

chmod +x start_server.sh

success "Script de inicio creado (./start_server.sh)"

# Resumen
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                                                          ║${NC}"
echo -e "${GREEN}║              ✓ Instalación completada                   ║${NC}"
echo -e "${GREEN}║                                                          ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
info "Resumen de configuración:"
echo "  📦 Base de datos: backend/ejercicios.db"
echo "  🔐 Usuario admin: $ADMIN_USERNAME"
echo "  🌐 URL Backend: $BACKEND_URL"
echo "  🚀 Puerto: $BACKEND_PORT"
echo ""
info "Próximos pasos:"
echo ""
echo "  1. Iniciar el servidor backend:"
echo -e "     ${BLUE}./start_server.sh${NC}"
echo ""
echo "  2. Abrir el frontend:"
echo -e "     ${BLUE}Abrir index.html en tu navegador${NC}"
echo ""
echo "  3. Acceder al dashboard:"
echo -e "     ${BLUE}$BACKEND_URL/dashboard.php${NC}"
echo ""
echo -e "${YELLOW}Nota:${NC} Los ejercicios enviarán datos automáticamente al backend."
echo ""
success "¡Disfruta del sistema de ejercicios didácticos!"
echo ""
