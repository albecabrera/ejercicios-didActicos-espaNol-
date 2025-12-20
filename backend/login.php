<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #6c757d;
            font-size: 1.1em;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #6c757d;
            font-size: 0.95em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            font-size: 1em;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            text-align: center;
            border: 2px solid #f5c6cb;
        }

        .error-message.show {
            display: block;
        }

        .login-btn {
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
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .back-link {
            text-align: center;
            margin-top: 25px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #764ba2;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Dashboard</h1>
            <p>Inicia sesión para acceder</p>
        </div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="error-message" id="errorMessage"></div>

            <div class="form-group">
                <label for="username">Usuario:</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autocomplete="username"
                    placeholder="Ingresa tu usuario"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                >
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                Iniciar Sesión
            </button>
        </form>

        <div class="back-link">
            <a href="../index.html">← Volver a ejercicios</a>
        </div>
    </div>

    <script>
        async function handleLogin(event) {
            event.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const btn = document.getElementById('loginBtn');
            const errorMsg = document.getElementById('errorMessage');

            // Limpiar errores
            errorMsg.classList.remove('show');

            // Deshabilitar botón
            btn.disabled = true;
            btn.textContent = 'Iniciando sesión...';

            try {
                const response = await fetch('auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    // Redirigir al dashboard
                    window.location.href = 'dashboard.php';
                } else {
                    // Mostrar error
                    errorMsg.textContent = data.error || 'Error al iniciar sesión';
                    errorMsg.classList.add('show');
                }
            } catch (error) {
                console.error('Error:', error);
                errorMsg.textContent = 'Error de conexión. Por favor, intenta de nuevo.';
                errorMsg.classList.add('show');
            } finally {
                // Rehabilitar botón
                btn.disabled = false;
                btn.textContent = 'Iniciar Sesión';
            }
        }

        // Focus en el campo de usuario al cargar
        document.getElementById('username').focus();
    </script>
</body>
</html>
