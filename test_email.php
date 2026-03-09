<?php
// PRUEBA RÁPIDA: test_email.php
// Accede a: http://localhost:8080/PRERMI/test_email.php

require_once __DIR__ . '/config/mailer.php';

echo "<style>
body { font-family: Arial; background: #f5f5f5; padding: 20px; }
.container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
h1 { color: #333; }
.form-group { margin-bottom: 20px; }
label { display: block; font-weight: bold; margin-bottom: 5px; }
input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
button { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
button:hover { background: #5568d3; }
.success { background: #e7f5e7; color: #2b8a3e; padding: 15px; border-radius: 5px; margin: 20px 0; }
.error { background: #ffe7e7; color: #c92a2a; padding: 15px; border-radius: 5px; margin: 20px 0; }
.info { background: #e7f5ff; color: #1971c2; padding: 15px; border-radius: 5px; margin: 20px 0; }
</style>";

echo "<div class='container'>";
echo "<h1>🧪 Prueba de Email - PRERMI</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    
    if (!$email || !$nombre) {
        echo "<div class='error'>❌ Email y nombre son obligatorios</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='error'>❌ Email inválido</div>";
    } else {
        try {
            $mail = getMailer();
            $mail->addAddress($email, $nombre);
            $mail->Subject = '🎉 Prueba de Email - PRERMI';
            $mail->isHTML(true);
            
            $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { background: #f5f5f5; padding: 20px; }
                    .card { background: white; padding: 30px; border-radius: 10px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
                    .success-icon { font-size: 40px; }
                    h1 { color: #333; margin: 0; }
                    p { color: #666; line-height: 1.6; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='card'>
                        <div class='header'>
                            <div class='success-icon'>✅</div>
                            <h1>¡Email Funciona!</h1>
                        </div>
                        <p>Hola <strong>$nombre</strong>,</p>
                        <p>Este es un email de prueba enviado desde PRERMI usando Mailtrap.</p>
                        <p style='background: #f0f0f0; padding: 15px; border-radius: 5px; border-left: 4px solid #667eea;'>
                            Si recibes este mensaje, significa que tu configuración SMTP está <strong>correcta</strong> y los emails se enviarán sin problemas.
                        </p>
                        <p><strong>Próximos pasos:</strong></p>
                        <ul>
                            <li>Ahora puedes registrar usuarios y admins</li>
                            <li>Los emails se enviarán automáticamente</li>
                            <li>En Mailtrap verás todos los emails capturados</li>
                            <li>Para producción, cambia a Gmail o SendGrid</li>
                        </ul>
                        <div class='footer'>
                            <p>PRERMI - Plataforma de Reciclaje Inteligente</p>
                            <p>Email enviado el " . date('d/m/Y H:i:s') . "</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>";
            
            $mail->Body = $body;
            $mail->send();
            
            echo "<div class='success'>
                ✅ <strong>Email enviado exitosamente a $email</strong><br>
                Ve a <a href='https://mailtrap.io' target='_blank'>https://mailtrap.io</a> para ver el email en tu Inbox.
            </div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>
                ❌ <strong>Error enviando email:</strong><br>
                " . htmlspecialchars($mail->ErrorInfo) . "
            </div>";
            
            // Log del error
            error_log('[EMAIL ERROR] ' . $mail->ErrorInfo);
        }
    }
}
?>

<form method="POST">
    <div class="form-group">
        <label for="nombre">👤 Nombre:</label>
        <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required>
    </div>
    
    <div class="form-group">
        <label for="email">📧 Email:</label>
        <input type="email" id="email" name="email" placeholder="tu_email@mailtrap.io o tu_email@gmail.com" required>
    </div>
    
    <button type="submit">📤 Enviar Email de Prueba</button>
</form>

<div class="info">
    <strong>💡 Cómo funciona:</strong><br>
    1. Ingresa tu email<br>
    2. Clic "Enviar Email de Prueba"<br>
    3. Ve a <a href="https://mailtrap.io" target="_blank">Mailtrap</a> para ver el email<br>
    4. Si llega, ¡todo funciona correctamente!
</div>

</div>
