<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'utils/session.php';

if(isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user->correo_usuario = $_POST['usuario_correo']; // Aquí puede ser nombre de usuario o correo
    $user->contrasena = $_POST['contrasena']; // Captura la contraseña

    if ($user->login()) {
        setUserSession($user->id_usuario, $user->nombre_usuario, $user->isAdmin());
        header("Location: index.php");
        exit();
    } else {
        $message = 'Credenciales incorrectas. Por favor, verifica tu usuario/correo y contraseña.';
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image" href="assets/img/favicon.png">

    <style>
        body {
            background-color: #f9fafb;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .login-logo img {
            max-width: 200px;
        }
        .relative {
    position: relative;
}
        .relative input {
    width: 100%;
    padding: 10px 15px;
    padding-left: 40px; /* Espacio suficiente para el ícono */
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.relative span {
    position: absolute;
    top: 50%;
    left: 10px;
    transform: translateY(-50%);
    font-size: 16px;
    color: #888;
}

.relative input:focus {
    border-color:rgb(0, 255, 102);
    outline: none;
    box-shadow: 0 0 5px rgba(53, 220, 15, 0.2);
}

    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="assets/img/logo.png" alt="Logo">
            </div>
            
            <h1 class="text-2xl font-bold mb-6 text-center">Iniciar Sesión</h1>
            
            <?php if(!empty($message)): ?>
                <div class="alert alert-danger mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <!-- Campo de Usuario o Correo Electrónico -->
    <div class="form-group">
        <label for="usuario_correo" class="form-label">Usuario o Correo Electrónico</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                <i class="fas fa-user"></i>
            </span>
            <input type="text" id="usuario_correo" name="usuario_correo" required 
                   class="form-control pl-10" placeholder="Ingrese su usuario o correo">
        </div>
    </div>
    
    <!-- Campo de Contraseña -->
    <div class="form-group">
        <label for="contrasena" class="form-label">Contraseña</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                <i class="fas fa-lock"></i>
            </span>
            <input type="password" id="contrasena" name="contrasena" required 
                   class="form-control pl-10" placeholder="Ingrese su contraseña">
        </div>
    </div>

    <!-- Botón de envío -->
    <button type="submit" class="btn btn-primary w-full mt-6">
        Iniciar Sesión
    </button>
</form>

    </div>
</body>
</html>
