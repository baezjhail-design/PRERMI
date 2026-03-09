<?php
// config/mailer.php
// PHPMailer - simple SMTP configuration using provided Gmail credentials

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Prefer Composer autoload if present
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
}

$MAILER_CONFIG = [
    'mode' => 'smtp',
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'username' => 'baezjhail@gmail.com',
        'password' => 'gzghfibxuryaebuj', // App password or SMTP password provided
        'port' => 587,
        'secure' => PHPMailer::ENCRYPTION_STARTTLS,
    ],
];

function getMailer($isHTML = true, $fromEmail = 'noreply@prermi.local', $fromName = 'PRERMI Sistema') {
    global $MAILER_CONFIG;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 0; // set 2 for verbose debug
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromEmail, $fromName);
        $mail->isHTML($isHTML);

        $smtp = $MAILER_CONFIG['smtp'];
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];
        $mail->SMTPSecure = $smtp['secure'];
        $mail->Port = $smtp['port'];

        // Avoid cert issues on local dev
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        return $mail;
    } catch (Exception $e) {
        if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
        file_put_contents(__DIR__ . '/../logs/email_errors.log', "[".date('c')."] MailerInitError: ".$e->getMessage()."\n", FILE_APPEND);
        throw $e;
    }
}

function sendUserDepositEmail($to, $name, $peso, $credito_kwh) {
    // Delegar a la funcion con formato de factura completa
    return sendDepositNotificationEmail($to, $name, $peso, $credito_kwh, date('Y-m-d H:i:s'), '');
}

