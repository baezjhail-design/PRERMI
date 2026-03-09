# Código Frontend Actualizado - Cambios Estructurales

---

## 1. web/register.php

```php
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
        .register-container { width: 100%; max-width: 550px; }
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
        .register-header i { font-size: 2.5rem; }
        .register-header p { margin: 0; opacity: 0.9; font-size: 1rem; }
        .register-body { padding: 3rem 2rem; }
        .form-group { margin-bottom: 1.5rem; }
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
            .form-row { grid-template-columns: 1fr; }
            .register-header { padding: 2rem 1.5rem; }
            .register-body { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-container">
            <a href="index.php" class="back-link" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 2rem; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
            <div class="register-card">
                <div class="register-header">
                    <h1><i class="fas fa-user-plus"></i> Crear Cuenta</h1>
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
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Juan" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="apellido">
                                    <i class="fas fa-user"></i> Apellido
                                </label>
                                <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Pérez" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="usuario">
                                <i class="fas fa-at"></i> Nombre de Usuario
                            </label>
                            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="juanperez" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cedula">
                                <i class="fas fa-id-card"></i> Cédula de Identidad
                            </label>
                            <input type="text" class="form-control" id="cedula" name="cedula" placeholder="12345678" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">
                                <i class="fas fa-envelope"></i> Correo Electrónico
                            </label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="tu@email.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="telefono">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="+1234567890">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="clave">
                                <i class="fas fa-lock"></i> Contraseña
                            </label>
                            <input type="password" class="form-control" id="clave" name="clave" placeholder="••••••••" required>
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span id="strengthText" style="font-size: 0.85rem; margin-top: 0.5rem;">Contraseña débil</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="clave-confirm">
                                <i class="fas fa-lock"></i> Confirmar Contraseña
                            </label>
                            <input type="password" class="form-control" id="clave-confirm" name="clave-confirm" placeholder="••••••••" required>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle"></i>
                            <span>Al registrarte aceptas nuestros <a href="#" style="color: inherit; font-weight: 600;">términos de servicio</a></span>
                        </div>
                        <button type="submit" class="btn-register">
                            <i class="fas fa-user-check"></i> Crear Cuenta
                        </button>
                    </form>
                </div>
                <div class="register-footer">
                    <p>¿Ya tienes cuenta? <a href="/PRERMI/web/login.php" style="color: #667eea; font-weight: 600; text-decoration: none;">Inicia sesión <i class="fas fa-arrow-right"></i></a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').catch(function(err){
                console.warn('SW registration failed', err);
            });
        }

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

        document.getElementById("registerForm").addEventListener("submit", async function(e){
            e.preventDefault();
            
            const msgDiv = document.getElementById("msg");
            const btnRegister = document.querySelector('.btn-register');
            const originalText = btnRegister.innerHTML;
            
            const clave = document.getElementById('clave').value;
            const claveConfirm = document.getElementById('clave-confirm').value;
            
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
                email: document.getElementById('email').value || null,
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
                    msgDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Cuenta creada exitosamente. Se envió un correo de confirmación. Redirigiendo a login...</div>';
                    setTimeout(() => { window.location.href = "login.php"; }, 3000);
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
```

---

## 2. web/login.php

