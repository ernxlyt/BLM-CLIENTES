<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'models/Role.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$role = new Role($db);

$page_title = "Nuevo Usuario";
$action = "create";
$error_message = "";
$success_message = "";

// Verificar si se está editando un usuario existente
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user->id_usuario = $_GET['id'];
    
    if ($user->readOne()) {
        $page_title = "Editar Usuario";
        $action = "update";
    } else {
        header("Location: users.php");
        exit();
    }
}

// Procesar formulario de envío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user->nombre_usuario = trim($_POST['nombre_usuario']);
    $user->correo_usuario = trim($_POST['correo_usuario']);
    $user->id_rol = $_POST['id_rol'];

    // Validaciones básicas
    if (empty($user->nombre_usuario)) {
        $error_message = "El nombre del usuario es obligatorio.";
    } elseif (empty($user->correo_usuario)) {
        $error_message = "El correo electrónico es obligatorio.";
    } elseif (empty($user->id_rol)) {
        $error_message = "Debe seleccionar un rol.";
    } elseif ($action === "create" && empty($_POST['contrasena'])) {
        $error_message = "La contraseña es obligatoria al crear un usuario.";
    } else {
        if ($action === "create") {
            $user->contrasena = $_POST['contrasena'];
            $result = $user->create();
            
            if ($result === true) {
                header("Location: users.php?success=created");
                exit();
            } else {
                // Manejar diferentes tipos de error
                switch ($result) {
                    case 'username_exists':
                        $error_message = "El nombre de usuario '{$user->nombre_usuario}' ya está en uso. Por favor, elija otro nombre.";
                        break;
                    case 'email_exists':
                        $error_message = "El correo electrónico '{$user->correo_usuario}' ya está registrado. Por favor, use otro correo.";
                        break;
                    case 'password_required':
                        $error_message = "La contraseña es obligatoria al crear un usuario.";
                        break;
                    case 'database_error':
                        $error_message = "Error al crear el usuario. Por favor, inténtelo de nuevo.";
                        break;
                    default:
                        $error_message = "Error desconocido al crear el usuario.";
                }
            }
        } else {
            // Actualizar usuario
            if (!empty($_POST['contrasena'])) {
                $user->contrasena = $_POST['contrasena'];
                $user->updatePassword();
            }

            $result = $user->update();
            
            if ($result === true) {
                header("Location: users.php?success=updated");
                exit();
            } else {
                // Manejar diferentes tipos de error
                switch ($result) {
                    case 'username_exists':
                        $error_message = "El nombre de usuario '{$user->nombre_usuario}' ya está en uso. Por favor, elija otro nombre.";
                        break;
                    case 'email_exists':
                        $error_message = "El correo electrónico '{$user->correo_usuario}' ya está registrado. Por favor, use otro correo.";
                        break;
                    case 'database_error':
                        $error_message = "Error al actualizar el usuario. Por favor, inténtelo de nuevo.";
                        break;
                    default:
                        $error_message = "Error desconocido al actualizar el usuario.";
                }
            }
        }
    }
}

// Leer roles para el select
$roles_stmt = $role->read();

include 'includes/layout_header.php';
?>

<style>
/* Paleta de colores minimalista */
:root {
    --primary-green: #23D950;
    --dark-green: #1ea83e;
    --light-green: #f0fdf4;
    --black: #000000;
    --dark-gray: #1f2937;
    --medium-gray: #6b7280;
    --light-gray: #f8fafc;
    --border-gray: #e5e7eb;
    --white: #ffffff;
    --red: #ef4444;
    --light-red: #fef2f2;
    --green: #10b981;
}

/* Contenedor principal */
.user-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Header de la página */
.page-header {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
    position: relative;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    border-radius: 12px 12px 0 0;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-gray);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.title-icon {
    width: 32px;
    height: 32px;
    background: var(--primary-green);
    color: var(--white);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* Alertas */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-danger {
    background: var(--light-red);
    color: var(--red);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-success {
    background: var(--light-green);
    color: var(--dark-green);
    border: 1px solid rgba(35, 217, 80, 0.2);
}

.alert-icon {
    font-size: 1rem;
    flex-shrink: 0;
}

/* Formulario */
.form-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    overflow: hidden;
    margin-bottom: 2rem;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-header {
    background: var(--light-green);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border-gray);
}

.form-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-green);
    margin: 0;
}

.form-body {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-gray);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--dark-gray);
    font-size: 0.875rem;
}

.required-indicator {
    color: var(--red);
    margin-left: 0.25rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    background: var(--white);
    color: var(--dark-gray);
    font-size: 0.875rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

.form-control.error {
    border-color: var(--red);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-text {
    font-size: 0.75rem;
    color: var(--medium-gray);
    margin-top: 0.5rem;
}

/* Campo de contraseña */
.password-input-container {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--medium-gray);
    cursor: pointer;
    transition: color 0.2s ease;
    z-index: 10;
}

.password-toggle:hover {
    color: var(--primary-green);
}

/* Botones */
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    gap: 1rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    font-size: 0.875rem;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: var(--primary-green);
    color: var(--white);
    border-color: var(--primary-green);
}

.btn-primary:hover:not(:disabled) {
    background: var(--dark-green);
    border-color: var(--dark-green);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(35, 217, 80, 0.1);
}

.btn-secondary {
    background: var(--white);
    color: var(--dark-gray);
    border-color: var(--border-gray);
}

.btn-secondary:hover {
    background: var(--light-gray);
    border-color: var(--medium-gray);
}

