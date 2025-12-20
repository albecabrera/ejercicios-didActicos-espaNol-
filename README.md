# 📚 Ejercicios Didácticos de Español

Sistema interactivo completo de ejercicios de español con tracking de estudiantes, estadísticas y dashboard administrativo.

## 🎯 Características

### Frontend
- ✅ Catálogo de ejercicios interactivos con búsqueda y filtros
- ✅ Ejercicios gamificados con sistema de puntos y estrellas
- ✅ Text-to-speech integrado para pronunciación
- ✅ Diseño responsive (móvil y desktop)
- ✅ Modo offline/sin backend

### Backend
- ✅ Base de datos SQLite (sin MySQL requerido)
- ✅ API REST para tracking de ejercicios
- ✅ Sistema de autenticación para administradores
- ✅ Dashboard con estadísticas avanzadas y filtros
- ✅ Tracking de ejercicios iniciados y completados
- ✅ Análisis de abandonos y rendimiento

### Instalación
- ✅ Instalador automático para Linux/macOS
- ✅ Configuración interactiva de puerto y URL
- ✅ Creación automática de base de datos
- ✅ Usuario administrador configurable

## 🚀 Instalación Rápida

### Prerequisitos

- PHP 7.4 o superior con extensión SQLite3
- Navegador web moderno

### Paso 1: Ejecutar el Instalador

```bash
cd ejercicios_didacticos
./install.sh
```