```php
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }
        .login-container { width: 100%; max-width: 450px; }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        .login-header {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .login-header i { font-size: 2.5rem; }
        .login-header p { margin: 0; opacity: 0.9; font-size: 1rem; }
        .login-body { padding: 3rem 2rem; }
        .form-group { margin-bottom: 1.5rem; }
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
            border-color: #4ecdc4;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
            outline: none;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(78, 205, 196, 0.3);
        }
        .login-footer {
            text-align: center;
            padding: 2rem;
            border-top: 1px solid #e0e0e0;
            background: #f8f9fa;
        }
        @media (max-width: 576px) {
            .login-header { padding: 2rem 1.5rem; }
            .login-body { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <a href="index.php" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 2rem; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
            <div class="login-card">
                <div class="login-header">
                    <h1><i class="fas fa-user-circle"></i> Usuario</h1>
                    <p>Acceso a tu cuenta</p>
                </div>
                <div class="login-body">
                    <div id="msg"></div>
                    <form id="loginForm">
                        <div class="form-group">
                            <label class="form-label" for="usuario">
                                <i class="fas fa-user"></i> Nombre de Usuario
                            </label>
                            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="usuario" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="clave">
                                <i class="fas fa-lock"></i> Contraseña
                            </label>
                            <input type="password" class="form-control" id="clave" name="clave" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn-login" id="btnLogin">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </form>
                    <div class="alert alert-info" style="margin-top: 1.5rem;">
                        <i class="fas fa-info-circle"></i>
                        <span>¿Olvidaste tu contraseña? <a href="#" style="color: inherit; font-weight: 600;">Recupérala aquí</a></span>
                    </div>
                </div>
                <div class="login-footer">
                    <p>¿No tienes cuenta? <a href="register.php" style="color: #4ecdc4; font-weight: 600; text-decoration: none;">Regístrate ahora <i class="fas fa-arrow-right"></i></a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').catch(function(err){
                console.warn('SW registration failed', err);
            });
        }

        document.getElementById("loginForm").addEventListener("submit", async function(e){
            e.preventDefault();
            
            const btnLogin = document.getElementById("btnLogin");
            const msgDiv = document.getElementById("msg");
            const originalText = btnLogin.innerHTML;
            
            btnLogin.disabled = true;
            btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Autenticando...';

            const formData = {
                usuario: document.getElementById('usuario').value,
                clave: document.getElementById('clave').value
            };

            try {
                const res = await fetch("../api/usuarios/login.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(formData)
                });

                const data = await res.json();

                if (data.success) {
                    msgDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Login exitoso, redirigiendo...</div>';
                    setTimeout(() => { window.location.href = "user-dashboard.php"; }, 1500);
                } else {
                    msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (data.msg || 'Error en la autenticación') + '</div>';
                    btnLogin.disabled = false;
                    btnLogin.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                msgDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error de conexión al servidor</div>';
                btnLogin.disabled = false;
                btnLogin.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>
```

---

## 3. web/user-dashboard.php (Fragmento crítico actualizado)

```php
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../api/utils.php';

try {
    $pdo = getPDO();
    
    $stmtUser = $pdo->prepare("SELECT nombre, apellido, usuario, email, token FROM usuarios WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    // ACTUALIZADO: usar id_contenedor en lugar de contenedor_id
    $stmtDepositos = $pdo->prepare("
        SELECT d.id, d.id_contenedor, d.peso, d.tipo_residuo, d.metal_detectado, d.credito_kwh, d.fecha_hora, d.token_usado, 
               c.id_contenedor as contenedor_nombre, c.ubicacion
        FROM depositos d
        LEFT JOIN contenedores_registrados c ON d.id_contenedor = c.id
        WHERE d.user_id = ?
        ORDER BY d.fecha_hora DESC
        LIMIT 50
    ");
    $stmtDepositos->execute([$_SESSION['user_id']]);
    $depositos = $stmtDepositos->fetchAll(PDO::FETCH_ASSOC);
    
    $totalDepositos = count($depositos);
    $totalPeso = 0;
    $totalCredito = 0;
    $metalDetectados = 0;
    
    foreach ($depositos as $dep) {
        $totalPeso += $dep['peso'];
        $totalCredito += $dep['credito_kwh'];
        if ($dep['metal_detectado']) {
            $metalDetectados++;
        }
    }
    
    $stmtMultas = $pdo->prepare("SELECT COUNT(*) as total FROM multas WHERE user_id = ?");
    $stmtMultas->execute([$_SESSION['user_id']]);
    $multasResult = $stmtMultas->fetch();
    $totalMultas = $multasResult['total'];
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!-- HTML resto igual, pero en la tabla de depósitos: -->

                        <tbody>
                            <?php foreach ($depositos as $dep): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dep['contenedor_nombre'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($dep['ubicacion'] ?? 'No especificada'); ?></td>
                                <td><?php echo number_format($dep['peso'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($dep['tipo_residuo'] ?? 'general'); ?></td>
                                <td>
                                    <?php 
                                    if ($dep['metal_detectado']) {
                                        echo '<span class="badge-metal"><i class="fas fa-exclamation"></i> Detectado</span>';
                                    } else {
                                        echo '<span class="badge-ok"><i class="fas fa-check"></i> Normal</span>';
                                    }
                                    ?>
                                </td>
                                <td><strong><?php echo number_format($dep['credito_kwh'], 4); ?> kWh</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($dep['fecha_hora'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($depositos)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <em>Aún no has realizado depósitos. ¡Comienza a reciclar!</em>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
```