function sendAdminFineEmail($admins, $userEmail, $userName, $user_id, $contenedor_id, $peso) {
    try {
        $mail = getMailer(true);
        foreach ($admins as $a) {
            if (!empty($a['email'])) $mail->addAddress($a['email'], $a['name'] ?? '');
        }
        $mail->Subject = 'ALERTA: Multa por metal - PRERMI';
        $html = "<p><strong>Alerta de multa</strong></p>";
        $html .= "<p>Usuario: " . htmlspecialchars($userName) . " &lt;" . htmlspecialchars($userEmail) . "&gt;</p>";
        $html .= "<p>Contenedor: " . htmlspecialchars($contenedor_id) . "<br>Peso: " . htmlspecialchars($peso) . " kg</p>";
        $html .= "<p>Acción: multa registrada.</p>";
        $mail->Body = $html;
        $mail->AltBody = "Usuario: $userName <$userEmail>\nContenedor: $contenedor_id\nPeso: $peso kg\nAcción: multa registrada.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
        file_put_contents(__DIR__ . '/../logs/email_errors.log', "[".date('c')."] AdminMailError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

function sendRegistrationConfirmationEmail($to, $name, $verification_link, $userType = 'usuario') {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);
        
        if ($userType === 'admin') {
            $mail->Subject = 'Confirma tu email - Registro de Administrador PRERMI';
            $typeLabel = 'Administrador';
        } else {
            $mail->Subject = 'Confirma tu email - Registro PRERMI';
            $typeLabel = 'Usuario';
        }
        
        $html = "<p>Hola <strong>".htmlspecialchars($name)."</strong>,</p>";
        $html .= "<p>Gracias por registrarte como $typeLabel en PRERMI. Para completar tu registro, confirma tu correo haciendo clic en el siguiente enlace:</p>";
        $html .= "<p><a href=\"".htmlspecialchars($verification_link)."\" style=\"background-color:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;\">Confirmar Email</a></p>";
        $html .= "<p>O copia y pega este enlace en tu navegador:<br><code>".htmlspecialchars($verification_link)."</code></p>";
        $html .= "<p>Si no realizaste este registro, ignora este email.</p>";
        $html .= "<p>Gracias,<br>Equipo PRERMI</p>";
        
        $mail->Body = $html;
        $mail->AltBody = "Hola $name,\n\nGracias por registrarte como $typeLabel en PRERMI. Confirma tu email en: ".$verification_link."\n\nGracias,\nEquipo PRERMI";
        $mail->send();
        return true;
    } catch (Exception $e) {
        if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
        file_put_contents(__DIR__ . '/../logs/email_errors.log', "[".date('c')."] RegistrationConfirmationError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

function sendDepositNotificationEmail($to, $name, $peso, $credito_kwh, $deposit_date, $transaction_id = '') {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);
        $mail->Subject = 'Factura de Deposito #' . $transaction_id . ' - PRERMI';

        $fechaFormateada = date('d/m/Y H:i:s', strtotime($deposit_date));
        $numFactura = str_pad($transaction_id, 6, '0', STR_PAD_LEFT);

        $html = '
        <div style="font-family: \'Segoe UI\', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                <h1 style="margin: 0; font-size: 28px; letter-spacing: 1px;">PRERMI</h1>
                <p style="margin: 5px 0 0; opacity: 0.9; font-size: 14px;">Programa de Reduccion de Residuos y Manejo Inteligente</p>
            </div>

            <!-- Invoice Title -->
            <div style="background: white; padding: 25px 30px; border-bottom: 2px solid #667eea;">
                <table style="width: 100%;">
                    <tr>
                        <td>
                            <h2 style="color: #333; margin: 0; font-size: 22px;">FACTURA DE DEPOSITO</h2>
                            <p style="color: #888; margin: 5px 0 0; font-size: 13px;">Comprobante de reciclaje registrado</p>
                        </td>
                        <td style="text-align: right;">
                            <div style="background: #667eea; color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; font-weight: 600; font-size: 14px;">
                                #' . htmlspecialchars($numFactura) . '
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Client & Date Info -->
            <div style="background: white; padding: 20px 30px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top;">
                            <strong style="color: #667eea; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Cliente</strong><br>
                            <span style="color: #333; font-size: 15px;">' . htmlspecialchars($name) . '</span><br>
                            <span style="color: #888; font-size: 13px;">' . htmlspecialchars($to) . '</span>
                        </td>
                        <td style="padding: 8px 0; text-align: right; vertical-align: top;">
                            <strong style="color: #667eea; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Fecha</strong><br>
                            <span style="color: #333; font-size: 15px;">' . htmlspecialchars($fechaFormateada) . '</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Details Table -->
            <div style="background: white; padding: 0 30px 20px;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <th style="color: white; padding: 12px 15px; text-align: left; font-size: 13px; border-radius: 6px 0 0 0;">Concepto</th>
                            <th style="color: white; padding: 12px 15px; text-align: center; font-size: 13px;">Cantidad</th>
                            <th style="color: white; padding: 12px 15px; text-align: right; font-size: 13px; border-radius: 0 6px 0 0;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; color: #333;">Material reciclado depositado</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: center; color: #333;">1</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: right; font-weight: 600; color: #333;">' . htmlspecialchars(number_format($peso, 2)) . ' kg</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; color: #333;">Credito energetico generado</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: center; color: #333;">-</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: right; font-weight: 600; color: #28a745;">' . htmlspecialchars(number_format($credito_kwh, 4)) . ' kWh</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Total Box -->
            <div style="background: #f0f2ff; padding: 20px 30px; margin: 0;">
                <table style="width: 100%;">
                    <tr>
                        <td style="color: #667eea; font-weight: 600; font-size: 16px;">CREDITO TOTAL</td>
                        <td style="text-align: right; color: #28a745; font-weight: 700; font-size: 22px;">' . htmlspecialchars(number_format($credito_kwh, 4)) . ' kWh</td>
                    </tr>
                </table>
            </div>

            <!-- Footer -->
            <div style="background: #333; color: #aaa; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px;">
                <p style="margin: 0 0 5px;">Este comprobante fue generado automaticamente por el sistema PRERMI.</p>
                <p style="margin: 0 0 5px;">ID Transaccion: <strong style="color: #ccc;">' . htmlspecialchars($transaction_id) . '</strong></p>
                <p style="margin: 0; color: #888;">Gracias por reciclar y contribuir al medio ambiente.</p>
            </div>
        </div>';

        $mail->Body = $html;
        $altBody = "FACTURA DE DEPOSITO - PRERMI\n";
        $altBody .= "==============================\n";
        $altBody .= "Factura #$numFactura\n";
        $altBody .= "Cliente: $name\n";
        $altBody .= "Fecha: $fechaFormateada\n\n";
        $altBody .= "- Material depositado: " . number_format($peso, 2) . " kg\n";
        $altBody .= "- Credito generado: " . number_format($credito_kwh, 4) . " kWh\n";
        $altBody .= "- ID Transaccion: $transaction_id\n\n";
        $altBody .= "Gracias por reciclar.\nEquipo PRERMI";
        $mail->AltBody = $altBody;
        $mail->send();
        return true;
    } catch (Exception $e) {
        if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
        file_put_contents(__DIR__ . '/../logs/email_errors.log', "[".date('c')."] DepositNotificationError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}

/**
 * Enviar reporte de sancion al usuario
 */
function sendSanctionReportEmail($to, $name, $sancion_id, $descripcion, $peso, $contenedor_info, $fecha) {
    try {
        $mail = getMailer(true);
        $mail->addAddress($to, $name);
        $mail->Subject = 'Reporte de Sancion #' . $sancion_id . ' - PRERMI';

        $fechaFormateada = date('d/m/Y H:i:s', strtotime($fecha));
        $numReporte = str_pad($sancion_id, 6, '0', STR_PAD_LEFT);
        $pesoStr = ($peso !== null && $peso > 0) ? number_format($peso, 3) . ' kg' : 'N/A';

        $html = '
        <div style="font-family: \'Segoe UI\', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                <h1 style="margin: 0; font-size: 28px; letter-spacing: 1px;">PRERMI</h1>
                <p style="margin: 5px 0 0; opacity: 0.9; font-size: 14px;">Programa de Reduccion de Residuos y Manejo Inteligente</p>
            </div>

            <!-- Alert Banner -->
            <div style="background: #fff3cd; border-left: 5px solid #e74c3c; padding: 15px 25px; display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 12px;">&#9888;</span>
                <div>
                    <strong style="color: #856404; font-size: 15px;">REPORTE DE SANCION</strong><br>
                    <span style="color: #856404; font-size: 13px;">Se ha registrado una sancion en tu cuenta</span>
                </div>
            </div>

            <!-- Report Title -->
            <div style="background: white; padding: 25px 30px; border-bottom: 1px solid #eee;">
                <table style="width: 100%;">
                    <tr>
                        <td>
                            <h2 style="color: #e74c3c; margin: 0; font-size: 20px;">REPORTE #' . htmlspecialchars($numReporte) . '</h2>
                            <p style="color: #888; margin: 5px 0 0; font-size: 13px;">Notificacion oficial de sancion</p>
                        </td>
                        <td style="text-align: right;">
                            <div style="background: #e74c3c; color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; font-weight: 600; font-size: 14px;">
                                SANCION
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- User & Date Info -->
            <div style="background: white; padding: 20px 30px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; vertical-align: top;">
                            <strong style="color: #e74c3c; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Usuario</strong><br>
                            <span style="color: #333; font-size: 15px;">' . htmlspecialchars($name) . '</span><br>
                            <span style="color: #888; font-size: 13px;">' . htmlspecialchars($to) . '</span>
                        </td>
                        <td style="padding: 8px 0; text-align: right; vertical-align: top;">
                            <strong style="color: #e74c3c; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Fecha del incidente</strong><br>
                            <span style="color: #333; font-size: 15px;">' . htmlspecialchars($fechaFormateada) . '</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Details Table -->
            <div style="background: white; padding: 0 30px 20px;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                            <th style="color: white; padding: 12px 15px; text-align: left; font-size: 13px; border-radius: 6px 0 0 0;">Detalle</th>
                            <th style="color: white; padding: 12px 15px; text-align: right; font-size: 13px; border-radius: 0 6px 0 0;">Informacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; color: #333; font-weight: 600;">Motivo</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: right; color: #333;">' . htmlspecialchars($descripcion) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; color: #333; font-weight: 600;">Peso del material</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: right; color: #333;">' . htmlspecialchars($pesoStr) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; color: #333; font-weight: 600;">Contenedor</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: right; color: #333;">' . htmlspecialchars($contenedor_info) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; color: #333; font-weight: 600;">ID Sancion</td>
                            <td style="padding: 14px 15px; border-bottom: 1px solid #eee; text-align: right; color: #667eea; font-weight: 600;">#' . htmlspecialchars($numReporte) . '</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Warning Box -->
            <div style="background: #fff5f5; border: 1px solid #fed7d7; margin: 0 30px 20px; padding: 15px 20px; border-radius: 8px;">
                <p style="margin: 0; color: #c53030; font-size: 13px; line-height: 1.5;">
                    <strong>Nota importante:</strong> Esta sancion ha sido registrada en su historial. Si considera que se trata de un error, 
                    puede comunicarse con la administracion de PRERMI para solicitar una revision del caso.
                </p>
            </div>

            <!-- Footer -->
            <div style="background: #333; color: #aaa; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px;">
                <p style="margin: 0 0 5px;">Este reporte fue generado automaticamente por el sistema PRERMI.</p>
                <p style="margin: 0 0 5px;">Reporte #<strong style="color: #ccc;">' . htmlspecialchars($numReporte) . '</strong></p>
                <p style="margin: 0; color: #888;">Por favor, deposite unicamente materiales permitidos.</p>
            </div>
        </div>';

        $mail->Body = $html;
        $altBody = "REPORTE DE SANCION - PRERMI\n";
        $altBody .= "==============================\n";
        $altBody .= "Reporte #$numReporte\n";
        $altBody .= "Usuario: $name\n";
        $altBody .= "Fecha: $fechaFormateada\n\n";
        $altBody .= "- Motivo: $descripcion\n";
        $altBody .= "- Peso: $pesoStr\n";
        $altBody .= "- Contenedor: $contenedor_info\n";
        $altBody .= "- ID Sancion: $sancion_id\n\n";
        $altBody .= "Si considera que es un error, contacte la administracion.\n\nEquipo PRERMI";
        $mail->AltBody = $altBody;
        $mail->send();
        return true;
    } catch (Exception $e) {
        if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
        file_put_contents(__DIR__ . '/../logs/email_errors.log', "[".date('c')."] SanctionReportError: ".$e->getMessage()."\n", FILE_APPEND);
        return false;
    }
}
