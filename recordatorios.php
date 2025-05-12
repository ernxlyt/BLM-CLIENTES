<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'client_management';
$username = 'root'; // Usuario por defecto en XAMPP/WAMP
$password = ''; // Contraseña por defecto en XAMPP/WAMP (vacía)

// Configuración del correo
$nombre_empresa = 'Sistema de Gestión de Clientes';

// Configuración de PHPMailer
// Primero necesitas instalar PHPMailer: composer require phpmailer/phpmailer

require 'vendor/autoload.php'; // Ajusta la ruta según donde tengas instalado Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Función para registrar logs
function escribirLog($mensaje) {
    $directorio_logs = __DIR__ . '/logs';
    
    // Crear directorio de logs si no existe
    if (!file_exists($directorio_logs)) {
        mkdir($directorio_logs, 0777, true);
    }
    
    $archivo = $directorio_logs . '/recordatorios_' . date('Y-m-d') . '.log';
    $fecha = date('Y-m-d H:i:s');
    file_put_contents($archivo, "[$fecha] $mensaje" . PHP_EOL, FILE_APPEND);
    
    // También mostrar en consola si se ejecuta desde línea de comandos
    if (php_sapi_name() === 'cli') {
        echo "[$fecha] $mensaje" . PHP_EOL;
    }
}