---

## 4. web/contenedores.php (Fragmento actualizado)

```php
<?php 
session_start();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../api/utils.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin/loginA.php");
    exit;
}

try {
    $pdo = getPDO();
    
    // ACTUALIZADO: obtener contenedores con nuevos campos (ultimo_token, token_generado_en, token_expira_en)
    $stmtContenedores = $pdo->query("
        SELECT id, id_contenedor, nivel_basura, ubicacion, latitud, longitud, 
               ultimo_token, token_generado_en, token_expira_en, estado
        FROM contenedores_registrados 
        ORDER BY actualizado_en DESC
    ");
    $contenedoresData = $stmtContenedores->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!-- En la sección de vista de tabla: -->

                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID Contenedor</th>
                                            <th>Ubicación</th>
                                            <th>Nivel Basura (%)</th>
                                            <th>Último Token</th>
                                            <th>Token Expira</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contenedoresData as $cont): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cont['id_contenedor']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($cont['ubicacion'] ?? 'No especificada'); ?></td>
                                            <td><?php echo $cont['nivel_basura']; ?>%</td>
                                            <td>
                                                <code style="font-size: 0.75rem;">
                                                    <?php echo substr($cont['ultimo_token'] ?? 'N/A', 0, 12) . '...'; ?>
                                                </code>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($cont['token_expira_en'])) ?: 'No definido'; ?></td>
                                            <td>
                                                <?php 
                                                $estado = $cont['estado'] ?? 'activo';
                                                $badgeClass = $estado === 'activo' ? 'badge-success' : 'badge-danger';
                                                echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($estado) . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="verContenedor(<?php echo $cont['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="editarContenedor(<?php echo $cont['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="eliminarContenedor(<?php echo $cont['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verContenedor(id) {
            fetch('../api/contenedores/obtener.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('ID Contenedor: ' + data.contenedor.id_contenedor + 
                              '\nUbicación: ' + data.contenedor.ubicacion);
                    }
                });
        }

        function editarContenedor(id) {
            const nuevoToken = prompt('Ingresar nuevo token:');
            if (nuevoToken) {
                fetch('../api/contenedores/actualizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, ultimo_token: nuevoToken })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }

        function eliminarContenedor(id) {
            if (confirm('¿Estás seguro de eliminar este contenedor?')) {
                fetch('../api/contenedores/eliminar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>
```

---

## 5. web/usuarios/depositos_usuario.php (Actualizado)

```php
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

require_once "../../config/db_config.php";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$id = $_SESSION['usuario_id'];

// ACTUALIZADO: usar id_contenedor, tipo_residuo, fecha_hora, token_usado
$query = $conn->query("
    SELECT d.id, d.id_contenedor, d.peso, d.tipo_residuo, d.credito_kwh, d.fecha_hora, d.token_usado, c.ubicacion 
    FROM depositos d 
    LEFT JOIN contenedores_registrados c ON d.id_contenedor = c.id 
    WHERE d.user_id='$id' 
    ORDER BY d.fecha_hora DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Depósitos - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">
    <style>
        table {
            width: 90%;
            margin: 25px auto;
            background: white;
            border-radius: 10px;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background: #00aa99;
            color: white;
        }
    </style>
</head>

<body>
<header>
    <h1>PRERMI</h1>
</header>

<h2 style="text-align:center; color:white;">Historial de Depósitos</h2>

<table>
    <tr>
        <th>Contenedor</th>
        <th>Peso (kg)</th>
        <th>Tipo Residuo</th>
        <th>Crédito (kWh)</th>
        <th>Fecha/Hora</th>
        <th>Token Usado</th>
        <th>Ubicación</th>
    </tr>

    <?php while($row = $query->fetch_assoc()): ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($row['id_contenedor'] ?? 'Desconocido'); ?></strong></td>
            <td><?php echo number_format($row['peso'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['tipo_residuo'] ?? 'general'); ?></td>
            <td><?php echo number_format($row['credito_kwh'], 4); ?></td>
            <td><?php echo date('d/m/Y H:i:s', strtotime($row['fecha_hora'])); ?></td>
            <td><code style="font-size: 0.75rem;"><?php echo substr($row['token_usado'] ?? 'N/A', 0, 12); ?>...</code></td>
            <td><?php echo htmlspecialchars($row['ubicacion'] ?? 'No especificada'); ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
```