El instalador te preguntará:
- Puerto del servidor backend (default: 8000)
- URL del backend (default: http://localhost:8000)
- Usuario administrador (default: admin)
- Contraseña del administrador

### Paso 2: Iniciar el Servidor

```bash
./start_server.sh
```

### Paso 3: Usar el Sistema

**Ejercicios (estudiantes):**
1. Abrir `index.html` en el navegador
2. Buscar y filtrar ejercicios
3. Iniciar un ejercicio
4. Ingresar nombre cuando se solicite
5. Completar el ejercicio

**Dashboard (profesores):**
1. Navegar a `http://localhost:8000/dashboard.php`
2. Iniciar sesión con credenciales configuradas
3. Ver estadísticas, filtrar resultados
4. Analizar rendimiento de estudiantes

## 📖 Ejercicios Disponibles

### 1. Madrid Abenteuer - Mi Barrio 🏙️
- **Nivel:** A1-A2
- **Temas:** Vocabulario, Direcciones, Ciudad
- **Características:**
  - 10 niveles progresivos
  - Escenarios basados en Madrid
  - Vocabulario de lugares, comida, transporte
  - Sistema de puntos y estrellas
  - Text-to-speech en español

### 2. Escape Room - Casa de los Gatos 🐱
- **Nivel:** A1-A2
- **Temas:** Gramática, Preposiciones, Verbo estar
- **Características:**
  - 6 habitaciones con acertijos
  - Práctica de preposiciones de lugar
  - Conjugación del verbo "estar"
  - Modo claro/oscuro
  - Efectos de sonido y confetti

## 📊 Dashboard de Estadísticas

### Funcionalidades

- **Estadísticas Generales:**
  - Total de estudiantes
  - Ejercicios completados
  - Promedio de puntuaciones
  - Ejercicios abandonados

- **Filtros Avanzados:**
  - Por ejercicio específico
  - Por nombre de estudiante
  - Por nivel (A1, A2, etc.)
  - Por rango de fechas

- **Análisis por Ejercicio:**
  - Número de estudiantes
  - Total de completados
  - Mejor/peor puntuación
  - Tiempo promedio

- **Historial de Resultados:**
  - Últimos 100 resultados
  - Datos detallados de cada intento
  - Información de fecha y tiempo

## 🔧 Configuración

### Cambiar Puerto del Backend

Editar `backend/config.php`:
```php
define('SERVER_PORT', '9000');
define('BACKEND_URL', 'http://localhost:9000');
```

Editar `ejercicios/config.js`:
```javascript
window.BACKEND_API_URL = 'http://localhost:9000/api.php';
```

### Modo Silent Errors

Por defecto, el frontend no muestra errores de conexión. Para cambiar:

En `ejercicios/config.js`:
```javascript
window.APP_CONFIG = {
    backendUrl: 'http://localhost:8000',
    apiUrl: 'http://localhost:8000/api.php',
    silentErrors: false  // Mostrar errores al usuario
};
```

### Cambiar Contraseña de Admin

```bash
# Eliminar base de datos y recrear
rm backend/ejercicios.db
php backend/init_database.php nuevo_admin nueva_contraseña
```

## 📁 Estructura del Proyecto

```
ejercicios_didacticos/
├── index.html                          # Catálogo principal
├── install.sh                          # Instalador automático
├── start_server.sh                     # Script de inicio (generado)
├── CLAUDE.md                           # Instrucciones para Claude
├── README.md                           # Este archivo
│
├── ejercicios/                         # Ejercicios interactivos
│   ├── config.js                       # Configuración (generado)
│   ├── ejercicio-tracker.js            # Sistema de tracking
│   ├── mi_barrio_spiel.html            # Ejercicio Madrid
│   └── escape_room_spanisch.html       # Ejercicio Escape Room
│
└── backend/                            # Backend PHP
    ├── config.php                      # Configuración (generado)
    ├── ejercicios.db                   # Base de datos (generado)
    ├── init_database.php               # Inicializador de BD
    ├── api.php                         # API REST
    ├── auth.php                        # Autenticación
    ├── dashboard.php                   # Dashboard web
    ├── login.php                       # Página de login
    ├── database.sql                    # Referencia (obsoleto)
    └── README.md                       # Documentación del backend
```

## 💾 Base de Datos

### Tablas

- **admins** - Usuarios administradores
- **estudiantes** - Registro de estudiantes
  - Extrae automáticamente el primer nombre
- **ejercicios_iniciados** - Tracking de sesiones
  - Marca ejercicios completados/abandonados
- **resultados** - Resultados de ejercicios completados
  - Almacena JSON con datos detallados

### Vistas

- **vista_estadisticas** - Estadísticas agregadas por ejercicio
- **vista_estudiantes** - Resumen de actividad de estudiantes

### Backup

```bash
# Crear backup
cp backend/ejercicios.db backend/ejercicios.db.backup

# Restaurar backup
cp backend/ejercicios.db.backup backend/ejercicios.db
```

## 🔒 Seguridad

- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Prepared statements (previene SQL injection)
- ✅ Validación de entrada de datos
- ✅ Sesiones PHP para autenticación
- ✅ CORS configurado

**Para producción:**
- Cambiar contraseña por defecto
- Usar HTTPS
- Configurar CORS específico (no *)
- Deshabilitar display_errors
- Implementar rate limiting

## 📱 GitHub Pages (Despliegue)

Para desplegar en GitHub Pages:

1. Actualizar `GITHUB_USERNAME` en `index.html` (línea ~352)
2. Habilitar GitHub Pages en Settings → Pages
3. El backend debe desplegarse en un servidor PHP separado
4. Actualizar `ejercicios/config.js` con la URL del backend en producción

## 🐛 Troubleshooting

### Backend no inicia

```bash
# Verificar PHP
php --version

# Verificar extensión SQLite3
php -m | grep sqlite3

# Si falta, instalar:
# macOS
brew install php

# Ubuntu/Debian
sudo apt-get install php php-sqlite3
```

### Ejercicios no guardan datos

1. Verificar que el backend esté corriendo
2. Abrir consola del navegador (F12)
3. Verificar URL en `ejercicios/config.js`
4. Verificar errores de CORS

### Dashboard muestra error 500

1. Verificar que `backend/ejercicios.db` existe
2. Verificar permisos: `chmod 666 backend/ejercicios.db`
3. Revisar logs en la terminal del servidor

### No puedo iniciar sesión en dashboard

1. Verificar credenciales usadas durante instalación
2. Si olvidaste la contraseña, recrear admin:
   ```bash
   rm backend/ejercicios.db
   php backend/init_database.php admin nueva_contraseña
   ```

## 🛠️ Desarrollo

### Agregar Nuevo Ejercicio

1. Crear archivo HTML en `ejercicios/`
2. Incluir `config.js` y `ejercicio-tracker.js`
3. Inicializar tracker al inicio
4. Llamar a `tracker.registrarCompletado()` al finalizar
5. Agregar al array en `index.html`

Ejemplo:
```javascript
// En el nuevo ejercicio
const tracker = new EjercicioTracker('mi-ejercicio', 'Mi Ejercicio', 'A1');

async function iniciarEjercicio() {
    await tracker.inicializar();
    // ... lógica del ejercicio
}

async function finalizarEjercicio(resultado, puntuacion) {
    await tracker.registrarCompletado(resultado, puntuacion);
}
```

### Modo Debug

En `backend/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📄 Licencia

Proyecto de uso educativo.

## 🤝 Contribuciones

Para agregar nuevos ejercicios o mejorar el sistema:

1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nuevo-ejercicio`)
3. Commit cambios (`git commit -m 'Agregar nuevo ejercicio'`)
4. Push a la rama (`git push origin feature/nuevo-ejercicio`)
5. Crear Pull Request

## 📧 Soporte

- Issues: https://github.com/[tu-usuario]/ejercicios_didacticos/issues
- Documentación del backend: `backend/README.md`

---

**Desarrollado con ❤️ para la enseñanza del español**

🤖 Sistema de tracking generado con [Claude Code](https://claude.com/claude-code)