.btn-danger {
    background: var(--white);
    color: var(--red);
    border-color: var(--red);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .user-form-container {
        padding: 0 0.5rem;
    }
    
    .form-body {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<div class="user-form-container">
    <!-- Header de la página -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <div class="title-icon">
                    <i class="fas fa-user"></i>
                </div>
                <?php echo $page_title; ?>
            </h1>
        </div>
    </div>

    <!-- Mostrar mensajes de error -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle alert-icon"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Mostrar mensajes de éxito -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle alert-icon"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $user->id_usuario : "")); ?>" id="userForm">
        <div class="form-card">
            <div class="form-header">
                <h2 class="form-title">Información del Usuario</h2>
            </div>
            
            <div class="form-body">
                <div class="form-section">
                    <div class="form-group">
                        <label for="nombre_usuario" class="form-label">
                            Nombre del Usuario
                            <span class="required-indicator">*</span>
                        </label>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" required 
                               value="<?php echo $action === "update" ? htmlspecialchars($user->nombre_usuario) : (isset($_POST['nombre_usuario']) ? htmlspecialchars($_POST['nombre_usuario']) : ''); ?>"
                               class="form-control <?php echo (!empty($error_message) && strpos($error_message, 'nombre de usuario') !== false) ? 'error' : ''; ?>" 
                               placeholder="Ej: Juan Pérez"
                               autofocus>
                        <div class="form-text">Ingresa el nombre completo del usuario. Debe ser único en el sistema.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo_usuario" class="form-label">
                            Correo Electrónico
                            <span class="required-indicator">*</span>
                        </label>
                        <input type="email" id="correo_usuario" name="correo_usuario" required 
                               value="<?php echo $action === "update" ? htmlspecialchars($user->correo_usuario) : (isset($_POST['correo_usuario']) ? htmlspecialchars($_POST['correo_usuario']) : ''); ?>"
                               class="form-control <?php echo (!empty($error_message) && strpos($error_message, 'correo') !== false) ? 'error' : ''; ?>" 
                               placeholder="usuario@ejemplo.com">
                        <div class="form-text">Este correo se usará para iniciar sesión y debe ser único.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contrasena" class="form-label">
                            <?php echo $action === "create" ? "Contraseña" : "Nueva Contraseña"; ?>
                            <?php if ($action === "create"): ?>
                                <span class="required-indicator">*</span>
                            <?php endif; ?>
                        </label>
                        <div class="password-input-container">
                            <input type="password" id="contrasena" name="contrasena" 
                                   <?php echo $action === "create" ? "required" : ""; ?>
                                   class="form-control" placeholder="••••••••"
                                   minlength="4">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                        </div>
                        <div class="form-text">
                            <?php if ($action === "update"): ?>
                                Dejar en blanco para mantener la contraseña actual.
                            <?php else: ?>
                                Mínimo 4 caracteres recomendados.
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_rol" class="form-label">
                            Rol del Usuario
                            <span class="required-indicator">*</span>
                        </label>
                        <select id="id_rol" name="id_rol" required class="form-control">
                            <option value="">Seleccionar Rol</option>
                            <?php 
                            while ($row = $roles_stmt->fetch(PDO::FETCH_ASSOC)) {
                                extract($row);
                                $selected = "";
                                if ($action === "update" && $user->id_rol == $id_rol) {
                                    $selected = "selected";
                                } elseif (isset($_POST['id_rol']) && $_POST['id_rol'] == $id_rol) {
                                    $selected = "selected";
                                }
                                echo "<option value='{$id_rol}' {$selected}>{$nombre_rol}</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text">Define los permisos y accesos del usuario.</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        <?php echo $action === "update" ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('contrasena');
    const toggleIcon = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Prevenir envío doble del formulario
    const form = document.getElementById('userForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    });
    
    // Validación básica del lado del cliente
    form.addEventListener('submit', function(e) {
        const nombreUsuario = document.getElementById('nombre_usuario').value.trim();
        const correoUsuario = document.getElementById('correo_usuario').value.trim();
        const idRol = document.getElementById('id_rol').value;
        const contrasena = document.getElementById('contrasena').value;
        
        let hasError = false;
        
        if (nombreUsuario === '') {
            e.preventDefault();
            alert('Por favor ingrese un nombre para el usuario.');
            document.getElementById('nombre_usuario').focus();
            hasError = true;
        }
        
        if (!hasError && correoUsuario === '') {
            e.preventDefault();
            alert('Por favor ingrese un correo electrónico válido.');
            document.getElementById('correo_usuario').focus();
            hasError = true;
        }
        
        if (!hasError && idRol === '') {
            e.preventDefault();
            alert('Por favor seleccione un rol para el usuario.');
            document.getElementById('id_rol').focus();
            hasError = true;
        }
        
        // Validar contraseña solo al crear usuario
        <?php if ($action === "create"): ?>
        if (!hasError && contrasena === '') {
            e.preventDefault();
            alert('Por favor ingrese una contraseña.');
            document.getElementById('contrasena').focus();
            hasError = true;
        }
        
        if (!hasError && contrasena.length < 4) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 4 caracteres.');
            document.getElementById('contrasena').focus();
            hasError = true;
        }
        <?php endif; ?>
        
        // Si hay error, rehabilitar el botón
        if (hasError) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> <?php echo $action === "update" ? "Actualizar Usuario" : "Crear Usuario"; ?>';
        }
    });
    
    // Limpiar estilos de error al escribir
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('error');
        });
    });
    
    // Auto-ocultar alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 5000);
    });
});
</script>

<?php
include 'includes/layout_footer.php';
?>
