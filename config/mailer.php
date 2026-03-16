<?php
// config/mailer.php
// PHPMailer - simple SMTP configuration using provided Gmail credentials

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Cargar PHPMailer solo vía Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

$MAILER_CONFIG = [
    'mode' => 'smtp',
    'smtp' => [
        // Gmail SMTP — usar App Password (no contraseña normal)
        // Generar en: https://myaccount.google.com/apppasswords
        'host'     => 'smtp.gmail.com',
        'username' => 'baezjhail@gmail.com',
        'password' => 'gzghfibxuryaebuj',
    'port'     => 465,
    'secure'   => 'ssl',
    'timeout'  => 30,
    'ehlo'     => 'prermi.duckdns.org',
    ],
];

function getMailer($isHTML = true, $fromEmail = 'baezjhail@gmail.com', $fromName = 'PRERMI Sistema') {
    global $MAILER_CONFIG;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
      $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->CharSet   = 'UTF-8';
      $mail->Encoding  = 'base64';
      $mail->Timeout   = $MAILER_CONFIG['smtp']['timeout'] ?? 30;
      $mail->SMTPKeepAlive = false;
      $mail->SMTPAuth  = true;
      $mail->SMTPAutoTLS = true;
      $mail->XMailer = 'PRERMI Mailer';
        $mail->setFrom($fromEmail, $fromName);
        $mail->isHTML($isHTML);

        $smtp = $MAILER_CONFIG['smtp'];
        $mail->Host       = $smtp['host'];
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->Port       = $smtp['port'];
      $mail->Hostname   = $smtp['ehlo'] ?? 'prermi.duckdns.org';
      $mail->Helo       = $smtp['ehlo'] ?? 'prermi.duckdns.org';

      if (($smtp['secure'] ?? '') === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      } elseif (($smtp['secure'] ?? '') === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      }

      $mail->SMTPOptions = [
        'ssl' => [
          'verify_peer' => false,
          'verify_peer_name' => false,
          'allow_self_signed' => true,
        ],
      ];

        return $mail;
    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/email_errors.log', "[".date('c')."] MailerInitError: ".$e->getMessage()."\n", FILE_APPEND);
        throw $e;
    }
}

function sendUserDepositEmail($to, $name, $peso, $credito_kwh) {
    // Delegar a la funcion con formato de factura completa
    return sendDepositNotificationEmail($to, $name, $peso, $credito_kwh, date('Y-m-d H:i:s'), '');
}

// =====================================================================
// HELPER: genera el layout base de todos los emails PRERMI
// =====================================================================
function prMailLayout(string $headerGrad, string $accentColor, string $icon, string $title, string $badgeHtml, string $bodyHtml): string {
    $year = date('Y');
    return '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0a0f1e;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0f1e;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.6);">

  <!-- ═══ HEADER ═══ -->
  <tr><td style="background:' . $headerGrad . ';padding:40px 32px 32px;text-align:center;">
    <div style="display:inline-flex;align-items:center;justify-content:center;
                width:72px;height:72px;border-radius:18px;
                background:rgba(255,255,255,.15);backdrop-filter:blur(8px);
                font-size:36px;margin-bottom:16px;">' . $icon . '</div>
    <h1 style="color:#fff;margin:0;font-size:30px;font-weight:900;letter-spacing:2px;
               text-shadow:0 2px 12px rgba(0,0,0,.35);">PRERMI</h1>
    <p style="color:rgba(255,255,255,.8);margin:6px 0 0;font-size:12px;letter-spacing:3px;text-transform:uppercase;">
      Programa de Reducción de Residuos y Manejo Inteligente
    </p>
  </td></tr>

  <!-- ═══ TITLE STRIP ═══ -->
  <tr><td style="background:#0f172a;padding:16px 32px;
                 border-left:4px solid ' . $accentColor . ';
                 border-right:4px solid transparent;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
      <td style="color:#f1f5f9;font-size:16px;font-weight:700;">' . htmlspecialchars($title) . '</td>
      <td style="text-align:right;">' . $badgeHtml . '</td>
    </tr></table>
  </td></tr>

  <!-- ═══ BODY ═══ -->
  <tr><td style="background:#1e293b;padding:36px 32px;">' . $bodyHtml . '</td></tr>

  <!-- ═══ FOOTER ═══ -->
  <tr><td style="background:#0f172a;padding:24px 32px;text-align:center;border-top:1px solid rgba(255,255,255,.06);">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
      <td align="center">
        <p style="margin:0 0 6px;font-size:13px;">
          <span style="color:' . $accentColor . ';font-weight:800;">PRERMI</span>
          &nbsp;·&nbsp;<span style="color:#475569;">Instituto Tecnológico de México · Santiago, R.D.</span>
        </p>
        <p style="margin:0;color:#334155;font-size:11px;">© ' . $year . ' PRERMI — Todos los derechos reservados</p>
      </td>
    </tr></table>
  </td></tr>

</table>
</td></tr>
</table>
</body></html>';
}

