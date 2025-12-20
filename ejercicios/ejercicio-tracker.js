/**
 * Módulo para tracking de ejercicios
 * Maneja la integración con el backend PHP
 */

class EjercicioTracker {
    constructor(ejercicioId, ejercicioTitulo, nivel) {
        this.ejercicioId = ejercicioId;
        this.ejercicioTitulo = ejercicioTitulo;
        this.nivel = nivel;
        this.estudiante = null;
        this.inicioId = null;
        this.tiempoInicio = null;

        // Configuración del backend desde config.js o valor por defecto
        this.API_BASE_URL = window.APP_CONFIG?.apiUrl || window.BACKEND_API_URL || 'http://localhost:8000/api.php';
        this.silentErrors = window.APP_CONFIG?.silentErrors !== undefined ? window.APP_CONFIG.silentErrors : true;
    }

    /**
     * Solicitar nombre del estudiante y registrarlo
     */
    async solicitarNombre() {
        return new Promise((resolve) => {
            // Verificar si ya hay un estudiante en sessionStorage
            const estudianteGuardado = sessionStorage.getItem('estudiante');
            if (estudianteGuardado) {
                this.estudiante = JSON.parse(estudianteGuardado);
                resolve(this.estudiante);
                return;
            }

            // Crear modal para solicitar nombre
            const modalHTML = `
                <div id="nombreModal" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                ">
                    <div style="
                        background: white;
                        padding: 40px;
                        border-radius: 20px;
                        max-width: 500px;
                        width: 90%;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    ">
                        <h2 style="
                            color: #667eea;
                            font-size: 2em;
                            margin-bottom: 20px;
                            text-align: center;
                        ">👋 ¡Bienvenido!</h2>
                        <p style="
                            font-size: 1.1em;
                            color: #6c757d;
                            margin-bottom: 25px;
                            text-align: center;
                        ">Por favor, introduce tu nombre para comenzar:</p>
                        <input
                            type="text"
                            id="nombreInput"
                            placeholder="Tu nombre completo"
                            style="
                                width: 100%;
                                padding: 15px;
                                font-size: 1.1em;
                                border: 2px solid #e9ecef;
                                border-radius: 10px;
                                margin-bottom: 25px;
                            "
                        >
                        <button
                            id="nombreSubmit"
                            style="
                                width: 100%;
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                color: white;
                                border: none;
                                padding: 15px;
                                font-size: 1.2em;
                                border-radius: 10px;
                                cursor: pointer;
                                font-weight: 600;
                                transition: transform 0.3s;
                            "
                        >Comenzar</button>
                        <p id="nombreError" style="
                            color: #dc3545;
                            margin-top: 15px;
                            text-align: center;
                            display: none;
                        "></p>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);

            const input = document.getElementById('nombreInput');
            const submit = document.getElementById('nombreSubmit');
            const error = document.getElementById('nombreError');

            const handleSubmit = async () => {
                const nombre = input.value.trim();

                if (!nombre || nombre.length < 2) {
                    error.textContent = 'Por favor, introduce un nombre válido';
                    error.style.display = 'block';
                    return;
                }

                submit.disabled = true;
                submit.textContent = 'Registrando...';

                try {
                    const response = await fetch(`${this.API_BASE_URL}?action=register_student`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ nombre })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.estudiante = data.estudiante;
                        sessionStorage.setItem('estudiante', JSON.stringify(this.estudiante));

                        // Cerrar modal
                        document.getElementById('nombreModal').remove();

                        // Resolver promesa
                        resolve(this.estudiante);
                    } else {
                        throw new Error(data.error || 'Error al registrar estudiante');
                    }
                } catch (err) {
                    if (!this.silentErrors) {
                        console.error('Error al registrar estudiante:', err);
                        error.textContent = 'Error de conexión. Por favor, intenta de nuevo.';
                        error.style.display = 'block';
                        submit.disabled = false;
                        submit.textContent = 'Comenzar';
                    } else {
                        // En modo silencioso, crear estudiante local sin backend
                        console.warn('Backend no disponible. Modo local activado.');
                        this.estudiante = {
                            id: Date.now(),
                            nombre: nombre,
                            primer_nombre: nombre.split(' ')[0]
                        };
                        sessionStorage.setItem('estudiante', JSON.stringify(this.estudiante));
                        document.getElementById('nombreModal').remove();
                        resolve(this.estudiante);
                    }
                }
            };

            submit.addEventListener('click', handleSubmit);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') handleSubmit();
            });

            // Foco en el input
            setTimeout(() => input.focus(), 100);
        });
    }

    /**
     * Mostrar nombre del estudiante en la interfaz
     */
    mostrarNombreEnHeader() {
        if (!this.estudiante) return;

        const nombreDisplay = document.createElement('div');
        nombreDisplay.id = 'estudianteNombre';
        nombreDisplay.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 10px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-weight: 600;
            color: #667eea;
            z-index: 9999;
            font-size: 1em;
        `;
        nombreDisplay.textContent = `👤 ${this.estudiante.primer_nombre}`;

        document.body.appendChild(nombreDisplay);
    }

    /**
     * Registrar inicio de ejercicio
     */
    async registrarInicio() {
        if (!this.estudiante) {
            console.error('No hay estudiante registrado');
            return false;
        }

        this.tiempoInicio = Date.now();

        try {
            const response = await fetch(`${this.API_BASE_URL}?action=start_exercise`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    estudiante_id: this.estudiante.id,
                    ejercicio_id: this.ejercicioId,
                    ejercicio_titulo: this.ejercicioTitulo
                })
            });

            const data = await response.json();

            if (data.success) {
                this.inicioId = data.inicio_id;
                console.log('Ejercicio iniciado:', data);
                return true;
            } else {
                if (!this.silentErrors) {
                    console.error('Error al iniciar ejercicio:', data.error);
                }
                return false;
            }
        } catch (err) {
            if (!this.silentErrors) {
                console.error('Error de conexión al iniciar ejercicio:', err);
            } else {
                console.warn('Backend no disponible. Continuando en modo local.');
            }
            return this.silentErrors; // Retorna true si errores silenciados, false si no
        }
    }

    /**
     * Registrar ejercicio completado
     */
    async registrarCompletado(resultado, puntuacion) {
        if (!this.estudiante) {
            console.error('No hay estudiante registrado');
            return false;
        }

        const tiempoTranscurrido = this.tiempoInicio
            ? Math.floor((Date.now() - this.tiempoInicio) / 1000)
            : null;

        try {
            const response = await fetch(`${this.API_BASE_URL}?action=complete_exercise`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    estudiante_id: this.estudiante.id,
                    ejercicio_id: this.ejercicioId,
                    ejercicio_titulo: this.ejercicioTitulo,
                    resultado: resultado,
                    puntuacion: puntuacion,
                    nivel: this.nivel,
                    tiempo_transcurrido: tiempoTranscurrido
                })
            });

            const data = await response.json();

            if (data.success) {
                console.log('Ejercicio completado registrado:', data);
                return true;
            } else {
                if (!this.silentErrors) {
                    console.error('Error al registrar completado:', data.error);
                }
                return false;
            }
        } catch (err) {
            if (!this.silentErrors) {
                console.error('Error de conexión al registrar completado:', err);
            } else {
                console.warn('Backend no disponible. Resultado no registrado.');
            }
            return this.silentErrors; // Retorna true si errores silenciados, false si no
        }
    }

    /**
     * Inicializar tracker
     */
    async inicializar() {
        await this.solicitarNombre();
        this.mostrarNombreEnHeader();
        await this.registrarInicio();
    }
}
