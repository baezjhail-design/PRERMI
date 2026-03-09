<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
    <style>
        .register-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1rem;
        }

        .register-container {
            width: 100%;
            max-width: 550px;
        }

        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        .register-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .register-header i {
            font-size: 2.5rem;
        }

        .register-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .register-body {
            padding: 3rem 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .register-footer {
            text-align: center;
            padding: 2rem;
            border-top: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .register-footer p {
            margin: 0;
            color: #666;
        }

        .register-footer a {
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .register-footer a:hover {
            color: #764ba2;
        }

        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .back-link:hover {
            gap: 12px;
        }

        .password-strength {
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            background: #ff6b6b;
        }

        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .register-header {
                padding: 2rem 1.5rem;
            }

            .register-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-container">
            <a href="/PRERMI/index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>

            <div class="register-card">
                <div class="register-header">
                    <h1>
                        <i class="fas fa-user-plus"></i> Crear Cuenta
                    </h1>
                    <p>Únete a PRERMI hoy</p>
                </div>

                <div class="register-body">
                    <div id="msg"></div>
                    
                    <form id="registerForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nombre">
                                    <i class="fas fa-user"></i> Nombre
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       placeholder="Juan" value="" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="apellido">
                                    <i class="fas fa-user"></i> Apellido
                                </label>
                                <input type="text" class="form-control" id="apellido" name="apellido" 
                                       placeholder="Pérez" value="" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="usuario">
                                <i class="fas fa-at"></i> Nombre de Usuario
                            </label>
                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                   placeholder="juanperez" value="" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cedula">
                                <i class="fas fa-id-card"></i> Cédula de Identidad
                            </label>
                            <input type="text" class="form-control" id="cedula" name="cedula" 
                                   placeholder="12345678" value="" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">
                                <i class="fas fa-envelope"></i> Correo Electrónico <span style="color: #ff6b6b;">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="tu@email.com" value="" required>
                            <small style="color: #666; font-size: 0.8rem;">Se enviará un enlace de verificación a este correo</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="telefono">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   placeholder="+1234567890" value="">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="clave">
                                <i class="fas fa-lock"></i> Contraseña
                            </label>
                            <input type="password" class="form-control" id="clave" name="clave" 
                                   placeholder="********" required>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span id="strengthText">Contraseña débil</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="clave-confirm">
                                <i class="fas fa-lock"></i> Confirmar Contraseña
                            </label>
                            <input type="password" class="form-control" id="clave-confirm" name="clave-confirm"
                                   placeholder="********" required>
                        </div>

                        <div class="alert alert-info" style="flex-direction: column; text-align: center;">
                            <i class="fas fa-envelope-open-text" style="font-size: 1.3rem;"></i>
                            <span>Al registrarte se enviará un correo de verificación. <strong>Debes verificar tu email antes de iniciar sesión.</strong></span>
                        </div>

                        <button type="submit" class="btn-register">
                            <i class="fas fa-user-check"></i> Crear Cuenta
                        </button>
                    </form>
                </div>

                <div class="register-footer">
                    <p>¿Ya tienes cuenta? 
                        <a href="/PRERMI/web/login.php">
                            Inicia sesión <i class="fas fa-arrow-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').then(function(reg){
                console.log('SW registered', reg.scope);
            }).catch(function(err){
                console.warn('SW registration failed', err);
            });
        }

        // Indicador de fortaleza de contraseña
        const passwordInput = document.getElementById('clave');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const pass = this.value;
                let strength = 0;

                if (pass.length >= 8) strength++;
                if (/[a-z]/.test(pass) && /[A-Z]/.test(pass)) strength++;
                if (/[0-9]/.test(pass)) strength++;
                if (/[^a-zA-Z0-9]/.test(pass)) strength++;

                const colors = ['#ff6b6b', '#ffd93d', '#51cf66', '#51cf66'];
                const texts = ['Débil', 'Regular', 'Fuerte', 'Muy fuerte'];
                
                strengthFill.style.width = (strength * 25) + '%';
                strengthFill.style.background = colors[strength - 1] || '#ff6b6b';
                strengthText.textContent = texts[strength - 1] || 'Débil';
            });
        }

        // AJAX Form Submission for Registration
        document.getElementById("registerForm").addEventListener("submit", async function(e){
            e.preventDefault();
            
            const msgDiv = document.getElementById("msg");
            const btnRegister = document.querySelector('.btn-register');
            const originalText = btnRegister.innerHTML;
            
            const clave = document.getElementById('clave').value;
            const claveConfirm = document.getElementById('clave-confirm').value;
            
            // Client-side validation
            if (clave !== claveConfirm) {
                msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Las contraseñas no coinciden</div>';
                return;
            }
            
            if (clave.length < 6) {
                msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> La contraseña debe tener al menos 6 caracteres</div>';
                return;
            }
            
            btnRegister.disabled = true;
            btnRegister.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';

            const formData = {
                nombre: document.getElementById('nombre').value,
                apellido: document.getElementById('apellido').value,
                usuario: document.getElementById('usuario').value,
                cedula: document.getElementById('cedula').value,
                email: document.getElementById('email').value,
                telefono: document.getElementById('telefono').value || null,
                clave: clave
            };

            try {
            const res = await fetch("../api/usuarios/register.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(formData)
                });

                const data = await res.json();

                if (data.success) {
                    document.getElementById('registerForm').style.display = 'none';
                    msgDiv.innerHTML = '<div class="alert alert-success" style="flex-direction: column; text-align: center; padding: 2rem;">' +
                        '<div style="font-size: 3rem; margin-bottom: 15px;"><i class="fas fa-envelope-circle-check"></i></div>' +
                        '<h4 style="color: #2d8a56; margin-bottom: 10px;">Cuenta creada exitosamente</h4>' +
                        '<p style="color: #555; margin-bottom: 15px;">Hemos enviado un correo de verificaci\u00f3n a <strong>' + (formData.email) + '</strong></p>' +
                        '<p style="color: #888; font-size: 0.9rem; margin-bottom: 20px;">Revisa tu bandeja de entrada (y spam) y haz clic en el enlace para activar tu cuenta.</p>' +
                        '<a href="login.php" class="btn-register" style="display: inline-block; text-decoration: none; max-width: 300px; margin: 0 auto;"><i class="fas fa-sign-in-alt"></i> Ir a Iniciar Sesi\u00f3n</a>' +
                        '</div>';
                } else {
                    msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (data.msg || 'Error en el registro') + '</div>';
                    btnRegister.disabled = false;
                    btnRegister.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al servidor</div>';
                btnRegister.disabled = false;
                btnRegister.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>


