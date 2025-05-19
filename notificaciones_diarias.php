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

// Clientes que deben pagar hoy
$stmt = $db->prepare("SELECT nombre_cliente, fecha_pago FROM clientes WHERE fecha_pago = CURDATE()");
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
    $mensaje = "Los siguientes clientes deben realizar su pago hoy:<br><ul>";
    foreach ($clientes_pago as $cliente) {
        $mensaje .= "<li><b>{$cliente['nombre_cliente']}</b> (Fecha de pago: {$cliente['fecha_pago']})</li>";
    }
    $mensaje .= "</ul>";
    foreach ($admins as $admin) {
        enviarCorreo($admin, $asunto, $mensaje, $smtpHost, $smtpUser, $smtpPass, $smtpPort);
    }
}

// Notificar a TODOS los usuarios sobre cumpleaños
if (count($clientes_cumple) > 0) {
    $asunto = "Recordatorio de cumpleaños de clientes";
    $mensaje = "Hoy están de cumpleaños los siguientes clientes:<br><ul>";
    foreach ($clientes_cumple as $cliente) {
        $mensaje .= "<li><b>{$cliente['nombre_cliente']}</b> (Cumpleaños: {$cliente['cumpleaños']})</li>";
    }
    $mensaje .= "</ul>";
    foreach ($usuarios as $usuario) {
        enviarCorreo($usuario, $asunto, $mensaje, $smtpHost, $smtpUser, $smtpPass, $smtpPort);
    }
}
?>