<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Administrador - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 550px;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .register-header {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .register-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .register-header p {
            font-size: 14px;
            opacity: 0.95;
            margin: 0;
        }

        .register-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .register-form {
            padding: 35px 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            background-color: white;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control::placeholder {
            color: #999;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #2563eb; }
        .strength-fair { background: #ffd93d; }
        .strength-good { background: #51cf66; }

        .password-tips {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            margin-top: 10px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
            color: white;
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: #ffe0e0;
            color: #1d4ed8;
        }

        .alert-success {
            background-color: #e7f5e7;
            color: #2b8a3e;
        }

        .alert-info {
            background-color: #e7f5ff;
            color: #1971c2;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: #7c3aed;
            text-decoration: underline;
        }

        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
            margin-right: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .register-header {
                padding: 30px 20px;
            }

            .register-header h1 {
                font-size: 24px;
            }

            .register-form {
                padding: 25px 20px;
            }
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #2563eb;
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
        }

        .info-box i {
            color: #2563eb;
            margin-right: 8px;
        }

        .loading-spinner {
            display: none;
        }

        .loading-spinner.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="register-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Registro de Administrador</h1>
                <p>Únete al equipo de PRERMI como administrador</p>
            </div>

            <!-- Form -->
            <div class="register-form">
                <div id="message" style="display:none;"></div>

                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    Se enviará un email de confirmación. Un superadmin debe verificar tu cuenta.
                </div>

                <form id="registerForm" onsubmit="handleRegister(event)">
                    <!-- Usuario -->
                    <div class="form-group">
                        <label for="usuario" class="form-label">
                            <i class="fas fa-user"></i> Nombre de Usuario
                        </label>
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            class="form-control" 
                            placeholder="Ejemplo: admin_jhail"
                            required
                        >
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Correo Electrónico
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            placeholder="tu_email@ejemplo.com"
                            required
                        >
                    </div>

                    <!-- Nombre y Apellido (Una fila) -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user-circle"></i> Nombre
                            </label>
                            <input 
                                type="text" 
                                id="nombre" 
                                name="nombre" 
                                class="form-control" 
                                placeholder="Tu nombre"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label for="apellido" class="form-label">
                                <i class="fas fa-user-circle"></i> Apellido
                            </label>
                            <input 
                                type="text" 
                                id="apellido" 
                                name="apellido" 
                                class="form-control" 
                                placeholder="Tu apellido"
                                required
                            >
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label for="clave" class="form-label">
                            <i class="fas fa-lock"></i> Contraseña
                        </label>
                        <input 
                            type="password" 
                            id="clave" 
                            name="clave" 
                            class="form-control" 
                            placeholder="Mínimo 8 caracteres"
                            onkeyup="checkPasswordStrength(this.value)"
                            required
                        >
                        <div class="password-strength">
                            <div id="strengthBar" class="password-strength-bar"></div>
                        </div>
                        <div class="password-tips">
                            ✓ Minúsculas, mayúsculas, números y símbolos
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="form-group">
                        <label for="clave_confirm" class="form-label">
                            <i class="fas fa-lock"></i> Confirmar Contraseña
                        </label>
                        <input 
                            type="password" 
                            id="clave_confirm" 
                            name="clave_confirm" 
                            class="form-control" 
                            placeholder="Repite tu contraseña"
                            required
                        >
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="btn-register">
                        <span class="loading-spinner" id="spinner">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </span>
                        <span id="btnText">Crear Cuenta de Admin</span>
                    </button>
                </form>

                <!-- Login Link -->
                <div class="login-link">
                    ¿Ya eres administrador? 
                    <a href="loginA.php">Inicia sesión aquí</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Validar fortaleza de contraseña
        function checkPasswordStrength(password) {
            const bar = document.getElementById('strengthBar');
            let strength = 0;

            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 10;

            bar.style.width = strength + '%';
            bar.className = 'password-strength-bar';

            if (strength < 50) bar.classList.add('strength-weak');
            else if (strength < 75) bar.classList.add('strength-fair');
            else bar.classList.add('strength-good');
        }

        // Manejar registro
        async function handleRegister(event) {
            event.preventDefault();

            const usuario = document.getElementById('usuario').value.trim();
            const email = document.getElementById('email').value.trim();
            const nombre = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            const clave = document.getElementById('clave').value;
            const clave_confirm = document.getElementById('clave_confirm').value;

            // Validar contraseñas coinciden
            if (clave !== clave_confirm) {
                showMessage('Las contraseñas no coinciden', 'danger');
                return;
            }

            // Validar fortaleza
            if (clave.length < 8) {
                showMessage('La contraseña debe tener al menos 8 caracteres', 'danger');
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');

            submitBtn.disabled = true;
            spinner.classList.add('show');
            btnText.textContent = 'Registrando...';

            try {
                const response = await fetch('/PRERMI/api/admin/registerA.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        usuario,
                        email,
                        nombre,
                        apellido,
                        clave
                    })
                });

                const data = await response.json();

                if (data.success === false) {
                    showMessage(data.msg || 'Error en el registro', 'danger');
                } else if (data.success === true) {
                    showMessage('¡Registro exitoso! Se envió un email de confirmación. Espera a que un superadmin verifique tu cuenta.', 'success');
                    document.getElementById('registerForm').reset();
                    document.getElementById('strengthBar').style.width = '0%';
                    
                    // Redirigir después de 3 segundos
                    setTimeout(() => {
                        window.location.href = 'loginA.php';
                    }, 3000);
                } else {
                    showMessage(data.message || 'Error inesperado en el servidor', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error de conexión. Intenta de nuevo.', 'danger');
            } finally {
                submitBtn.disabled = false;
                spinner.classList.remove('show');
                btnText.textContent = 'Crear Cuenta de Admin';
            }
        }

        // Mostrar mensajes
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.className = `alert alert-${type}`;
            messageDiv.innerHTML = `<i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'check-circle'}"></i> ${text}`;
            messageDiv.style.display = 'block';
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    </script>
</body>
</html>


