#!/bin/bash

# Script para iniciar el servidor backend
# Sistema de Ejercicios Didácticos de Español

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Banner
echo -e "${BLUE}"
cat << "BANNER"
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║     📚 Ejercicios Didácticos de Español                ║
║            Servidor Backend PHP                          ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
BANNER
echo -e "${NC}"

# Verificar que estemos en el directorio correcto
if [ ! -d "backend" ]; then
    echo -e "${YELLOW}⚠${NC} Error: No se encuentra el directorio 'backend'"
    echo "Por favor, ejecuta este script desde el directorio raíz del proyecto."
    exit 1
fi

# Verificar si existe config.php
if [ ! -f "backend/config.php" ]; then
    echo -e "${YELLOW}⚠${NC} Advertencia: No se encuentra backend/config.php"
    echo ""
    echo "Parece que no has ejecutado el instalador."
    echo "Por favor, ejecuta primero: ./install.sh"
    echo ""
    read -p "¿Deseas iniciar el servidor de todas formas en el puerto 8000? (s/n): " CONTINUE
    if [[ ! $CONTINUE =~ ^[Ss]$ ]]; then
        exit 0
    fi
    PORT=8000
else
    # Leer puerto del config.php
    PORT=$(grep -oP "define\('SERVER_PORT', '\K[0-9]+" backend/config.php 2>/dev/null || echo "8000")
fi

# Verificar si la base de datos existe
if [ ! -f "backend/ejercicios.db" ]; then
    echo -e "${YELLOW}⚠${NC} Advertencia: No se encuentra la base de datos (backend/ejercicios.db)"
    echo ""
    echo "La base de datos se creará automáticamente al recibir la primera petición,"
    echo "pero es recomendable ejecutar primero el instalador: ./install.sh"
    echo ""
fi

# Cambiar al directorio backend
cd backend

echo -e "${GREEN}✓${NC} Iniciando servidor backend..."
echo ""
echo -e "${BLUE}Configuración:${NC}"
echo "  📍 Puerto: $PORT"
echo "  🌐 URL: http://localhost:$PORT"
echo "  📊 Dashboard: http://localhost:$PORT/dashboard.php"
echo "  🔌 API: http://localhost:$PORT/api.php"
echo ""
echo -e "${YELLOW}Instrucciones:${NC}"
echo "  1. Abre index.html en tu navegador para acceder a los ejercicios"
echo "  2. Accede al dashboard en: http://localhost:$PORT/dashboard.php"
echo "  3. Presiona Ctrl+C para detener el servidor"
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Iniciar servidor PHP
php -S 0.0.0.0:$PORT
