<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Client.php';
include_once 'models/Plan.php';
include_once 'models/Empresa.php';
include_once 'models/SocialNetwork.php';
include_once 'utils/session.php';

// Require login and admin
requireLogin();
requireAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$client = new Client($db);
$plan = new Plan($db);
$empresa = new Empresa($db);
$social_network = new SocialNetwork($db);

// Set page title and action
$page_title = "Nuevo Cliente";
$action = "create";

// Check if editing
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $client->id_cliente = $_GET['id'];
    
    if($client->readOne(getCurrentUserId(), isAdmin())) {
        $page_title = "Editar Cliente";
        $action = "update";
    } else {
        header("Location: clients.php");
        exit();
    }
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // DEBUG - Mostrar datos recibidos (remover después de verificar)
    if(isset($_GET['debug'])) {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
        echo "<h4>🔍 DEBUG - Datos del formulario:</h4>";
        echo "<strong>País:</strong> '" . ($_POST['pais'] ?? 'NO ENVIADO') . "'<br>";
        echo "<strong>Provincia:</strong> '" . ($_POST['provincia'] ?? 'NO ENVIADO') . "'<br>";
        echo "<strong>Nombre:</strong> '" . ($_POST['nombre_cliente'] ?? 'NO ENVIADO') . "'<br>";
        echo "</div>";
    }
    
    try {
        // Iniciar transacción para asegurar consistencia
        $db->beginTransaction();
        
        // Set client properties
        $client->nombre_cliente = $_POST['nombre_cliente'];
        $client->fecha_inicio = $_POST['fecha_inicio'];
        $client->cumpleaños = $_POST['cumpleaños'];
        $client->fecha_pago = !empty($_POST['fecha_pago']) ? $_POST['fecha_pago'] : null;
        $client->estado = $_POST['estado'];
        $client->id_plan = !empty($_POST['id_plan']) ? $_POST['id_plan'] : null;
        $client->id_empresa = !empty($_POST['id_empresa']) ? $_POST['id_empresa'] : null;
        
        // CAMPOS GEOGRÁFICOS - Asegurar que se asignen correctamente
        $client->pais = isset($_POST['pais']) && !empty(trim($_POST['pais'])) ? trim($_POST['pais']) : null;
        $client->provincia = isset($_POST['provincia']) && !empty(trim($_POST['provincia'])) ? trim($_POST['provincia']) : null;
        
        // Create or update client
        $client_success = false;
        $client_id = null;
        
        if($action === "create") {
            if($client->create()) {
                $client_success = true;
                $client_id = $db->lastInsertId();
                $client->id_cliente = $client_id;
            }
        } else {
            if($client->update()) {
                $client_success = true;
                $client_id = $client->id_cliente;
            }
        }
        
        // If client was created/updated successfully and social network data was provided
        if($client_success && isset($_POST['add_social_network']) && $_POST['add_social_network'] == '1') {
            
            if(empty($client_id)) {
                throw new Exception("Error: No se pudo obtener el ID del cliente");
            }
            
            // Verificar que el cliente existe en la base de datos
            $check_query = "SELECT id_cliente FROM clientes WHERE id_cliente = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(1, $client_id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if($check_stmt->rowCount() == 0) {
                throw new Exception("Error: El cliente no existe en la base de datos");
            }
            
            // Validar datos de red social
            $tipo_red = '';
            if(isset($_POST['new_network_type']) && !empty($_POST['new_network_type'])) {
                $tipo_red = $_POST['new_network_type'];
            } elseif(isset($_POST['tipo_red']) && !empty($_POST['tipo_red']) && $_POST['tipo_red'] !== 'other') {
                $tipo_red = $_POST['tipo_red'];
            }
            
            if(!empty($tipo_red) && !empty($_POST['nombre_red'])) {
                // Set social network properties
                $social_network->id_cliente = $client_id;
                $social_network->tipo_red = $tipo_red;
                $social_network->nombre_red = $_POST['nombre_red'];
                $social_network->usuario_red = $_POST['usuario_red'] ?? '';
                $social_network->contrasena_red = $_POST['contrasena_red'] ?? '';
                $social_network->url_red = $_POST['url_red'] ?? '';
                $social_network->notas = $_POST['notas'] ?? '';
                
                // Create social network
                if(!$social_network->createWithValidation($client_id)) {
                    throw new Exception("Error al crear la red social");
                }
            }
        }
        
        // Crear relación usuario-cliente si es un nuevo cliente
        if($action === "create" && isset($_SESSION['user_id'])) {
            $relation_query = "INSERT INTO relaciones (id_usuario, id_cliente) VALUES (?, ?)";
            $relation_stmt = $db->prepare($relation_query);
            $relation_stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $relation_stmt->bindParam(2, $client_id, PDO::PARAM_INT);
            $relation_stmt->execute();
        }
        
        // Confirmar transacción
        $db->commit();
        
        // Redirect after processing
        header("Location: client-view.php?id=" . $client_id . "&success=1");
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->rollback();
        $error_message = $e->getMessage();
        error_log("Error en client-form.php: " . $error_message);
    }
}