// Función para enviar correo
function enviarCorreo($destinatario, $asunto, $cuerpo) {
    global $nombre_empresa;
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Cambia esto por tu servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ernalytvasquez25@gmail.com'; // Cambia esto por tu correo
        $mail->Password   = 'ERNAL2DAVTE025!!'; // Cambia esto por tu contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Destinatarios
        $mail->setFrom('ernalytvasquez25@gmail.com', $nombre_empresa);
        $mail->addAddress($destinatario);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;
        $mail->CharSet = 'UTF-8';
        
        $mail->send();
        escribirLog("Correo enviado a $destinatario correctamente.");
        return true;
    } catch (Exception $e) {
        escribirLog("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    escribirLog("Iniciando proceso de recordatorios");
    
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
    
    // Fecha actual
    $hoy = date('Y-m-d');
    $dia_actual = date('d');
    $mes_actual = date('m');
    
    escribirLog("Verificando recordatorios para el día $hoy");
    
    // Obtener información del superadministrador
    $sql_admin = "SELECT u.id_usuario, u.nombre_usuario, u.correo_usuario 
                  FROM usuarios u 
                  JOIN roles r ON u.id_rol = r.id_rol 
                  WHERE r.nombre_rol = 'Administrador' 
                  LIMIT 1";
    $stmt = $pdo->prepare($sql_admin);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $email_admin = $admin['correo_usuario'];
        $id_admin = $admin['id_usuario'];
        escribirLog("Administrador encontrado: {$admin['nombre_usuario']} ({$email_admin})");
    } else {
        $email_admin = 'ernalytvasquez25@gmail.com'; // Email por defecto si no se encuentra admin
        $id_admin = 0;
        escribirLog("No se encontró un administrador en la base de datos, usando el correo predeterminado.");
    }
    
    // Arrays para almacenar los mensajes para el superadministrador
    $mensajes_pago_admin = [];
    $mensajes_cumpleanos_admin = [];
    
    // Arrays para almacenar los mensajes por usuario
    $usuarios_pagos = [];
    $usuarios_cumpleanos = [];
    
    // 1. Verificar fechas de pago mensuales
    $sql_pagos = "SELECT c.id_cliente, c.nombre_cliente, c.fecha_pago, p.nombre_plan, e.nombre_empresa,
                  r.id_usuario, u.nombre_usuario, u.correo_usuario
                  FROM clientes c
                  LEFT JOIN planes p ON c.id_plan = p.id_plan
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  LEFT JOIN relaciones r ON c.id_cliente = r.id_cliente
                  LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                  WHERE DAY(c.fecha_pago) = :dia_actual
                  AND c.estado = 'Activo'";
    
    $stmt = $pdo->prepare($sql_pagos);
    $stmt->execute(['dia_actual' => $dia_actual]);
    $clientes_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($clientes_pago) > 0) {
        escribirLog("Se encontraron " . count($clientes_pago) . " clientes con fecha de pago hoy.");
        
        $tabla_pagos_admin = "<table border='1' cellpadding='5' style='border-collapse: collapse;'>
                        <tr style='background-color: #f2f2f2;'>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Fecha de Pago</th>
                            <th>Usuario Asignado</th>
                        </tr>";
        
        foreach ($clientes_pago as $cliente) {
            // Añadir a la tabla del administrador
            $tabla_pagos_admin .= "<tr>
                            <td>{$cliente['id_cliente']}</td>
                            <td>{$cliente['nombre_cliente']}</td>
                            <td>{$cliente['nombre_empresa']}</td>
                            <td>{$cliente['nombre_plan']}</td>
                            <td>{$cliente['fecha_pago']}</td>
                            <td>{$cliente['nombre_usuario']}</td>
                          </tr>";
            
            // Agrupar por usuario para enviar correos individuales
            if (!empty($cliente['id_usuario']) && $cliente['id_usuario'] != $id_admin) {
                if (!isset($usuarios_pagos[$cliente['id_usuario']])) {
                    $usuarios_pagos[$cliente['id_usuario']] = [
                        'nombre' => $cliente['nombre_usuario'],
                        'correo' => $cliente['correo_usuario'],
                        'clientes' => []
                    ];
                }
                
                $usuarios_pagos[$cliente['id_usuario']]['clientes'][] = $cliente;
            }
        }
        
        $tabla_pagos_admin .= "</table>";
        $mensajes_pago_admin[] = $tabla_pagos_admin;
    } else {
        escribirLog("No hay clientes con fecha de pago hoy.");
    }
    
    // 2. Verificar cumpleaños (recordatorio anual)
    $sql_cumpleanos = "SELECT c.id_cliente, c.nombre_cliente, c.cumpleaños, p.nombre_plan, e.nombre_empresa,
                       r.id_usuario, u.nombre_usuario, u.correo_usuario
                       FROM clientes c
                       LEFT JOIN planes p ON c.id_plan = p.id_plan
                       LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                       LEFT JOIN relaciones r ON c.id_cliente = r.id_cliente
                       LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                       WHERE DAY(c.cumpleaños) = :dia_actual 
                       AND MONTH(c.cumpleaños) = :mes_actual
                       AND c.estado = 'Activo'";
    
    $stmt = $pdo->prepare($sql_cumpleanos);
    $stmt->execute([
        'dia_actual' => $dia_actual,
        'mes_actual' => $mes_actual
    ]);
    $clientes_cumpleanos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($clientes_cumpleanos) > 0) {
        escribirLog("Se encontraron " . count($clientes_cumpleanos) . " clientes con cumpleaños hoy.");
        
        $tabla_cumpleanos_admin = "<table border='1' cellpadding='5' style='border-collapse: collapse;'>
                        <tr style='background-color: #f2f2f2;'>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Fecha de Nacimiento</th>
                            <th>Usuario Asignado</th>
                        </tr>";
        
        foreach ($clientes_cumpleanos as $cliente) {
            // Añadir a la tabla del administrador
            $tabla_cumpleanos_admin .= "<tr>
                            <td>{$cliente['id_cliente']}</td>
                            <td>{$cliente['nombre_cliente']}</td>
                            <td>{$cliente['nombre_empresa']}</td>
                            <td>{$cliente['nombre_plan']}</td>
                            <td>{$cliente['cumpleaños']}</td>
                            <td>{$cliente['nombre_usuario']}</td>
                          </tr>";
            
            // Agrupar por usuario para enviar correos individuales
            if (!empty($cliente['id_usuario']) && $cliente['id_usuario'] != $id_admin) {
                if (!isset($usuarios_cumpleanos[$cliente['id_usuario']])) {
                    $usuarios_cumpleanos[$cliente['id_usuario']] = [
                        'nombre' => $cliente['nombre_usuario'],
                        'correo' => $cliente['correo_usuario'],
                        'clientes' => []
                    ];
                }
                
                $usuarios_cumpleanos[$cliente['id_usuario']]['clientes'][] = $cliente;
            }
        }
        
        $tabla_cumpleanos_admin .= "</table>";
        $mensajes_cumpleanos_admin[] = $tabla_cumpleanos_admin;
    } else {
        escribirLog("No hay clientes con cumpleaños hoy.");
    }
    
    // Enviar correo al superadministrador si hay recordatorios
    if (count($mensajes_pago_admin) > 0 || count($mensajes_cumpleanos_admin) > 0) {
        $asunto = "Recordatorios del sistema - " . date('d/m/Y');
        
        $cuerpo = "<html><body>";
        $cuerpo .= "<h1 style='color: #2c3e50;'>Recordatorios del día " . date('d/m/Y') . "</h1>";
        
        if (count($mensajes_pago_admin) > 0) {
            $cuerpo .= "<h2 style='color: #3498db;'>Recordatorio de fechas de pago</h2>";
            $cuerpo .= "<p>Los siguientes clientes tienen fecha de pago hoy:</p>";
            $cuerpo .= implode("\n", $mensajes_pago_admin);
        }
        
        if (count($mensajes_cumpleanos_admin) > 0) {
            $cuerpo .= "<h2 style='color: #e74c3c;'>Recordatorio de cumpleaños</h2>";
            $cuerpo .= "<p>Los siguientes clientes cumplen años hoy:</p>";
            $cuerpo .= implode("\n", $mensajes_cumpleanos_admin);
        }
        
        $cuerpo .= "<p style='margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee;'>Este es un mensaje automático del sistema de gestión de clientes.</p>";
        $cuerpo .= "</body></html>";
        
        if (enviarCorreo($email_admin, $asunto, $cuerpo)) {
            escribirLog("Correo de recordatorios enviado al administrador correctamente.");
        }
    }
    
    // Enviar correos individuales a cada usuario con sus clientes asignados
    // 1. Enviar recordatorios de pago a usuarios
    foreach ($usuarios_pagos as $id_usuario => $usuario) {
        if (empty($usuario['correo'])) continue;
        
        $asunto = "Recordatorio de fechas de pago - " . date('d/m/Y');
        
        $cuerpo = "<html><body>";
        $cuerpo .= "<h1 style='color: #2c3e50;'>Recordatorio de fechas de pago</h1>";
        $cuerpo .= "<p>Hola {$usuario['nombre']},</p>";
        $cuerpo .= "<p>Los siguientes clientes asignados a ti tienen fecha de pago hoy " . date('d/m/Y') . ":</p>";
        
        $tabla = "<table border='1' cellpadding='5' style='border-collapse: collapse;'>
                <tr style='background-color: #f2f2f2;'>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Empresa</th>
                    <th>Plan</th>
                    <th>Fecha de Pago</th>
                </tr>";
        
        foreach ($usuario['clientes'] as $cliente) {
            $tabla .= "<tr>
                    <td>{$cliente['id_cliente']}</td>
                    <td>{$cliente['nombre_cliente']}</td>
                    <td>{$cliente['nombre_empresa']}</td>
                    <td>{$cliente['nombre_plan']}</td>
                    <td>{$cliente['fecha_pago']}</td>
                  </tr>";
        }
        
        $tabla .= "</table>";
        
        $cuerpo .= $tabla;
        $cuerpo .= "<p style='margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee;'>Este es un mensaje automático del sistema de gestión de clientes.</p>";
        $cuerpo .= "</body></html>";
        
        if (enviarCorreo($usuario['correo'], $asunto, $cuerpo)) {
            escribirLog("Correo de recordatorio de pagos enviado a {$usuario['nombre']} ({$usuario['correo']}).");
        }
    }
    
    // 2. Enviar recordatorios de cumpleaños a usuarios
    foreach ($usuarios_cumpleanos as $id_usuario => $usuario) {
        if (empty($usuario['correo'])) continue;
        
        $asunto = "Recordatorio de cumpleaños de clientes - " . date('d/m/Y');
        
        $cuerpo = "<html><body>";
        $cuerpo .= "<h1 style='color: #2c3e50;'>Recordatorio de cumpleaños</h1>";
        $cuerpo .= "<p>Hola {$usuario['nombre']},</p>";
        $cuerpo .= "<p>Los siguientes clientes asignados a ti cumplen años hoy " . date('d/m/Y') . ":</p>";
        
        $tabla = "<table border='1' cellpadding='5' style='border-collapse: collapse;'>
                <tr style='background-color: #f2f2f2;'>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Empresa</th>
                    <th>Plan</th>
                    <th>Fecha de Nacimiento</th>
                </tr>";
        
        foreach ($usuario['clientes'] as $cliente) {
            $tabla .= "<tr>
                    <td>{$cliente['id_cliente']}</td>
                    <td>{$cliente['nombre_cliente']}</td>
                    <td>{$cliente['nombre_empresa']}</td>
                    <td>{$cliente['nombre_plan']}</td>
                    <td>{$cliente['cumpleaños']}</td>
                  </tr>";
        }
        
        $tabla .= "</table>";
        
        $cuerpo .= $tabla;
        $cuerpo .= "<p style='margin-top: 20px; padding-top: 10px; border-top: 1px solid #eee;'>Este es un mensaje automático del sistema de gestión de clientes.</p>";
        $cuerpo .= "</body></html>";
        
        if (enviarCorreo($usuario['correo'], $asunto, $cuerpo)) {
            escribirLog("Correo de recordatorio de cumpleaños enviado a {$usuario['nombre']} ({$usuario['correo']}).");
        }
    }
    
    escribirLog("Proceso de recordatorios completado.");
    
} catch (PDOException $e) {
    escribirLog("Error de base de datos: " . $e->getMessage());
} catch (Exception $e) {
    escribirLog("Error general: " . $e->getMessage());
}
?>