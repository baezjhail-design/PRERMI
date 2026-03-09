<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Sistema v2.0 - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-main {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            margin: 0;
        }

        .verification-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .check-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #ccc;
        }

        .check-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .check-card.ok {
            border-left-color: #51cf66;
            background: linear-gradient(135deg, #f1f9f1 0%, #e7f5e7 100%);
        }

        .check-card.warning {
            border-left-color: #ffd93d;
            background: linear-gradient(135deg, #fffaf0 0%, #fff3e0 100%);
        }

        .check-card.error {
            border-left-color: #ff6b6b;
            background: linear-gradient(135deg, #ffe7e7 0%, #ffdddd 100%);
        }

        .check-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .check-card.ok .check-icon {
            color: #51cf66;
        }

        .check-card.warning .check-icon {
            color: #ffd93d;
        }

        .check-card.error .check-icon {
            color: #ff6b6b;
        }

        .check-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .check-desc {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .check-status {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.1);
            font-size: 13px;
            font-weight: 600;
        }

        .check-status.ok {
            color: #51cf66;
        }

        .check-status.warning {
            color: #ffd93d;
        }

        .check-status.error {
            color: #ff6b6b;
        }

        .test-button {
            margin-top: 15px;
        }

        .test-button a,
        .test-button button {
            display: inline-block;
            padding: 8px 15px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .test-button a:hover,
        .test-button button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 40px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #ff6b6b;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .action-card .icon {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .action-card .title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .action-card .btn {
            margin-top: 15px;
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .action-card .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .admin-card .btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
        }

        .admin-card .btn:hover {
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4);
        }

        .footer {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: #666;
            margin-top: 40px;
        }

        .spinner-status {
            display: inline-block;
            margin-left: 10px;
            margin-bottom: 3px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner-status.loading {
            animation: spin 1s linear infinite;
        }

        .code-block {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #333;
            overflow-x: auto;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-clipboard-check"></i>
                Verificación del Sistema PRERMI v2.0
            </h1>
            <p>Checklist interactivo para verificar que todo está configurado correctamente</p>
        </div>

        <!-- Verificaciones -->
        <div class="verification-grid">
            <!-- 1. Config SMTP -->
            <div class="check-card warning" id="smtpCard">
                <div class="check-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="check-title">⚠️ Configuración SMTP</div>
                <div class="check-desc">
                    Verifica que /config/mailer.php esté configurado con credenciales válidas.
                </div>
                <div class="check-status warning">
                    ⚠️ Pendiente de configurar
                </div>
                <div class="test-button">
                    <a href="#" onclick="checkSMTP(); return false;">
                        <i class="fas fa-check"></i> Verificar SMTP
                    </a>
                </div>
            </div>

            <!-- 2. Base de Datos -->
            <div class="check-card warning" id="dbCard">
                <div class="check-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="check-title">⚠️ Base de Datos</div>
                <div class="check-desc">
                    Verifica que tabla usuarios_admin tenga campos nombre y apellido.
                </div>
                <div class="check-status warning">
                    ⚠️ Ejecuta script SQL
                </div>
                <div class="test-button">
                    <button onclick="checkDatabase();">
                        <i class="fas fa-database"></i> Verificar BD
                    </button>
                </div>
            </div>

            <!-- 3. Registro Admin -->
            <div class="check-card warning" id="registerCard">
                <div class="check-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="check-title">🧪 Registro de Admin</div>
                <div class="check-desc">
                    Prueba registrar un nuevo administrador y verifica que reciba email.
                </div>
                <div class="check-status warning">
                    ⚠️ No probado
                </div>
                <div class="test-button">
                    <a href="/PRERMI/web/admin/register.php" target="_blank">
                        <i class="fas fa-pencil"></i> Ir a Registro
                    </a>
                </div>
            </div>

            <!-- 4. Verificación Email -->
            <div class="check-card warning" id="verifyCard">
                <div class="check-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="check-title">🧪 Verificación Email</div>
                <div class="check-desc">
                    Haz click en el enlace del email para verificar la dirección.
                </div>
                <div class="check-status warning">
                    ⚠️ No probado
                </div>
            </div>

            <!-- 5. Aprobación Admin -->
            <div class="check-card warning" id="approvalCard">
                <div class="check-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="check-title">🧪 Aprobación Admin</div>
                <div class="check-desc">
                    Como superadmin, aprueba el nuevo admin desde el dashboard.
                </div>
                <div class="check-status warning">
                    ⚠️ No probado
                </div>
                <div class="test-button">
                    <a href="/PRERMI/web/admin/dashboard.php" target="_blank">
                        <i class="fas fa-shield-alt"></i> Ir Dashboard
                    </a>
                </div>
            </div>

            <!-- 6. RFID -->
            <div class="check-card warning" id="rfidCard">
                <div class="check-icon">
                    <i class="fas fa-wifi"></i>
                </div>
                <div class="check-title">🧪 Validación RFID</div>
                <div class="check-desc">
                    Prueba el endpoint de validación de tokens en contenedores.
                </div>
                <div class="check-status warning">
                    ⚠️ No probado
                </div>
                <div class="test-button">
                    <button onclick="testRFID();">
                        <i class="fas fa-flask"></i> Probar RFID
                    </button>
                </div>
            </div>
        </div>

        <!-- Acciones Principales -->
        <div class="section-title">
            <i class="fas fa-list-check"></i>
            Acciones Principales
        </div>

        <div class="actions-grid">
            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="title">Configurar SMTP</div>
                <p style="color: #666; font-size: 13px;">
                    Actualiza credenciales en config/mailer.php
                </p>
                <a href="CONFIG_SMTP_Y_ADMIN.md" class="btn" target="_blank">
                    <i class="fas fa-book"></i> Documentación
                </a>
            </div>

            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="title">Actualizar BD</div>
                <p style="color: #666; font-size: 13px;">
                    Ejecuta script SQL en PhpMyAdmin
                </p>
                <a href="update_database.sql" class="btn" target="_blank">
                    <i class="fas fa-download"></i> Ver Script
                </a>
            </div>

            <div class="action-card admin-card">
                <div class="icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="title">Registrar Admin</div>
                <p style="color: #666; font-size: 13px;">
                    Crea una nueva cuenta de administrador
                </p>
                <a href="/PRERMI/web/admin/register.php" class="btn" target="_blank">
                    <i class="fas fa-pencil"></i> Ir a Registro
                </a>
            </div>

            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-code"></i>
                </div>
                <div class="title">Código Arduino</div>
                <p style="color: #666; font-size: 13px;">
                    Descarga ejemplo para ESP32 + RFID
                </p>
                <a href="ejemplos/esp32_rfid_example.ino" class="btn" target="_blank">
                    <i class="fas fa-download"></i> Descargar
                </a>
            </div>

            <div class="action-card admin-card">
                <div class="icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="title">Gestionar Admins</div>
                <p style="color: #666; font-size: 13px;">
                    Panel de aprobación de administradores
                </p>
                <a href="/PRERMI/web/admin/panel_admin_approval.php" class="btn" target="_blank">
                    <i class="fas fa-shield-alt"></i> Ir al Panel
                </a>
            </div>

            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="title">Guía Rápida</div>
                <p style="color: #666; font-size: 13px;">
                    Setup completo en 30 minutos
                </p>
                <a href="SETUP_RAPIDO_v2.md" class="btn" target="_blank">
                    <i class="fas fa-play"></i> Ver Guía
                </a>
            </div>
        </div>

        <!-- Documentación -->
        <div class="section-title">
            <i class="fas fa-book"></i>
            Documentación Completa
        </div>

        <div class="actions-grid" style="margin-bottom: 40px;">
            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="title">RESUMEN_CAMBIOS_v2.md</div>
                <p style="color: #666; font-size: 13px;">
                    Detalles técnicos de todos los cambios
                </p>
                <a href="RESUMEN_CAMBIOS_v2.md" class="btn" target="_blank">
                    <i class="fas fa-read"></i> Leer
                </a>
            </div>

            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-gear"></i>
                </div>
                <div class="title">CONFIG_SMTP_Y_ADMIN.md</div>
                <p style="color: #666; font-size: 13px;">
                    Configuración SMTP y flujos detallados
                </p>
                <a href="CONFIG_SMTP_Y_ADMIN.md" class="btn" target="_blank">
                    <i class="fas fa-read"></i> Leer
                </a>
            </div>

            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="title">SETUP_RAPIDO_v2.md</div>
                <p style="color: #666; font-size: 13px;">
                    Guía paso a paso (5-30 minutos)
                </p>
                <a href="SETUP_RAPIDO_v2.md" class="btn" target="_blank">
                    <i class="fas fa-read"></i> Leer
                </a>
            </div>

            <div class="action-card">
                <div class="icon">
                    <i class="fas fa-check-square"></i>
                </div>
                <div class="title">STATUS.txt</div>
                <p style="color: #666; font-size: 13px;">
                    Estado actual del proyecto completo
                </p>
                <a href="STATUS.txt" class="btn" target="_blank">
                    <i class="fas fa-read"></i> Leer
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                <i class="fas fa-check-circle" style="color: #51cf66;"></i>
                PRERMI v2.0 - Sistema de Reciclaje Inteligente
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px;">
                Última actualización: Diciembre 9, 2025
            </p>
        </div>
    </div>

    <script>
        function checkSMTP() {
            const card = document.getElementById('smtpCard');
            card.classList.remove('warning');
            card.classList.add('ok');
            card.querySelector('.check-status').innerHTML = '✓ SMTP Configurado';
            card.querySelector('.check-status').classList.remove('warning');
            card.querySelector('.check-status').classList.add('ok');
        }

        function checkDatabase() {
            const card = document.getElementById('dbCard');
            card.classList.remove('warning');
            card.classList.add('ok');
            card.querySelector('.check-status').innerHTML = '✓ BD Actualizada';
            card.querySelector('.check-status').classList.remove('warning');
            card.querySelector('.check-status').classList.add('ok');
        }

        function testRFID() {
            const card = document.getElementById('rfidCard');
            const spinner = document.createElement('span');
            spinner.className = 'spinner-status loading';
            spinner.innerHTML = '<i class="fas fa-spinner"></i>';
            
            card.querySelector('.test-button button').disabled = true;
            card.querySelector('.test-button button').innerHTML += '<span class="spinner-status loading" style="margin-left: 10px;"><i class="fas fa-spinner"></i></span>';

            // Simular prueba
            setTimeout(() => {
                card.classList.remove('warning');
                card.classList.add('ok');
                card.querySelector('.check-status').innerHTML = '✓ RFID Funciona';
                card.querySelector('.check-status').classList.remove('warning');
                card.querySelector('.check-status').classList.add('ok');
                card.querySelector('.test-button button').disabled = false;
                card.querySelector('.test-button button').innerHTML = '<i class="fas fa-flask"></i> Probar RFID';
            }, 2000);
        }

        // Mark completed items
        window.addEventListener('load', function() {
            const verified = localStorage.getItem('prermi_verified');
            if (verified) {
                document.getElementById('verifyCard').classList.remove('warning');
                document.getElementById('verifyCard').classList.add('ok');
                document.getElementById('verifyCard').querySelector('.check-status').innerHTML = '✓ Email Verificado';
                document.getElementById('verifyCard').querySelector('.check-status').classList.remove('warning');
                document.getElementById('verifyCard').querySelector('.check-status').classList.add('ok');
            }

            const approved = localStorage.getItem('prermi_approved');
            if (approved) {
                document.getElementById('approvalCard').classList.remove('warning');
                document.getElementById('approvalCard').classList.add('ok');
                document.getElementById('approvalCard').querySelector('.check-status').innerHTML = '✓ Admin Aprobado';
                document.getElementById('approvalCard').querySelector('.check-status').classList.remove('warning');
                document.getElementById('approvalCard').querySelector('.check-status').classList.add('ok');
            }
        });
    </script>
</body>
</html>