// Get all plans
$planes_stmt = $plan->read();

// Get all empresas
$empresas_stmt = $empresa->read();

// Get network types for dropdown
$network_types = $social_network->getNetworkTypes();

// Include header
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
}

/* Contenedor principal */
.form-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Mensajes de error */
.error-message {
    background-color: #fef2f2;
    border: 1px solid #ef4444;
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Card principal del formulario */
.form-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    overflow: hidden;
}

.form-header {
    background: var(--black);
    color: var(--white);
    padding: 1.5rem 2rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.form-content {
    padding: 2rem;
}

/* Secciones del formulario */
.form-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-icon {
    width: 24px;
    height: 24px;
    color: var(--primary-green);
}

/* Grid de campos */
.fields-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.field-card {
    background: var(--light-gray);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s ease;
}

.field-card:hover {
    border-color: var(--primary-green);
}

.field-card.full-width {
    grid-column: 1 / -1;
}

/* Labels y campos */
.field-label {
    display: block;
    color: var(--dark-gray);
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.field-required {
    color: #ef4444;
}

.field-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-gray);
    border-radius: 6px;
    background: var(--white);
    color: var(--dark-gray);
    transition: border-color 0.2s ease;
    font-size: 0.875rem;
}

.field-input:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

/* Secciones especiales */
.location-section {
    background: var(--light-gray);
    border: 2px solid var(--border-gray);
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
}

.location-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-green);
    border-radius: 12px 12px 0 0;
}

.social-section {
    background: var(--light-gray);
    border: 2px solid var(--border-gray);
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
}

.social-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--black);
    border-radius: 12px 12px 0 0;
}

.social-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.social-form {
    background: var(--white);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
}

/* Botones */
.btn-minimal {
    display: inline-flex;
    align-items: center;
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

.btn-primary {
    background: var(--primary-green);
    color: var(--white);
    border-color: var(--primary-green);
}

.btn-primary:hover {
    background: var(--dark-green);
    border-color: var(--dark-green);
    transform: translateY(-1px);
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

.btn-black {
    background: var(--black);
    color: var(--white);
    border-color: var(--black);
}

.btn-black:hover {
    background: var(--dark-gray);
    border-color: var(--dark-gray);
    transform: translateY(-1px);
}

.btn-toggle {
    transition: all 0.3s ease;
}

.btn-toggle.active {
    background: #ef4444;
    border-color: #ef4444;
}

.btn-toggle.active:hover {
    background: #dc2626;
    border-color: #dc2626;
}

/* Campos especiales */
.password-field {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--medium-gray);
    cursor: pointer;
    padding: 0.25rem;
}

.password-toggle:hover {
    color: var(--dark-gray);
}

.flex-field {
    display: flex;
    gap: 0.5rem;
}

.flex-field .field-input {
    flex: 1;
}

/* Área de acciones */
.form-actions {
    background: var(--light-gray);
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--border-gray);
    margin-top: 2rem;
}

.actions-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.actions-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.actions-group {
    display: flex;
    gap: 0.75rem;
}