---

## 6. web/usuarios/multas_usuario.php (Actualizado)

```php
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

require_once "../../config/db_config.php";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$id = $_SESSION['usuario_id'];

// ACTUALIZADO: usar id_contenedor en lugar de contenedor_id
$query = $conn->query("
    SELECT m.id, m.id_contenedor, m.descripcion, m.peso, m.creado_en, c.ubicacion 
    FROM multas m 
    LEFT JOIN contenedores_registrados c ON m.id_contenedor = c.id 
    WHERE m.user_id='$id'
    ORDER BY m.creado_en DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Multas - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">
    <style>
        table {
            width: 90%;
            margin: 25px auto;
            background: white;
            border-radius: 10px;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ccc;
        }
        th {
            background: #cc0000;
            color: white;
        }
    </style>
</head>

<body>
<header>
    <h1>PRERMI</h1>
</header>

<h2 style="color:white; text-align:center;">Multas Registradas</h2>

<table>
    <tr>
        <th>ID Contenedor</th>
        <th>Ubicación</th>
        <th>Motivo</th>
        <th>Peso (kg)</th>
        <th>Fecha</th>
    </tr>

    <?php while($row = $query->fetch_assoc()): ?>
    <tr>
        <td><strong><?php echo htmlspecialchars($row['id_contenedor'] ?? 'Desconocido'); ?></strong></td>
        <td><?php echo htmlspecialchars($row['ubicacion'] ?? 'No especificada'); ?></td>
        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
        <td><?php echo number_format($row['peso'], 2); ?></td>
        <td><?php echo date('d/m/Y H:i', strtotime($row['creado_en'])); ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<p style="text-align:center;">
    <a href="dashboard_usuario.php">← Volver al Dashboard</a>
</p>

</body>
</html>
```

---

## 7. web/usuarios/tarjeta_usuario.php (Mostrar token)

```php
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login_usuario.php");
    exit();
}

require_once "../../config/db_config.php";
require_once "../../api/utils.php";

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, usuario, token FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tarjeta Digital - PRERMI</title>
    <link rel="stylesheet" href="estilos_usuario.css">
    <style>
        .token-box {
            width: 400px;
            background: white;
            margin: 40px auto;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .token-box h2 {
            color: #0077aa;
            margin-bottom: 20px;
        }
        .token-display {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            margin: 20px 0;
            border: 2px dashed #0077aa;
        }
        .copy-btn {
            background: #0077aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }
        .copy-btn:hover {
            background: #005588;
        }
    </style>
</head>
<body>

<header>
    <h1>PRERMI</h1>
</header>

<div class="token-box">
    <h2>Mi Tarjeta Digital</h2>
    <p>Este es tu token único para desbloquear contenedores:</p>
    <div class="token-display" id="tokenDisplay">
        <?php echo htmlspecialchars($user['token']); ?>
    </div>
    <button class="copy-btn" onclick="copyToken()">Copiar Token</button>
    <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
        Conserva este token en lugar seguro. Es único y personal.
    </p>
    <p>
        <a href="dashboard_usuario.php">← Volver al Dashboard</a>
    </p>
</div>

<script>
    function copyToken() {
        const tokenText = document.getElementById('tokenDisplay').innerText;
        navigator.clipboard.writeText(tokenText).then(() => {
            alert('Token copiado al portapapeles');
        });
    }
</script>

</body>
</html>
```

---

## RESUMEN DE CAMBIOS FRONTEND

### Cambios JSON/Fetch:
- ✓ `contenedor_id` → `id_contenedor` (todas las consultas y respuestas)
- ✓ Nuevos campos en depositos: `tipo_residuo`, `token_usado`, `fecha_hora`
- ✓ Nuevos campos en contenedores: `ultimo_token`, `token_generado_en`, `token_expira_en`, `estado`

### Cambios en formularios:
- ✓ Validaciones actualizadas para campos nuevos
- ✓ Nombres de campos en `fetch()` coinciden con backend

### Cambios en vistas:
- ✓ Tablas muestran nuevas columnas
- ✓ Alias de campos actualizados
- ✓ URLs de endpoints frontend correctas

### Cambios en JavaScript:
- ✓ AJAX requests usan nombres de campos nuevos
- ✓ Manejo de respuestas JSON actualizado
- ✓ Funciones de copiar token implementadas
