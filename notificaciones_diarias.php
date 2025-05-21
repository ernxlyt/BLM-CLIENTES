<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Configuración SMTP de Gmail
$smtpHost = 'smtp.gmail.com';
$smtpUser = 'ernalytvasquez25@gmail.com'; // Cambia por tu correo
$smtpPass = 'nvsq mkgr zixn qkow'; // Cambia por tu contraseña de aplicación
$smtpPort = 587;

// Obtener correos de administradores
$stmt = $db->prepare("SELECT correo_usuario FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE r.nombre_rol = 'Administrador'");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener correos de todos los usuarios
$stmt = $db->prepare("SELECT correo_usuario FROM usuarios");
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Clientes que deben pagar hoy (basado en el día del mes)
$stmt = $db->prepare("SELECT nombre_cliente, fecha_pago FROM clientes WHERE DAY(fecha_pago) = DAY(CURDATE())");
$stmt->execute();
$clientes_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clientes que cumplen años hoy
$stmt = $db->prepare("SELECT nombre_cliente, cumpleaños FROM clientes WHERE DATE_FORMAT(cumpleaños, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')");
$stmt->execute();
$clientes_cumple = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para enviar correo con PHPMailer
function enviarCorreo($para, $asunto, $mensaje, $smtpHost, $smtpUser, $smtpPass, $smtpPort) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpUser, 'Notificaciones: Saberes y Emociones');
        $mail->addAddress($para);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();
    } catch (Exception $e) {
        error_log("No se pudo enviar el correo a $para. Error: {$mail->ErrorInfo}");
    }
}

// Notificar a administradores sobre pagos
if (count($clientes_pago) > 0) {
    $asunto = "Recordatorio de pagos de clientes";
    $mensaje = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Recordatorio de Pagos</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f7f7f7;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td style="padding: 20px 0;">
                    <table align="center" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 0 auto;">
                        <!-- Header -->
                        <tr>
                            <td style="padding: 30px 40px 20px 40px; text-align: center; background-color: #4a6cf7; border-radius: 8px 8px 0 0;">
                                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Recordatorio de Pagos</h1>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding: 30px 40px;">
                                <p style="margin-top: 0; margin-bottom: 20px; color: #333333; font-size: 16px; line-height: 1.5;">Los siguientes clientes deben realizar su pago hoy (día ' . date('d') . ' del mes):</p>
                                <table width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse: separate; border-spacing: 0 8px;">
                                    <tr>
                                        <th style="text-align: left; padding: 10px; background-color: #f2f6ff; border-radius: 4px 0 0 4px; color: #4a6cf7; font-weight: bold;">Cliente</th>
                                        <th style="text-align: left; padding: 10px; background-color: #f2f6ff; border-radius: 0 4px 4px 0; color: #4a6cf7; font-weight: bold;">Fecha de Pago</th>
                                    </tr>';
    
    foreach ($clientes_pago as $cliente) {
        // Calcular la fecha de pago para este mes
        $dia_pago = date('d', strtotime($cliente['fecha_pago']));
        $mes_actual = date('m');
        $anio_actual = date('Y');
        $fecha_pago_este_mes = "$anio_actual-$mes_actual-$dia_pago";
        
        $mensaje .= '
                                    <tr>
                                        <td style="padding: 12px 10px; background-color: #f9fafc; border-radius: 4px 0 0 4px; border-left: 3px solid #4a6cf7; font-weight: bold; color: #333333;">' . htmlspecialchars($cliente['nombre_cliente']) . '</td>
                                        <td style="padding: 12px 10px; background-color: #f9fafc; border-radius: 0 4px 4px 0; color: #666666;">' . htmlspecialchars(date('d/m/Y', strtotime($fecha_pago_este_mes))) . '</td>
                                    </tr>';
    }
    
    $mensaje .= '
                                </table>
                                <p style="margin-top: 30px; color: #666666; font-size: 14px; line-height: 1.5;">Por favor, asegúrese de contactar a estos clientes para recordarles su pago pendiente.</p>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="padding: 20px 40px; text-align: center; background-color: #f5f7ff; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e9f2;">
                                <p style="margin: 0; color: #8c9db5; font-size: 13px;">Este es un mensaje automático de Saberes y Emociones.</p>
                                <p style="margin: 10px 0 0 0; color: #8c9db5; font-size: 13px;">© ' . date('Y') . ' Saberes y Emociones. Todos los derechos reservados.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
    
    foreach ($admins as $admin) {
        enviarCorreo($admin, $asunto, $mensaje, $smtpHost, $smtpUser, $smtpPass, $smtpPort);
    }
}

// Notificar a TODOS los usuarios sobre cumpleaños
if (count($clientes_cumple) > 0) {
    $asunto = "Recordatorio de cumpleañoos de clientes";
    $mensaje = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Recordatorio de Cumpleaños</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f7f7f7;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td style="padding: 20px 0;">
                    <table align="center" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 0 auto;">
                        <!-- Header -->
                        <tr>
                            <td style="padding: 30px 40px 20px 40px; text-align: center; background-color: #ff9b21; background-image: linear-gradient(to right, #ff9b21, #ffb347); border-radius: 8px 8px 0 0;">
                                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">🎂 ¡Cumpleaños de Clientes! 🎉</h1>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding: 30px 40px;">
                                <p style="margin-top: 0; margin-bottom: 20px; color: #333333; font-size: 16px; line-height: 1.5;">Hoy están de cumpleaños los siguientes clientes:</p>
                                <table width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse: separate; border-spacing: 0 12px;">
    ';
    
    foreach ($clientes_cumple as $cliente) {
        $mensaje .= '
                                    <tr>
                                        <td style="padding: 15px; background-color: #fff9f0; border-radius: 8px; border-left: 4px solid #ff9b21; text-align: center;">
                                            <p style="margin: 0; font-size: 18px; font-weight: bold; color: #333333;">' . htmlspecialchars($cliente['nombre_cliente']) . '</p>
                                            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666666;">Cumpleaños: ' . htmlspecialchars(date('d/m', strtotime($cliente['cumpleaños']))) . '</p>
                                            <div style="margin-top: 10px; font-size: 20px;">🎁 🎈 🎊</div>
                                        </td>
                                    </tr>';
    }
    
    $mensaje .= '
                                </table>
                                <p style="margin-top: 30px; color: #666666; font-size: 14px; line-height: 1.5; text-align: center;">¡No olvides felicitar a nuestros clientes en su día especial!</p>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="padding: 20px 40px; text-align: center; background-color: #fff9f0; border-radius: 0 0 8px 8px; border-top: 1px solid #ffe5c4;">
                                <p style="margin: 0; color: #b38c65; font-size: 13px;">Este es un mensaje automático de Saberes y Emociones.</p>
                                <p style="margin: 10px 0 0 0; color: #b38c65; font-size: 13px;">© ' . date('Y') . ' Saberes y Emociones. Todos los derechos reservados.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
    
    foreach ($usuarios as $usuario) {
        enviarCorreo($usuario, $asunto, $mensaje, $smtpHost, $smtpUser, $smtpPass, $smtpPort);
    }
}
?>