.form-note {
    text-align: center;
    font-size: 0.875rem;
    color: var(--medium-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* Animaciones */
.fade-in {
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.fade-in.active {
    opacity: 1;
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 768px) {
    .form-container {
        padding: 0 0.5rem;
    }
    
    .form-content {
        padding: 1rem;
    }
    
    .fields-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .actions-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .actions-group {
        flex-direction: column;
    }
    
    .social-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
}

/* Estados de validación */
.field-input.error {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.field-input.success {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

/* Loading state */
.btn-loading {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-loading:hover {
    transform: none;
}
</style>

<div class="form-container">
    <!-- Mensaje de error -->
    <?php if(isset($error_message)): ?>
    <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- Card principal del formulario -->
    <div class="form-card">
        <div class="form-header">
            <h1 class="form-title"><?php echo $page_title; ?></h1>
        </div>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $client->id_cliente : "")); ?>" id="clientForm">
            <div class="form-content">
                <!-- Información del Cliente -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user section-icon"></i>
                        Información del Cliente
                    </h3>
                    
                    <div class="fields-grid">
                        <!-- Nombre del Cliente -->
                        <div class="field-card">
                            <label for="nombre_cliente" class="field-label">
                                Nombre del Cliente <span class="field-required">*</span>
                            </label>
                            <input type="text" id="nombre_cliente" name="nombre_cliente" required 
                                   value="<?php echo $action === "update" ? htmlspecialchars($client->nombre_cliente ?? '') : ''; ?>"
                                   class="field-input" placeholder="Nombre completo del cliente">
                        </div>
                        
                        <!-- Fecha de Inicio -->
                        <div class="field-card">
                            <label for="fecha_inicio" class="field-label">
                                Fecha de Inicio <span class="field-required">*</span>
                            </label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" required 
                                   value="<?php echo $action === "update" ? ($client->fecha_inicio ?? '') : ''; ?>"
                                   class="field-input">
                        </div>
                        
                        <!-- Cumpleaños -->
                        <div class="field-card">
                            <label for="cumpleaños" class="field-label">
                                Cumpleaños <span class="field-required">*</span>
                            </label>
                            <input type="date" id="cumpleaños" name="cumpleaños" required 
                                   value="<?php echo $action === "update" ? ($client->cumpleaños ?? '') : ''; ?>"
                                   class="field-input">
                        </div>
                        
                        <!-- Fecha de Pago -->
                        <div class="field-card">
                            <label for="fecha_pago" class="field-label">
                                Fecha de Pago
                            </label>
                            <input type="date" id="fecha_pago" name="fecha_pago" 
                                   value="<?php echo $action === "update" ? ($client->fecha_pago ?? '') : ''; ?>"
                                   class="field-input">
                        </div>
                        
                        <!-- Estado del Cliente -->
                        <div class="field-card">
                            <label for="estado" class="field-label">
                                Estado del Cliente <span class="field-required">*</span>
                            </label>
                            <select id="estado" name="estado" required class="field-input">
                                <option value="Activo" <?php echo ($action === "update" && ($client->estado ?? '') === "Activo") ? "selected" : ""; ?>>Activo</option>
                                <option value="Inactivo" <?php echo ($action === "update" && ($client->estado ?? '') === "Inactivo") ? "selected" : ""; ?>>Inactivo</option>
                            </select>
                        </div>
                        
                        <!-- Tipo de Plan -->
                        <div class="field-card">
                            <label for="id_plan" class="field-label">Tipo de Plan</label>
                            <select id="id_plan" name="id_plan" class="field-input">
                                <option value="">Seleccionar Plan</option>
                                <?php 
                                while($row = $planes_stmt->fetch(PDO::FETCH_ASSOC)) {
                                    extract($row);
                                    $selected = ($action === "update" && ($client->id_plan ?? '') == $id_plan) ? "selected" : "";
                                    echo "<option value='{$id_plan}' {$selected}>{$nombre_plan} - $" . number_format($precio, 2) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Empresa -->
                        <div class="field-card full-width">
                            <label for="id_empresa" class="field-label">Empresa</label>
                            <select id="id_empresa" name="id_empresa" class="field-input">
                                <option value="">Seleccionar Empresa</option>
                                <?php 
                                while($row = $empresas_stmt->fetch(PDO::FETCH_ASSOC)) {
                                    extract($row);
                                    $selected = ($action === "update" && ($client->id_empresa ?? '') == $id_empresa) ? "selected" : "";
                                    echo "<option value='{$id_empresa}' {$selected}>{$nombre_empresa} - {$rubro}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Ubicación Geográfica -->
                <div class="location-section">
                    <h3 class="section-title">
                        <i class="fas fa-map-marked-alt section-icon"></i>
                        Ubicación Geográfica
                    </h3>
                    
                    <div class="fields-grid">
                        <!-- País -->
                        <div class="field-card">
                            <label for="pais" class="field-label">
                                <i class="fas fa-globe" style="margin-right: 0.5rem; color: var(--primary-green);"></i>
                                País
                            </label>
                            <input type="text" id="pais" name="pais" 
                                   value="<?php echo $action === "update" && isset($client->pais) ? htmlspecialchars($client->pais) : ''; ?>"
                                   class="field-input" 
                                   placeholder="Ej: México, Colombia, Argentina...">
                            <small style="color: var(--medium-gray); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                Escriba el nombre del país
                            </small>
                        </div>
                        
                        <!-- Ubicación -->
                        <div class="field-card">
                            <label for="provincia" class="field-label">
                                <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--primary-green);"></i>
                                Ubicación (Estado/Provincia/Ciudad)
                            </label>
                            <input type="text" id="provincia" name="provincia" 
                                   value="<?php echo $action === "update" && isset($client->provincia) ? htmlspecialchars($client->provincia) : ''; ?>"
                                   class="field-input" 
                                   placeholder="Ej: Ciudad de México, Buenos Aires, Bogotá...">
                            <small style="color: var(--medium-gray); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                                Puede incluir estado, provincia, ciudad o región
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Red Social -->
                <div class="social-section">
                    <div class="social-header">
                        <h3 class="section-title" style="margin-bottom: 0;">
                            <i class="fas fa-share-alt section-icon" style="color: var(--black);"></i>
                            Agregar Red Social (Opcional)
                        </h3>
                        <button type="button" 
                                id="toggleSocialButton"
                                onclick="toggleSocialNetworkForm()" 
                                class="btn-minimal btn-black btn-toggle">
                            <i class="fas fa-plus"></i>Agregar Red Social
                        </button>
                    </div>
                    
                    <div id="social-network-form" class="social-form hidden fade-in">
                        <input type="hidden" name="add_social_network" id="add_social_network" value="0">
                        
                        <div class="fields-grid">
                            <div class="field-card">
                                <label for="tipo_red" class="field-label">
                                    Tipo de Red Social <span class="field-required">*</span>
                                </label>
                                <div class="flex-field">
                                    <select id="tipo_red" name="tipo_red" class="field-input" onchange="handleNetworkTypeChange()">
                                        <option value="">Seleccionar Tipo</option>
                                        <?php foreach($network_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['nombre_tipo']); ?>">
                                            <?php echo htmlspecialchars($type['nombre_tipo']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <option value="other">Otro (Personalizado)</option>
                                    </select>
                                    <button type="button" onclick="toggleNewNetworkType()" 
                                            class="btn-minimal btn-secondary" title="Agregar nuevo tipo">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <div id="new-network-type-container" class="hidden" style="margin-top: 0.5rem;">
                                    <input type="text" id="new_network_type" name="new_network_type" 
                                           class="field-input" placeholder="Nombre del nuevo tipo de red social">
                                </div>
                            </div>
                            
                            <div class="field-card">
                                <label for="nombre_red" class="field-label">
                                    Nombre de la Cuenta <span class="field-required">*</span>
                                </label>
                                <input type="text" id="nombre_red" name="nombre_red" 
                                       class="field-input" placeholder="Ej: Cuenta Principal, Cuenta Secundaria, etc.">
                            </div>
                            
                            <div class="field-card">
                                <label for="usuario_red" class="field-label">
                                    Usuario o Correo
                                </label>
                                <input type="text" id="usuario_red" name="usuario_red" 
                                       class="field-input" placeholder="Nombre de usuario o correo electrónico">
                            </div>
                            
                            <div class="field-card">
                                <label for="contrasena_red" class="field-label">
                                    Contraseña
                                </label>
                                <div class="password-field">
                                    <input type="password" id="contrasena_red" name="contrasena_red" 
                                           class="field-input" placeholder="Contraseña de la cuenta">
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('contrasena_red')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="field-card full-width">
                                <label for="url_red" class="field-label">
                                    URL (opcional)
                                </label>
                                <input type="url" id="url_red" name="url_red" 
                                       class="field-input" placeholder="https://ejemplo.com/perfil">
                            </div>
                            
                            <div class="field-card full-width">
                                <label for="notas" class="field-label">
                                    Notas (opcional)
                                </label>
                                <textarea id="notas" name="notas" rows="3" 
                                          class="field-input" style="resize: vertical;" 
                                          placeholder="Información adicional sobre esta cuenta..."></textarea>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-gray);">
                            <button type="button" onclick="clearSocialNetworkForm()" class="btn-minimal btn-secondary">
                                <i class="fas fa-eraser"></i>Limpiar
                            </button>
                            <button type="button" onclick="toggleSocialNetworkForm()" class="btn-minimal btn-black">
                                <i class="fas fa-times"></i>Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones del formulario -->
            <div class="form-actions">
                <div class="actions-content">
                    <div class="actions-row">
                        <a href="clients.php" class="btn-minimal btn-secondary">
                            <i class="fas fa-arrow-left"></i>Cancelar
                        </a>
                        
                        <div class="actions-group">
                            <button type="button" onclick="resetForm()" class="btn-minimal btn-secondary">
                                <i class="fas fa-undo"></i>Limpiar Formulario
                            </button>
                            
                            <button type="submit" class="btn-minimal btn-primary" id="saveButton">
                                <i class="fas fa-save"></i>
                                <?php echo $action === "create" ? "Crear Cliente" : "Actualizar Cliente"; ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-note">
                        <i class="fas fa-info-circle"></i>
                        Los campos marcados con <span class="field-required">*</span> son obligatorios. La fecha de pago es opcional.
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Variables globales para el manejo del formulario
    let socialFormVisible = false;
    let formSubmitted = false;

    // Función mejorada para mostrar/ocultar el formulario de red social
    function toggleSocialNetworkForm() {
        const form = document.getElementById('social-network-form');
        const addSocialNetwork = document.getElementById('add_social_network');
        const button = document.getElementById('toggleSocialButton');
        
        if (!socialFormVisible) {
            // Mostrar formulario
            form.classList.remove('hidden');
            setTimeout(() => {
                form.classList.add('active');
            }, 10);
            
            addSocialNetwork.value = '1';
            button.innerHTML = '<i class="fas fa-minus"></i>Ocultar Red Social';
            button.classList.add('active');
            socialFormVisible = true;
            
            // Focus en el primer campo
            setTimeout(() => {
                document.getElementById('tipo_red').focus();
            }, 300);
            
        } else {
            // Ocultar formulario
            form.classList.remove('active');
            setTimeout(() => {
                form.classList.add('hidden');
                clearSocialNetworkForm();
            }, 300);
            
            addSocialNetwork.value = '0';
            button.innerHTML = '<i class="fas fa-plus"></i>Agregar Red Social';
            button.classList.remove('active');
            socialFormVisible = false;
        }
    }
    
    // Función para limpiar el formulario de red social
    function clearSocialNetworkForm() {
        const fields = ['tipo_red', 'nombre_red', 'usuario_red', 'contrasena_red', 'url_red', 'notas', 'new_network_type'];
        
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = '';
            }
        });
        
        document.getElementById('new-network-type-container').classList.add('hidden');
        
        // Resetear el tipo de contraseña
        const passwordField = document.getElementById('contrasena_red');
        if (passwordField && passwordField.type === 'text') {
            passwordField.type = 'password';
            const eyeIcon = passwordField.parentElement.querySelector('i');
            if (eyeIcon) {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    }
    
    // Función para limpiar todo el formulario
    function resetForm() {
        if (confirm('¿Está seguro de que desea limpiar todos los campos del formulario?')) {
            document.getElementById('clientForm').reset();
            clearSocialNetworkForm();
            
            if (socialFormVisible) {
                toggleSocialNetworkForm();
            }
            
            // Resetear el estado del formulario
            formSubmitted = false;
            const saveButton = document.getElementById('saveButton');
            saveButton.innerHTML = '<i class="fas fa-save"></i><?php echo $action === "create" ? "Crear Cliente" : "Actualizar Cliente"; ?>';
            saveButton.disabled = false;
            saveButton.classList.remove('btn-loading');
        }
    }
    
    // Función mejorada para mostrar/ocultar contraseña
    function togglePasswordVisibility(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleButton = passwordInput.nextElementSibling;
        const eyeIcon = toggleButton.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }
    
    // Función para manejar nuevo tipo de red social
    function toggleNewNetworkType() {
        const container = document.getElementById('new-network-type-container');
        const tipoRedSelect = document.getElementById('tipo_red');
        
        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            tipoRedSelect.value = 'other';
            handleNetworkTypeChange();
            
            setTimeout(() => {
                document.getElementById('new_network_type').focus();
            }, 100);
        } else {
            container.classList.add('hidden');
            tipoRedSelect.value = '';
            document.getElementById('new_network_type').value = '';
        }
    }
    
    // Función para manejar cambio de tipo de red social
    function handleNetworkTypeChange() {
        const tipoRedSelect = document.getElementById('tipo_red');
        const newNetworkTypeContainer = document.getElementById('new-network-type-container');
        const nombreRedInput = document.getElementById('nombre_red');
        const selectedOption = tipoRedSelect.options[tipoRedSelect.selectedIndex];
        
        if (tipoRedSelect.value === 'other') {
            newNetworkTypeContainer.classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('new_network_type').focus();
            }, 100);
        } else {
            newNetworkTypeContainer.classList.add('hidden');
            document.getElementById('new_network_type').value = '';
            
            // Auto-llenar el nombre de la cuenta si está vacío
            if (nombreRedInput.value === '' && tipoRedSelect.value !== '') {
                nombreRedInput.value = selectedOption.text;
            }
        }
    }
    
    // Manejo del envío del formulario
    document.getElementById('clientForm').addEventListener('submit', function(e) {
        if (formSubmitted) {
            e.preventDefault();
            return false;
        }
        
        const saveButton = document.getElementById('saveButton');
        
        // Cambiar el botón para mostrar que se está procesando
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Guardando...';
        saveButton.disabled = true;
        saveButton.classList.add('btn-loading');
        
        const addSocialNetwork = document.getElementById('add_social_network').value;
        
        // Validar formulario de red social si está activo
        if (addSocialNetwork === '1') {
            const tipoRed = document.getElementById('tipo_red').value;
            const newNetworkType = document.getElementById('new_network_type').value;
            const nombreRed = document.getElementById('nombre_red').value;
            
            if ((tipoRed === '' || tipoRed === 'other') && newNetworkType === '') {
                e.preventDefault();
                alert('Por favor, seleccione o especifique un tipo de red social.');
                resetSubmitButton();
                return false;
            }
            
            if (nombreRed === '') {
                e.preventDefault();
                alert('Por favor, especifique un nombre para la cuenta de red social.');
                resetSubmitButton();
                return false;
            }
        }
        
        // Validar campos geográficos (opcional pero útil)
        const pais = document.getElementById('pais').value.trim();
        const provincia = document.getElementById('provincia').value.trim();
        
        if (pais && pais.length < 2) {
            e.preventDefault();
            alert('El nombre del país debe tener al menos 2 caracteres.');
            resetSubmitButton();
            return false;
        }
        
        if (provincia && provincia.length < 2) {
            e.preventDefault();
            alert('El nombre de la ubicación debe tener al menos 2 caracteres.');
            resetSubmitButton();
            return false;
        }
        
        formSubmitted = true;
    });
    
    // Función auxiliar para resetear el botón de envío
    function resetSubmitButton() {
        const saveButton = document.getElementById('saveButton');
        saveButton.innerHTML = '<i class="fas fa-save"></i><?php echo $action === "create" ? "Crear Cliente" : "Actualizar Cliente"; ?>';
        saveButton.disabled = false;
        saveButton.classList.remove('btn-loading');
        formSubmitted = false;
    }
    
    // Prevenir cierre accidental si hay datos en el formulario
    window.addEventListener('beforeunload', function(e) {
        const form = document.getElementById('clientForm');
        const formData = new FormData(form);
        let hasData = false;
        
        for (let [key, value] of formData.entries()) {
            if (value && value.toString().trim() !== '') {
                hasData = true;
                break;
            }
        }
        
        if (hasData && !formSubmitted) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // Inicialización cuando se carga la página
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-focus en el primer campo
        const firstField = document.getElementById('nombre_cliente');
        if (firstField && !firstField.value) {
            firstField.focus();
        }
        
        // Agregar efectos de hover a los campos
        const fieldCards = document.querySelectorAll('.field-card');
        fieldCards.forEach(card => {
            const input = card.querySelector('.field-input');
            if (input) {
                input.addEventListener('focus', function() {
                    card.style.borderColor = 'var(--primary-green)';
                });
                
                input.addEventListener('blur', function() {
                    card.style.borderColor = 'var(--border-gray)';
                });
            }
        });
    });
</script>

<?php
include 'includes/layout_footer.php';
?>