// ─────────────────────────────────────────────────────────────────────────────
// Alerta de metal / multa → admins
// ─────────────────────────────────────────────────────────────────────────────
function sendAdminFineEmail($admins, $userEmail, $userName, $user_id, $contenedor_id, $peso) {
    try {
        $mail = getMailer(true);
        foreach ($admins as $a) {
            if (!empty($a['email'])) $mail->addAddress($a['email'], $a['name'] ?? '');
        }
        $mail->Subject = '🚨 ALERTA: Metal detectado en depósito — PRERMI';

        $badge   = '<span style="background:rgba(239,68,68,.25);color:#fca5a5;border:1px solid rgba(239,68,68,.4);
                                 padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">ALERTA</span>';

        $bodyHtml = '
          <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;letter-spacing:1px;text-transform:uppercase;">Notificación automática del sistema</p>
          <h2 style="color:#f1f5f9;font-size:20px;margin:0 0 24px;font-weight:700;">Se detectó metal en un depósito de biomasa</h2>

          <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;border-radius:12px;overflow:hidden;">
            <tr style="background:#0f172a;">
              <td style="padding:12px 16px;color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;width:45%;">Campo</td>
              <td style="padding:12px 16px;color:#64748b;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Valor</td>
            </tr>
            <tr style="border-bottom:1px solid #0f172a;">
              <td style="padding:14px 16px;background:#162032;color:#94a3b8;font-size:14px;">Usuario</td>
              <td style="padding:14px 16px;background:#162032;color:#f1f5f9;font-size:14px;font-weight:600;">' . htmlspecialchars($userName) . ' &lt;' . htmlspecialchars($userEmail) . '&gt;</td>
            </tr>
            <tr style="border-bottom:1px solid #0f172a;">
              <td style="padding:14px 16px;background:#1a2540;color:#94a3b8;font-size:14px;">Contenedor</td>
              <td style="padding:14px 16px;background:#1a2540;color:#f1f5f9;font-size:14px;font-weight:600;">' . htmlspecialchars($contenedor_id) . '</td>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#162032;color:#94a3b8;font-size:14px;">Peso detectado</td>
              <td style="padding:14px 16px;background:#162032;color:#fca5a5;font-size:14px;font-weight:700;">' . htmlspecialchars($peso) . ' kg</td>
            </tr>
          </table>

          <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:16px 20px;">
            <p style="margin:0;color:#fca5a5;font-size:13px;line-height:1.6;">
              <strong>⚠ Acción registrada:</strong> Se ha aplicado automáticamente una multa al usuario. 
              Revisa el panel de administración de PRERMI para más detalles y seguimiento.
            </p>
          </div>';

        $mail->Body    = prMailLayout(
            'linear-gradient(135deg,#ef4444 0%,#dc2626 50%,#7c3aed 100%)',
            '#f87171', '🚨', 'Alerta de Metal Detectado', $badge, $bodyHtml
        );
        $mail->AltBody = "ALERTA PRERMI: Metal detectado\nUsuario: $userName <$userEmail>\nContenedor: $contenedor_id\nPeso: $peso kg\nSe ha registrado una multa.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/email_errors.log', "[".date('c')."] AdminMailError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Correo de verificación de cuenta (usuario o admin)
// ─────────────────────────────────────────────────────────────────────────────
function sendRegistrationConfirmationEmail($to, $name, $verification_link, $userType = 'usuario') {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);

        $isAdmin     = ($userType === 'admin');
        $headerGrad  = $isAdmin
            ? 'linear-gradient(135deg,#7c3aed 0%,#4f46e5 50%,#06b6d4 100%)'
            : 'linear-gradient(135deg,#06b6d4 0%,#10b981 50%,#7c3aed 100%)';
        $accentColor = $isAdmin ? '#a78bfa' : '#22d3ee';
        $icon        = $isAdmin ? '🛡️' : '🌱';
        $typeLabel   = $isAdmin ? 'Administrador' : 'Usuario';
        $badge       = '<span style="background:rgba(6,182,212,.2);color:#22d3ee;border:1px solid rgba(6,182,212,.4);
                                     padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">VERIFICACIÓN</span>';
        $extraNote   = $isAdmin
            ? '<p style="color:#94a3b8;font-size:13px;line-height:1.6;margin-top:16px;">Una vez confirmado tu email, un <strong style="color:#a78bfa;">Superadministrador</strong> deberá aprobar tu acceso antes de que puedas iniciar sesión.</p>'
            : '';

        $mail->Subject = '✅ Confirma tu correo — ' . ($isAdmin ? 'Registro de Administrador' : 'Registro') . ' PRERMI';

        $bodyHtml = '
          <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;letter-spacing:1px;text-transform:uppercase;">Hola,</p>
          <h2 style="color:#f1f5f9;font-size:22px;margin:0 0 6px;font-weight:700;">' . htmlspecialchars($name) . '</h2>
          <p style="color:#64748b;font-size:13px;margin:0 0 24px;">Registro como <strong style="color:' . $accentColor . ';">' . htmlspecialchars($typeLabel) . '</strong></p>

          <p style="color:#cbd5e1;font-size:15px;line-height:1.75;margin:0 0 28px;">
            Gracias por unirte a <strong style="color:#f1f5f9;">PRERMI</strong>, el sistema inteligente de gestión de residuos y generación de energía renovable del Instituto Tecnológico de México.<br><br>
            Para activar tu cuenta, confirma tu dirección de correo electrónico pulsando el botón:
          </p>

          <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:8px 0 28px;">
            <a href="' . htmlspecialchars($verification_link) . '"
               style="display:inline-block;
                      background:' . $headerGrad . ';
                      color:#fff;text-decoration:none;
                      padding:16px 52px;border-radius:50px;
                      font-size:16px;font-weight:800;letter-spacing:.5px;
                      box-shadow:0 6px 28px rgba(6,182,212,.45);">
              ✔&nbsp; Confirmar mi correo
            </a>
          </td></tr></table>

          ' . $extraNote . '

          <div style="border-top:1px solid #334155;margin:28px 0;"></div>
          <p style="color:#64748b;font-size:13px;margin:0 0 10px;">Si el botón no funciona, copia este enlace en tu navegador:</p>
          <div style="background:#0f172a;border:1px solid #334155;border-radius:10px;padding:14px 18px;word-break:break-all;">
            <code style="color:' . $accentColor . ';font-size:12px;">' . htmlspecialchars($verification_link) . '</code>
          </div>
          <p style="color:#334155;font-size:12px;margin-top:20px;line-height:1.5;">
            Si no realizaste este registro, puedes ignorar este mensaje de forma segura.
          </p>';

        $mail->Body    = prMailLayout($headerGrad, $accentColor, $icon, 'Verificación de Cuenta', $badge, $bodyHtml);
        $mail->AltBody = "Hola $name,\n\nGracias por registrarte en PRERMI.\nConfirma tu email: $verification_link\n\nEquipo PRERMI";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/email_errors.log', "[".date('c')."] RegistrationConfirmationError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Correo de bienvenida tras verificación/aprobación
// $userType: 'usuario' | 'admin_pending' | 'admin_approved'
// ─────────────────────────────────────────────────────────────────────────────
function sendWelcomeEmail($to, $name, $userType = 'usuario') {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);

        if ($userType === 'admin_pending') {
            // Admin verificó email, espera a superadmin
            $mail->Subject = '📩 Tu email fue verificado — Pendiente de aprobación · PRERMI';
            $headerGrad  = 'linear-gradient(135deg,#7c3aed 0%,#4f46e5 50%,#06b6d4 100%)';
            $accentColor = '#a78bfa';
            $icon        = '⏳';
            $badge       = '<span style="background:rgba(251,191,36,.2);color:#fbbf24;border:1px solid rgba(251,191,36,.4);
                                         padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">PENDIENTE</span>';
            $bodyHtml    = '
              <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">¡Excelente!</p>
              <h2 style="color:#f1f5f9;font-size:22px;margin:0 0 20px;font-weight:700;">Tu correo fue verificado, ' . htmlspecialchars($name) . '</h2>
              <p style="color:#cbd5e1;font-size:15px;line-height:1.75;margin:0 0 24px;">
                Email confirmado correctamente. Tu solicitud de acceso como <strong style="color:#a78bfa;">Administrador</strong> está ahora en proceso de revisión.<br><br>
                Un <strong style="color:#f1f5f9;">Superadministrador</strong> de PRERMI revisará y aprobará tu cuenta en breve. Recibirás otro correo cuando tu acceso sea activado.
              </p>
              <div style="background:rgba(124,58,237,.1);border:1px solid rgba(124,58,237,.3);border-radius:12px;padding:20px 24px;">
                <p style="margin:0 0 10px;color:#c4b5fd;font-size:14px;font-weight:700;">¿Qué sigue?</p>
                <ul style="margin:0;padding-left:20px;color:#94a3b8;font-size:14px;line-height:2;">
                  <li>El superadministrador revisará tu solicitud</li>
                  <li>Recibirás un correo de activación al ser aprobado</li>
                  <li>Tendrás acceso completo al panel de administración</li>
                </ul>
              </div>';
        } elseif ($userType === 'admin_approved') {
            // Admin fue aprobado por superadmin
            $mail->Subject = '🎉 ¡Tu cuenta fue activada! Bienvenido al equipo de Administración · PRERMI';
            $headerGrad  = 'linear-gradient(135deg,#7c3aed 0%,#06b6d4 50%,#10b981 100%)';
            $accentColor = '#22d3ee';
            $icon        = '🛡️';
            $badge       = '<span style="background:rgba(16,185,129,.2);color:#34d399;border:1px solid rgba(16,185,129,.4);
                                         padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">ACTIVADO</span>';
            $bodyHtml    = '
              <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">¡Bienvenido/a al equipo!</p>
              <h2 style="color:#f1f5f9;font-size:22px;margin:0 0 20px;font-weight:700;">Tu cuenta de Administrador está activa, ' . htmlspecialchars($name) . '</h2>
              <p style="color:#cbd5e1;font-size:15px;line-height:1.75;margin:0 0 24px;">
                Un superadministrador ha <strong style="color:#34d399;">aprobado y activado</strong> tu acceso a PRERMI. Ya puedes iniciar sesión en el panel de administración.
              </p>

              <div style="background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.2);border-radius:12px;padding:20px 24px;margin-bottom:24px;">
                <p style="margin:0 0 14px;color:#22d3ee;font-size:14px;font-weight:700;">
                  🔑 &nbsp;Tus capacidades como Administrador:
                </p>
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="padding:6px 0;color:#94a3b8;font-size:13px;" width="50%">📦 Gestionar contenedores</td>
                    <td style="padding:6px 0;color:#94a3b8;font-size:13px;">👥 Administrar usuarios</td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;color:#94a3b8;font-size:13px;">⚡ Monitorear energía</td>
                    <td style="padding:6px 0;color:#94a3b8;font-size:13px;">🚗 Control vehicular</td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;color:#94a3b8;font-size:13px;">⚠️ Gestionar sanciones</td>
                    <td style="padding:6px 0;color:#94a3b8;font-size:13px;">📊 Ver reportes del sistema</td>
                  </tr>
                </table>
              </div>

              <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:8px 0 8px;">
                <a href="https://prermi.duckdns.org/PRERMI/web/admin/dashboard.php"
                   style="display:inline-block;
                          background:linear-gradient(135deg,#7c3aed,#06b6d4);
                          color:#fff;text-decoration:none;
                          padding:14px 44px;border-radius:50px;
                          font-size:15px;font-weight:800;letter-spacing:.5px;
                          box-shadow:0 6px 28px rgba(124,58,237,.45);">
                  🛡️ &nbsp;Ir al Panel de Administración
                </a>
              </td></tr></table>';
        } else {
            // Usuario regular verificó su cuenta
            $mail->Subject = '🌿 ¡Bienvenido/a a PRERMI! Tu cuenta está lista';
            $headerGrad  = 'linear-gradient(135deg,#06b6d4 0%,#10b981 50%,#7c3aed 100%)';
            $accentColor = '#22d3ee';
            $icon        = '🌱';
            $badge       = '<span style="background:rgba(16,185,129,.2);color:#34d399;border:1px solid rgba(16,185,129,.4);
                                         padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">ACTIVO</span>';
            $bodyHtml    = '
              <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">¡Cuenta verificada!</p>
              <h2 style="color:#f1f5f9;font-size:22px;margin:0 0 20px;font-weight:700;">Bienvenido/a, ' . htmlspecialchars($name) . ' 🎉</h2>
              <p style="color:#cbd5e1;font-size:15px;line-height:1.75;margin:0 0 24px;">
                Tu cuenta en <strong style="color:#22d3ee;">PRERMI</strong> está lista. ¡Ya puedes empezar a reciclar, ganar créditos energéticos y contribuir al medio ambiente!
              </p>

              <div style="background:rgba(6,182,212,.06);border:1px solid rgba(6,182,212,.2);border-radius:12px;padding:20px 24px;margin-bottom:24px;">
                <p style="margin:0 0 14px;color:#22d3ee;font-size:14px;font-weight:700;">✨ &nbsp;¿Qué puedes hacer en PRERMI?</p>
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;width:50%;">
                      <div style="color:#34d399;font-size:18px;margin-bottom:4px;">♻️</div>
                      <div style="color:#f1f5f9;font-size:13px;font-weight:600;">Depositar biomasa</div>
                      <div style="color:#64748b;font-size:12px;margin-top:2px;">Deposita materiales reciclables en los contenedores PRERMI.</div>
                    </td>
                    <td style="padding:8px 0 8px 16px;vertical-align:top;">
                      <div style="color:#22d3ee;font-size:18px;margin-bottom:4px;">⚡</div>
                      <div style="color:#f1f5f9;font-size:13px;font-weight:600;">Ganar créditos kWh</div>
                      <div style="color:#64748b;font-size:12px;margin-top:2px;">Cada depósito te genera créditos de energía eléctrica.</div>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:8px 0;vertical-align:top;width:50%;">
                      <div style="color:#a78bfa;font-size:18px;margin-bottom:4px;">💰</div>
                      <div style="color:#f1f5f9;font-size:13px;font-weight:600;">Ahorro en pesos dominicanos</div>
                      <div style="color:#64748b;font-size:12px;margin-top:2px;">Ve cuánto reduces en tu factura eléctrica con la energía generada.</div>
                    </td>
                    <td style="padding:8px 0 8px 16px;vertical-align:top;">
                      <div style="color:#fbbf24;font-size:18px;margin-bottom:4px;">📊</div>
                      <div style="color:#f1f5f9;font-size:13px;font-weight:600;">Historial y estadísticas</div>
                      <div style="color:#64748b;font-size:12px;margin-top:2px;">Consulta el historial de todos tus depósitos y créditos acumulados.</div>
                    </td>
                  </tr>
                </table>
              </div>

              <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:8px 0 8px;">
                <a href="https://prermi.duckdns.org/PRERMI/web/index.php"
                   style="display:inline-block;
                          background:linear-gradient(135deg,#06b6d4,#10b981);
                          color:#fff;text-decoration:none;
                          padding:14px 48px;border-radius:50px;
                          font-size:15px;font-weight:800;letter-spacing:.5px;
                          box-shadow:0 6px 28px rgba(6,182,212,.45);">
                  🌱 &nbsp;Ingresar a PRERMI
                </a>
              </td></tr></table>

              <div style="border-top:1px solid #334155;margin:28px 0 0;"></div>
              <p style="color:#334155;font-size:12px;margin-top:16px;line-height:1.6;text-align:center;">
                PRERMI · Instituto Tecnológico de México · Santiago, República Dominicana<br>
                Para soporte escríbenos a <a href="mailto:baezjhail@gmail.com" style="color:#22d3ee;">baezjhail@gmail.com</a>
              </p>';
        }

        $mail->Body    = prMailLayout($headerGrad, $accentColor, $icon,
            $userType === 'admin_pending' ? 'Email Verificado — Acceso Pendiente' :
            ($userType === 'admin_approved' ? 'Cuenta de Administrador Activada' : 'Bienvenido/a a PRERMI'),
            $badge, $bodyHtml);
        $mail->AltBody = "Hola $name,\n\nBienvenido/a a PRERMI.\nVisita la plataforma en: https://prermi.duckdns.org/PRERMI/web/\n\nEquipo PRERMI";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/email_errors.log', "[".date('c')."] WelcomeEmailError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Factura de depósito al usuario
// ─────────────────────────────────────────────────────────────────────────────
function sendDepositNotificationEmail($to, $name, $peso, $credito_kwh, $deposit_date, $transaction_id = '') {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);
        $mail->Subject = '♻️ Factura de Depósito #' . str_pad($transaction_id, 6, '0', STR_PAD_LEFT) . ' — PRERMI';

        $fechaFmt   = date('d/m/Y H:i:s', strtotime($deposit_date));
        $numFactura = str_pad($transaction_id, 6, '0', STR_PAD_LEFT);
        $tarifaRD   = 11.50;
        $ahorroRD   = number_format($credito_kwh * $tarifaRD, 2);

        $badge    = '<span style="background:rgba(16,185,129,.2);color:#34d399;border:1px solid rgba(16,185,129,.4);
                                  padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">#' . $numFactura . '</span>';

        $bodyHtml = '
          <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">Comprobante de reciclaje</p>
          <h2 style="color:#f1f5f9;font-size:22px;margin:0 0 6px;font-weight:700;">¡Gracias por reciclar, ' . htmlspecialchars($name) . '!</h2>
          <p style="color:#64748b;font-size:13px;margin:0 0 24px;">' . htmlspecialchars($fechaFmt) . '</p>

          <!-- Tabla de detalle -->
          <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:12px;overflow:hidden;margin-bottom:20px;">
            <tr style="background:#0f172a;">
              <th style="padding:12px 16px;color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;text-align:left;letter-spacing:.5px;">Concepto</th>
              <th style="padding:12px 16px;color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;text-align:right;letter-spacing:.5px;">Valor</th>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#162032;color:#94a3b8;font-size:14px;border-bottom:1px solid #0f172a;">Material depositado</td>
              <td style="padding:14px 16px;background:#162032;color:#f1f5f9;font-size:14px;font-weight:700;text-align:right;border-bottom:1px solid #0f172a;">' . htmlspecialchars(number_format($peso, 2)) . ' kg</td>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#1a2540;color:#94a3b8;font-size:14px;border-bottom:1px solid #0f172a;">Crédito energético generado</td>
              <td style="padding:14px 16px;background:#1a2540;color:#22d3ee;font-size:14px;font-weight:700;text-align:right;border-bottom:1px solid #0f172a;">' . htmlspecialchars(number_format($credito_kwh, 4)) . ' kWh</td>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#162032;color:#94a3b8;font-size:14px;">Ahorro estimado en factura</td>
              <td style="padding:14px 16px;background:#162032;color:#34d399;font-size:14px;font-weight:700;text-align:right;">RD$ ' . $ahorroRD . '</td>
            </tr>
          </table>

          <!-- Total highlight -->
          <div style="background:linear-gradient(135deg,rgba(6,182,212,.15),rgba(16,185,129,.1));
                      border:1px solid rgba(6,182,212,.3);border-radius:12px;
                      padding:18px 24px;display:flex;margin-bottom:24px;">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
              <td style="color:#94a3b8;font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Crédito Total Acumulado (este depósito)</td>
              <td style="text-align:right;color:#22d3ee;font-size:22px;font-weight:900;">' . htmlspecialchars(number_format($credito_kwh, 4)) . ' kWh</td>
            </tr></table>
          </div>

          <p style="color:#64748b;font-size:12px;line-height:1.6;margin:0;">
            💡 Tarifa usada para el cálculo del ahorro: <strong style="color:#94a3b8;">RD$' . $tarifaRD . '/kWh</strong> (valor orientativo, tarifa residencial promedio República Dominicana).<br>
            ID Transacción: <code style="color:#22d3ee;">' . htmlspecialchars($transaction_id) . '</code>
          </p>';

        $mail->Body    = prMailLayout(
            'linear-gradient(135deg,#06b6d4 0%,#10b981 50%,#7c3aed 100%)',
            '#22d3ee', '♻️', 'Factura de Depósito de Biomasa', $badge, $bodyHtml
        );
        $mail->AltBody = "Factura PRERMI #$numFactura\nCliente: $name\nFecha: $fechaFmt\nPeso: " . number_format($peso,2) . " kg\nCrédito: " . number_format($credito_kwh,4) . " kWh\nAhorro: RD$$ahorroRD\n\nGracias por reciclar — Equipo PRERMI";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/email_errors.log', "[".date('c')."] DepositNotificationError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// Reporte de sanción al usuario
// ─────────────────────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
// Notificación de baneo al usuario
// ─────────────────────────────────────────────────────────────────────────────
function sendBanEmail($to, $name, $motivo = '') {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);
        $mail->Subject = '🚫 Tu cuenta ha sido suspendida — PRERMI';

        $badge = '<span style="background:rgba(239,68,68,.2);color:#fca5a5;border:1px solid rgba(239,68,68,.4);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">CUENTA SUSPENDIDA</span>';

        $motivoHtml = $motivo
            ? '<div style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:16px 20px;margin-top:20px;">
                 <p style="margin:0;color:#fca5a5;font-size:13px;line-height:1.6;"><strong>Motivo:</strong> ' . htmlspecialchars($motivo) . '</p>
               </div>'
            : '';

        $bodyHtml = '
          <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">Notificación de cuenta</p>
          <h2 style="color:#f1f5f9;font-size:22px;margin:0 0 16px;font-weight:700;">Tu acceso a PRERMI ha sido suspendido</h2>
          <p style="color:#cbd5e1;font-size:15px;line-height:1.7;margin:0 0 8px;">
            Hola <strong style="color:#f1f5f9;">' . htmlspecialchars($name) . '</strong>,
          </p>
          <p style="color:#94a3b8;font-size:14px;line-height:1.7;margin:0 0 20px;">
            Un administrador ha suspendido tu cuenta en la plataforma PRERMI. Mientras tu cuenta esté suspendida
            <strong style="color:#f1f5f9;">no podrás iniciar sesión</strong> ni acceder a ningún servicio.
          </p>
          ' . $motivoHtml . '
          <div style="border-top:1px solid #334155;margin:24px 0;"></div>
          <p style="color:#64748b;font-size:13px;line-height:1.6;">
            Si consideras que esto es un error, comunícate con el equipo de administración de PRERMI para solicitar una revisión de tu caso.
          </p>';

        $mail->Body    = prMailLayout(
            'linear-gradient(135deg,#ef4444 0%,#b91c1c 50%,#7c3aed 100%)',
            '#f87171', '🚫', 'Cuenta Suspendida', $badge, $bodyHtml
        );
        $mail->AltBody = "Hola $name,\n\nTu cuenta en PRERMI ha sido suspendida y no podrás iniciar sesión." .
                         ($motivo ? "\nMotivo: $motivo" : '') .
                         "\n\nContacta al administrador si crees que es un error.\n\nEquipo PRERMI";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/email_errors.log', "[".date('c')."] BanEmailError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

function sendSanctionReportEmail($to, $name, $sancion_id, $descripcion, $peso, $contenedor_info, $fecha) {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);
        $mail->Subject = '⚠️ Reporte de Sanción #' . str_pad($sancion_id, 6, '0', STR_PAD_LEFT) . ' — PRERMI';

        $fechaFmt  = date('d/m/Y H:i:s', strtotime($fecha));
        $numRep    = str_pad($sancion_id, 6, '0', STR_PAD_LEFT);
        $pesoStr   = ($peso !== null && $peso > 0) ? number_format($peso, 3) . ' kg' : 'N/A';

        $badge    = '<span style="background:rgba(239,68,68,.2);color:#fca5a5;border:1px solid rgba(239,68,68,.4);
                                  padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;">SANCIÓN #' . $numRep . '</span>';

        $bodyHtml = '
          <p style="color:#94a3b8;font-size:13px;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">Notificación oficial del sistema</p>
          <h2 style="color:#f1f5f9;font-size:22px;margin:0 0 6px;font-weight:700;">Se ha registrado una sanción en tu cuenta</h2>
          <p style="color:#64748b;font-size:13px;margin:0 0 24px;">' . htmlspecialchars($name) . ' &nbsp;·&nbsp; ' . $fechaFmt . '</p>

          <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:12px;overflow:hidden;margin-bottom:20px;">
            <tr style="background:#0f172a;">
              <th style="padding:12px 16px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.5px;text-align:left;">Detalle</th>
              <th style="padding:12px 16px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.5px;text-align:right;">Información</th>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#162032;color:#94a3b8;font-size:14px;border-bottom:1px solid #0f172a;">Motivo</td>
              <td style="padding:14px 16px;background:#162032;color:#f1f5f9;font-size:14px;font-weight:600;text-align:right;border-bottom:1px solid #0f172a;">' . htmlspecialchars($descripcion) . '</td>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#1a2540;color:#94a3b8;font-size:14px;border-bottom:1px solid #0f172a;">Peso del material</td>
              <td style="padding:14px 16px;background:#1a2540;color:#fca5a5;font-size:14px;font-weight:700;text-align:right;border-bottom:1px solid #0f172a;">' . htmlspecialchars($pesoStr) . '</td>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#162032;color:#94a3b8;font-size:14px;border-bottom:1px solid #0f172a;">Contenedor</td>
              <td style="padding:14px 16px;background:#162032;color:#f1f5f9;font-size:14px;text-align:right;border-bottom:1px solid #0f172a;">' . htmlspecialchars($contenedor_info) . '</td>
            </tr>
            <tr>
              <td style="padding:14px 16px;background:#1a2540;color:#94a3b8;font-size:14px;">ID Sanción</td>
              <td style="padding:14px 16px;background:#1a2540;color:#f87171;font-size:14px;font-weight:700;text-align:right;">#' . $numRep . '</td>
            </tr>
          </table>

          <div style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:16px 20px;">
            <p style="margin:0;color:#fca5a5;font-size:13px;line-height:1.6;">
              <strong>Nota importante:</strong> Esta sanción ha sido registrada en tu historial. Si consideras que se trata de un error, contacta a la administración de PRERMI para solicitar revisión del caso.
            </p>
          </div>';

        $mail->Body    = prMailLayout(
            'linear-gradient(135deg,#ef4444 0%,#dc2626 50%,#7c3aed 100%)',
            '#f87171', '⚠️', 'Reporte de Sanción', $badge, $bodyHtml
        );
        $mail->AltBody = "Reporte de Sanción PRERMI #$numRep\nUsuario: $name\nFecha: $fechaFmt\nMotivo: $descripcion\nPeso: $pesoStr\nContenedor: $contenedor_info";
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/email_errors.log', "[".date('c')."] SanctionReportError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

