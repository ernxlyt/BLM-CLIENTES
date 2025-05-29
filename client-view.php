<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Client.php';
include_once 'models/SocialNetwork.php';
include_once 'utils/session.php';

// Require login
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize client object
$client = new Client($db);
$social_network = new SocialNetwork($db);

// Check if ID is set
if(!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: clients.php");
  exit();
}

// Set client ID
$client->id_cliente = $_GET['id'];

// Check if client exists and user has access
if(!$client->readOne(getCurrentUserId(), isAdmin())) {
  header("Location: clients.php");
  exit();
}

// Get social networks for this client
$social_networks_stmt = $social_network->readByClient($client->id_cliente);
$social_network_types = $social_network->getNetworkTypes();
$usuarios_asignados = $client->getAssignedUsers();
$usuarios_disponibles = $client->getAvailableUsers();

// Process assignment form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // ========== PROCESAR NOTAS DEL CLIENTE ==========
        if($action === 'update_notes' && isAdmin()) {
            $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
            $client->notas = $notas;
            
            if ($client->update()) {
                header("Location: client-view.php?id=" . $client->id_cliente . "&success=notes");
                exit();
            } else {
                $error_message = "Error al actualizar las notas.";
            }
        }
        
        if($action === 'delete_notes' && isAdmin()) {
            $client->notas = '';
            
            if ($client->update()) {
                header("Location: client-view.php?id=" . $client->id_cliente . "&success=notes_deleted");
                exit();
            } else {
                $error_message = "Error al eliminar las notas.";
            }
        }
        
        // ========== PROCESAR ASIGNACIONES ==========
        else if($action === 'assign_user' && isAdmin()) {
            $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
            $tipo_servicio = isset($_POST['tipo_servicio']) ? trim($_POST['tipo_servicio']) : '';
            $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
            
            if ($id_usuario > 0 && !empty($tipo_servicio)) {
                if ($client->assignUser($id_usuario, $tipo_servicio, $notas)) {
                    header("Location: client-view.php?id=" . $client->id_cliente . "&success=assignment");
                    exit();
                } else {
                    $error_message = "Error al asignar el usuario.";
                }
            } else {
                $error_message = "Todos los campos son obligatorios.";
            }
        }
        
        else if($action === 'update_assignment' && isAdmin()) {
            $id_relacion = isset($_POST['id_relacion']) ? intval($_POST['id_relacion']) : 0;
            $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
            $tipo_servicio = isset($_POST['tipo_servicio']) ? trim($_POST['tipo_servicio']) : '';
            $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
            
            if ($id_relacion > 0 && $id_usuario > 0 && !empty($tipo_servicio)) {
                if ($client->updateAssignment($id_relacion, $id_usuario, $tipo_servicio, $notas)) {
                    header("Location: client-view.php?id=" . $client->id_cliente . "&success=update");
                    exit();
                } else {
                    $error_message = "Error al actualizar la asignación.";
                }
            } else {
                $error_message = "Todos los campos son obligatorios.";
            }
        }
        
        else if($action === 'remove_assignment' && isAdmin()) {
            $id_relacion = isset($_POST['id_relacion']) ? intval($_POST['id_relacion']) : 0;
            
            if ($id_relacion > 0) {
                if ($client->removeAssignment($id_relacion)) {
                    header("Location: client-view.php?id=" . $client->id_cliente . "&success=remove");
                    exit();
                } else {
                    $error_message = "Error al eliminar la asignación.";
                }
            } else {
                $error_message = "ID de relación inválido.";
            }
        }
        
        // ========== PROCESAR REDES SOCIALES ==========
        else if($action === 'create_social_network') {
            $social_network->id_cliente = $client->id_cliente;
            $social_network->tipo_red = isset($_POST['new_network_type']) && !empty($_POST['new_network_type']) ? $_POST['new_network_type'] : $_POST['tipo_red'];
            $social_network->nombre_red = $_POST['nombre_red'];
            $social_network->usuario_red = $_POST['usuario_red'];
            $social_network->contrasena_red = $_POST['contrasena_red'];
            $social_network->url_red = $_POST['url_red'];
            $social_network->notas = $_POST['notas'];
            
            if($social_network->create()) {
                header("Location: client-view.php?id=" . $client->id_cliente);
                exit();
            }
        }
        
        else if($action === 'update_social_network') {
            $social_network->id_red = $_POST['id_red'];
            $social_network->tipo_red = $_POST['tipo_red'];
            $social_network->nombre_red = $_POST['nombre_red'];
            $social_network->usuario_red = $_POST['usuario_red'];
            $social_network->contrasena_red = $_POST['contrasena_red'];
            $social_network->url_red = $_POST['url_red'];
            $social_network->notas = $_POST['notas'];
            
            if($social_network->update()) {
                header("Location: client-view.php?id=" . $client->id_cliente);
                exit();
            }
        }
    }
}

// Set page title
$page_title = "Detalles del Cliente";

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
.client-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Mensajes de estado */
.success-message {
    background-color: var(--light-green);
    border: 1px solid var(--primary-green);
    color: var(--dark-green);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

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

/* Cards principales */
.main-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header-minimal {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border-gray);
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title-minimal {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin: 0;
}

/* Grid de información del cliente */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    padding: 2rem;
}

.info-card {
    background: var(--white);
    border: 2px solid var(--border-gray);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-green);
}

.info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--black);
    border-radius: 12px 12px 0 0;
}

.info-card.accent-green::before {
    background: var(--primary-green);
}

.info-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.2rem;
    background: var(--black);
    color: var(--white);
}

.info-icon.green {
    background: var(--primary-green);
}

.info-title {
    font-weight: 600;
    color: var(--medium-gray);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-content {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark-gray);
    line-height: 1.4;
}

.info-content.secondary {
    color: var(--medium-gray);
    font-style: italic;
    font-weight: 400;
}

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-badge.active {
    background: var(--primary-green);
    color: var(--white);
}

.status-badge.inactive {
    background: var(--black);
    color: var(--white);
}

/* Sección de notas */
.notes-section {
    background: var(--light-gray);
    border: 2px solid var(--border-gray);
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
}

.notes-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-green);
    border-radius: 12px 12px 0 0;
}

.notes-content {
    background: var(--white);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1rem;
    font-size: 0.9rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 120px;
    overflow-y: auto;
}

.notes-empty {
    text-align: center;
    padding: 2rem;
    background: var(--white);
    border: 1px dashed var(--border-gray);
    border-radius: 8px;
    color: var(--medium-gray);
}

.notes-empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 1rem;
    background: var(--light-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--medium-gray);
}

/* Botones minimalistas */
.btn-minimal {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    font-size: 0.875rem;
    cursor: pointer;
}

.btn-minimal-primary {
    background: var(--primary-green);
    color: var(--white);
    border-color: var(--primary-green);
}

.btn-minimal-primary:hover {
    background: var(--dark-green);
    border-color: var(--dark-green);
    transform: translateY(-1px);
}

.btn-minimal-secondary {
    background: var(--white);
    color: var(--dark-gray);
    border-color: var(--border-gray);
}

.btn-minimal-secondary:hover {
    background: var(--light-gray);
    border-color: var(--medium-gray);
}

.btn-minimal-black {
    background: var(--black);
    color: var(--white);
    border-color: var(--black);
}

.btn-minimal-black:hover {
    background: var(--dark-gray);
    border-color: var(--dark-gray);
    transform: translateY(-1px);
}

.btn-minimal-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    justify-content: center;
}

/* Tablas minimalistas */
.table-minimal {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--white);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border-gray);
}

.table-minimal thead th {
    background: var(--light-gray);
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    color: var(--dark-gray);
    border-bottom: 1px solid var(--border-gray);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.table-minimal tbody td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-gray);
    color: var(--dark-gray);
    vertical-align: middle;
}

.table-minimal tbody tr:hover {
    background: var(--light-gray);
}

.table-minimal tbody tr:last-child td {
    border-bottom: none;
}

/* Avatar minimalista */
.avatar-minimal {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    background: var(--light-gray);
    color: var(--medium-gray);
    font-size: 1rem;
}

.avatar-minimal.green {
    background: var(--light-green);
    color: var(--primary-green);
}

.user-cell-minimal {
    display: flex;
    align-items: center;
    font-weight: 500;
}

/* Service badge */
.service-badge-minimal {
    background: var(--black);
    color: var(--white);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.service-badge-minimal.green {
    background: var(--primary-green);
}

/* Formularios */
.form-minimal {
    background: var(--light-gray);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.form-group-minimal {
    margin-bottom: 1rem;
}

.form-label-minimal {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--dark-gray);
    font-size: 0.875rem;
}

.form-control-minimal {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-gray);
    border-radius: 6px;
    background: var(--white);
    color: var(--dark-gray);
    transition: border-color 0.2s ease;
    font-size: 0.875rem;
}

.form-control-minimal:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

/* Empty states */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--medium-gray);
}

.empty-state-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: var(--light-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--medium-gray);
}

/* Responsive */
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        padding: 1rem;
    }
    
    .info-card {
        padding: 1rem;
    }
    
    .card-header-minimal {
        padding: 1rem 1.5rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .table-minimal thead th,
    .table-minimal tbody td {
        padding: 0.75rem 1rem;
    }
}

/* Animaciones sutiles */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.info-card {
    animation: fadeIn 0.3s ease-out;
}
</style>

<div class="client-container">
    <!-- Mensajes de estado -->
    <?php if(isset($_GET['success'])): ?>
    <div class="success-message">
        <i class="fas fa-check-circle"></i>
        <?php 
        switch($_GET['success']) {
            case 'notes':
                echo 'Notas actualizadas exitosamente.';
                break;
            case 'notes_deleted':
                echo 'Notas eliminadas exitosamente.';
                break;
            case 'assignment':
                echo 'Usuario asignado exitosamente.';
                break;
            case 'update':
                echo 'Asignación actualizada exitosamente.';
                break;
            case 'remove':
                echo 'Asignación eliminada exitosamente.';
                break;
            case '1':
                echo 'Cliente actualizado exitosamente.';
                break;
            default:
                echo 'Operación completada exitosamente.';
        }
        ?>
    </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
    <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- Card principal -->
    <div class="main-card">
        <div class="card-header-minimal">
            <h2 class="card-title-minimal">Detalles del Cliente</h2>
            <?php if(isAdmin()): ?>
            <a href="client-form.php?id=<?php echo $client->id_cliente; ?>" class="btn-minimal btn-minimal-black">
                <i class="fas fa-edit"></i> Editar
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Grid de información del cliente -->
        <div class="info-grid">
            <!-- Nombre -->
            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Nombre del Cliente</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php echo htmlspecialchars($client->nombre_cliente); ?>
                </div>
            </div>
            
            <!-- Fecha de Inicio -->
            <div class="info-card accent-green">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Fecha de Inicio</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php echo date('d/m/Y', strtotime($client->fecha_inicio)); ?>
                </div>
            </div>
            
            <!-- Cumpleaños -->
            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-birthday-cake"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Cumpleaños</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php echo date('d/m/Y', strtotime($client->cumpleaños)); ?>
                </div>
            </div>
            
            <!-- Fecha de Pago -->
            <div class="info-card accent-green">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Fecha de Pago</h4>
                    </div>
                </div>
                <div class="info-content <?php echo (empty($client->fecha_pago) || $client->fecha_pago === '0000-00-00' || strtotime($client->fecha_pago) === false) ? 'secondary' : ''; ?>">
                    <?php if (!empty($client->fecha_pago) && $client->fecha_pago !== '0000-00-00' && strtotime($client->fecha_pago) !== false): ?>
                        <?php echo date('d/m/Y', strtotime($client->fecha_pago)); ?>
                    <?php else: ?>
                        <i class="fas fa-calendar-times" style="margin-right: 0.5rem;"></i>No disponible
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Estado -->
            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-toggle-<?php echo strtolower($client->estado) === 'activo' ? 'on' : 'off'; ?>"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Estado del Cliente</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php if(strtolower($client->estado) === 'activo'): ?>
                        <span class="status-badge active">
                            <i class="fas fa-check-circle"></i>Activo
                        </span>
                    <?php else: ?>  
                        <span class="status-badge inactive">
                            <i class="fas fa-times-circle"></i>Inactivo
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Plan -->
            <div class="info-card accent-green">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Plan Asignado</h4>
                    </div>
                </div>
                <div class="info-content <?php echo !$client->nombre_plan ? 'secondary' : ''; ?>">
                    <?php echo $client->nombre_plan ? htmlspecialchars($client->nombre_plan) : 'No asignado'; ?>
                </div>
            </div>
            
            <!-- Empresa -->
            <div class="info-card" style="grid-column: span 2;">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Empresa Asociada</h4>
                    </div>
                </div>
                <div class="info-content <?php echo !$client->nombre_empresa ? 'secondary' : ''; ?>">
                    <?php echo $client->nombre_empresa ? htmlspecialchars($client->nombre_empresa) . ' (' . htmlspecialchars($client->rubro_empresa) . ')' : 'No asignada'; ?>
                </div>
            </div>

            <!-- Notas del Cliente -->
            <div class="notes-section" style="grid-column: span 2;">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Notas del Cliente</h4>
                    </div>
                </div>
                
                <?php if (!empty($client->notas)): ?>
                    <div class="notes-content">
                        <?php echo nl2br(htmlspecialchars($client->notas)); ?>
                    </div>
                    <?php if (isAdmin()): ?>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <button onclick="toggleNotesForm()" class="btn-minimal btn-minimal-primary">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button onclick="deleteNotes()" class="btn-minimal btn-minimal-black">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="notes-empty">
                        <div class="notes-empty-icon">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <p style="margin: 0 0 1rem 0; font-weight: 500;">Sin notas registradas</p>
                        <?php if (isAdmin()): ?>
                        <button onclick="toggleNotesForm()" class="btn-minimal btn-minimal-primary">
                            <i class="fas fa-plus"></i> Agregar Nota
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario de Edición de Notas -->
                <?php if (isAdmin()): ?>
                <div id="notes-form" class="form-minimal hidden">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $client->id_cliente); ?>">
                        <input type="hidden" name="action" value="update_notes">
                        
                        <div class="form-group-minimal">
                            <textarea class="form-control-minimal" name="notas" rows="4" 
                                      placeholder="Escriba las notas del cliente..."><?php echo htmlspecialchars($client->notas ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                            <button type="button" onclick="toggleNotesForm()" class="btn-minimal btn-minimal-secondary">
                                Cancelar
                            </button>
                            <button type="submit" class="btn-minimal btn-minimal-primary">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Usuarios Asignados -->
    <div class="main-card">
        <div class="card-header-minimal">
            <h3 class="card-title-minimal">
                <i class="fas fa-users" style="margin-right: 0.5rem; color: var(--primary-green);"></i>
                Usuarios Asignados
            </h3>
            <?php if (isAdmin()): ?>
            <button onclick="toggleForm('assignment-form')" class="btn-minimal btn-minimal-primary">
                <i class="fas fa-plus"></i> Asignar Usuario
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Formulario de Asignación -->
        <?php if (isAdmin()): ?>
        <div id="assignment-form" class="hidden" style="padding: 0 2rem;">
            <div class="form-minimal">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $client->id_cliente); ?>">
                    <input type="hidden" name="action" value="assign_user">
                    <input type="hidden" name="id_relacion" id="id_relacion" value="">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group-minimal">
                            <label for="id_usuario" class="form-label-minimal">Usuario:</label>
                            <select class="form-control-minimal" id="id_usuario" name="id_usuario" required>
                                <option value="">Seleccione un usuario</option>
                                <?php foreach ($usuarios_disponibles as $usuario): ?>
                                <option value="<?php echo $usuario['id_usuario']; ?>">
                                    <?php echo htmlspecialchars($usuario['nombre_usuario']); ?> 
                                    (<?php echo htmlspecialchars($usuario['correo_usuario']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group-minimal">
                            <label for="tipo_servicio" class="form-label-minimal">Tipo de Servicio:</label>
                            <input type="text" class="form-control-minimal" id="tipo_servicio" name="tipo_servicio" 
                                   placeholder="Ej: Social Media, Diseño de Stories, etc." required>
                        </div>
                    </div>
                    
                    <div class="form-group-minimal">
                        <label for="notas_asignacion" class="form-label-minimal">Notas de la Asignación:</label>
                        <textarea class="form-control-minimal" id="notas_asignacion" name="notas" rows="3" 
                                  placeholder="Detalles adicionales sobre el servicio..."></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" onclick="toggleForm('assignment-form')" class="btn-minimal btn-minimal-secondary">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-minimal btn-minimal-primary">
                            <span id="form-submit-text">Asignar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Lista de Usuarios Asignados -->
        <?php if (empty($usuarios_asignados)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-users"></i>
                </div>
                <p>No hay usuarios asignados a este cliente.</p>
                <?php if (isAdmin()): ?>
                <button onclick="toggleForm('assignment-form')" class="btn-minimal btn-minimal-primary" style="margin-top: 1rem;">
                    <i class="fas fa-plus"></i> Asignar Primer Usuario
                </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="padding: 0 2rem 2rem;">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Servicio</th>
                            <th>Fecha Asignación</th>
                            <th>Notas</th>
                            <?php if (isAdmin()): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_asignados as $asignacion): ?>
                        <tr>
                            <td>
                                <div class="user-cell-minimal">
                                    <div class="avatar-minimal green">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: var(--dark-gray);">
                                            <?php echo htmlspecialchars($asignacion['nombre_usuario']); ?>
                                        </div>
                                        <div style="font-size: 0.875rem; color: var(--medium-gray);">
                                            <?php echo htmlspecialchars($asignacion['correo_usuario']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="service-badge-minimal green">
                                    <i class="fas fa-cog"></i>
                                    <?php echo htmlspecialchars($asignacion['tipo_servicio']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($asignacion['fecha_asignacion'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($asignacion['notas'])); ?></td>
                            <?php if (isAdmin()): ?>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button onclick="editAssignment(<?php echo htmlspecialchars(json_encode($asignacion)); ?>)" 
                                            class="btn-minimal btn-minimal-icon btn-minimal-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta asignación?')">
                                        <input type="hidden" name="action" value="remove_assignment">
                                        <input type="hidden" name="id_relacion" value="<?php echo $asignacion['id_relacion']; ?>">
                                        <button type="submit" class="btn-minimal btn-minimal-icon btn-minimal-black" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Redes Sociales -->
    <div class="main-card">
        <div class="card-header-minimal">
            <h3 class="card-title-minimal">
                <i class="fas fa-share-alt" style="margin-right: 0.5rem; color: var(--medium-gray);"></i>
                Redes Sociales
            </h3>
            <button onclick="toggleForm('social-network-form')" class="btn-minimal btn-minimal-primary">
                <i class="fas fa-plus"></i> Agregar Red Social
            </button>
        </div>
        
        <!-- Social Network Form -->
        <div id="social-network-form" class="hidden" style="padding: 0 2rem;">
            <div class="form-minimal">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $client->id_cliente); ?>">
                    <input type="hidden" name="action" value="create_social_network">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group-minimal">
                            <label for="tipo_red" class="form-label-minimal">Tipo de Red Social</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <select id="tipo_red" name="tipo_red" required class="form-control-minimal" style="flex: 1;" onchange="handleNetworkTypeChange()">
                                    <option value="">Seleccionar Tipo</option>
                                    <?php foreach($social_network_types as $type): ?>
                                    <option value="<?php echo $type['nombre_tipo']; ?>"><?php echo $type['nombre_tipo']; ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Otro (Personalizado)</option>
                                </select>
                                <button type="button" onclick="toggleNewNetworkType()" class="btn-minimal btn-minimal-secondary" title="Agregar nuevo tipo">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <div id="new-network-type-container" class="hidden" style="margin-top: 0.5rem;">
                                <input type="text" id="new_network_type" name="new_network_type" class="form-control-minimal" placeholder="Nombre del nuevo tipo de red social">
                            </div>
                        </div>
                        <div class="form-group-minimal">
                            <label for="nombre_red" class="form-label-minimal">Nombre de la Cuenta</label>
                            <input type="text" id="nombre_red" name="nombre_red" required class="form-control-minimal" placeholder="Ej: Cuenta Principal, Cuenta Secundaria, etc.">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group-minimal">
                            <label for="usuario_red" class="form-label-minimal">Usuario o Correo</label>
                            <input type="text" id="usuario_red" name="usuario_red" required class="form-control-minimal" placeholder="Nombre de usuario o correo electrónico">
                        </div>
                        <div class="form-group-minimal">
                            <label for="contrasena_red" class="form-label-minimal">Contraseña</label>
                            <div style="position: relative;">
                                <input type="password" id="contrasena_red" name="contrasena_red" required class="form-control-minimal" placeholder="Contraseña de la cuenta">
                                <button type="button" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--medium-gray);" onclick="togglePasswordVisibility('contrasena_red')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group-minimal">
                        <label for="url_red" class="form-label-minimal">URL (opcional)</label>
                        <input type="url" id="url_red" name="url_red" class="form-control-minimal" placeholder="https://ejemplo.com/perfil">
                    </div>
                    
                    <div class="form-group-minimal">
                        <label for="notas_red" class="form-label-minimal">Notas (opcional)</label>
                        <textarea id="notas_red" name="notas" rows="3" class="form-control-minimal" placeholder="Información adicional sobre esta cuenta"></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" onclick="toggleForm('social-network-form')" class="btn-minimal btn-minimal-secondary">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-minimal btn-minimal-primary">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Social Networks List -->
        <?php if($social_networks_stmt->rowCount() > 0): ?>
        <div style="padding: 0 2rem 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php while($row = $social_networks_stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <div style="background: var(--white); border: 1px solid var(--border-gray); border-radius: 8px; overflow: hidden;">
                <div style="padding: 1rem; border-bottom: 1px solid var(--border-gray); display: flex; align-items: center; justify-content: space-between; background: var(--light-gray);">
                    <div>
                        <h4 style="margin: 0; font-weight: 600; color: var(--dark-gray);"><?php echo htmlspecialchars($row['nombre_red']); ?></h4>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--medium-gray);"><?php echo htmlspecialchars($row['tipo_red']); ?></p>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="editSocialNetwork(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn-minimal btn-minimal-icon btn-minimal-secondary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="red-social-delete.php?id=<?php echo $row['id_red']; ?>&client=true" class="btn-minimal btn-minimal-icon btn-minimal-black" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta red social?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div style="padding: 1rem;">
                    <p style="margin-bottom: 0.5rem;"><span style="font-weight: 500;">Usuario:</span> <?php echo htmlspecialchars($row['usuario_red']); ?></p>
                    <p style="margin-bottom: 0.5rem;">
                        <span style="font-weight: 500;">Contraseña:</span> 
                        <span id="password-hidden-<?php echo $row['id_red']; ?>">••••••••</span>
                        <span id="password-visible-<?php echo $row['id_red']; ?>" class="hidden"><?php echo htmlspecialchars($row['contrasena_red']); ?></span>
                        <button type="button" style="margin-left: 0.5rem; background: none; border: none; color: var(--primary-green); cursor: pointer;" onclick="toggleStoredPassword(<?php echo $row['id_red']; ?>)">
                            <i id="eye-icon-<?php echo $row['id_red']; ?>" class="fas fa-eye"></i>
                        </button>
                    </p>
                    
                    <?php if(!empty($row['url_red'])): ?>
                    <p style="margin-bottom: 0.5rem;">
                        <span style="font-weight: 500;">URL:</span> 
                        <a href="<?php echo htmlspecialchars($row['url_red']); ?>" target="_blank" style="color: var(--primary-green); text-decoration: none;">
                            <?php echo htmlspecialchars($row['url_red']); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <?php if(!empty($row['notas'])): ?>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-gray);">
                        <p style="font-weight: 500; margin-bottom: 0.5rem;">Notas:</p>
                        <p style="font-size: 0.875rem; color: var(--medium-gray); margin: 0;"><?php echo nl2br(htmlspecialchars($row['notas'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-share-alt"></i>
            </div>
            <p>No hay redes sociales registradas para este cliente.</p>
            <button onclick="toggleForm('social-network-form')" class="btn-minimal btn-minimal-primary" style="margin-top: 1rem;">
                <i class="fas fa-plus"></i> Agregar Primera Red Social
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Social Network Edit Form (Hidden) -->
        <div id="social-network-edit-form" class="hidden" style="padding: 0 2rem 2rem;">
            <div class="form-minimal">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $client->id_cliente); ?>">
                    <input type="hidden" name="action" value="update_social_network">
                    <input type="hidden" id="edit_id_red" name="id_red">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group-minimal">
                            <label for="edit_tipo_red" class="form-label-minimal">Tipo de Red Social</label>
                            <select id="edit_tipo_red" name="tipo_red" required class="form-control-minimal">
                                <option value="">Seleccionar Tipo</option>
                                <?php foreach($social_network_types as $type): ?>
                                <option value="<?php echo $type['nombre_tipo']; ?>"><?php echo $type['nombre_tipo']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group-minimal">
                            <label for="edit_nombre_red" class="form-label-minimal">Nombre de la Cuenta</label>
                            <input type="text" id="edit_nombre_red" name="nombre_red" required class="form-control-minimal">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group-minimal">
                            <label for="edit_usuario_red" class="form-label-minimal">Usuario o Correo</label>
                            <input type="text" id="edit_usuario_red" name="usuario_red" required class="form-control-minimal">
                        </div>
                        <div class="form-group-minimal">
                            <label for="edit_contrasena_red" class="form-label-minimal">Contraseña</label>
                            <div style="position: relative;">
                                <input type="password" id="edit_contrasena_red" name="contrasena_red" required class="form-control-minimal">
                                <button type="button" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--medium-gray);" onclick="togglePasswordVisibility('edit_contrasena_red')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group-minimal">
                        <label for="edit_url_red" class="form-label-minimal">URL (opcional)</label>
                        <input type="url" id="edit_url_red" name="url_red" class="form-control-minimal">
                    </div>
                    
                    <div class="form-group-minimal">
                        <label for="edit_notas_red" class="form-label-minimal">Notas (opcional)</label>
                        <textarea id="edit_notas_red" name="notas" rows="3" class="form-control-minimal"></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" onclick="hideEditForm()" class="btn-minimal btn-minimal-secondary">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-minimal btn-minimal-primary">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Botones de navegación -->
    <div style="display: flex; justify-content: space-between; margin-top: 2rem;">
        <a href="clients.php" class="btn-minimal btn-minimal-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        
        <?php if(isAdmin()): ?>
        <div style="display: flex; gap: 0.5rem;">
            <a href="client-form.php?id=<?php echo $client->id_cliente; ?>" class="btn-minimal btn-minimal-primary">
                <i class="fas fa-edit"></i> Editar Cliente
            </a>
            <a href="client-delete.php?id=<?php echo $client->id_cliente; ?>" 
               class="btn-minimal btn-minimal-black" 
               onclick="return confirm('¿Está seguro de eliminar este cliente? Esta acción no se puede deshacer.')">
                <i class="fas fa-trash"></i> Eliminar Cliente
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleForm(formId) {
        const form = document.getElementById(formId);
        const isHidden = form.classList.contains('hidden');
        
        if (isHidden && formId === 'assignment-form') {
            form.querySelector('form').reset();
            document.querySelector('input[name="action"]').value = 'assign_user';
            document.getElementById('id_relacion').value = '';
            document.getElementById('form-submit-text').textContent = 'Asignar';
        }
        
        form.classList.toggle('hidden');
    }
    
    function hideEditForm() {
        document.getElementById('social-network-edit-form').classList.add('hidden');
    }
    
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
    
    function toggleStoredPassword(id) {
        const hiddenPassword = document.getElementById('password-hidden-' + id);
        const visiblePassword = document.getElementById('password-visible-' + id);
        const eyeIcon = document.getElementById('eye-icon-' + id);
        
        if (hiddenPassword.classList.contains('hidden')) {
            hiddenPassword.classList.remove('hidden');
            visiblePassword.classList.add('hidden');
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        } else {
            hiddenPassword.classList.add('hidden');
            visiblePassword.classList.remove('hidden');
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        }
    }
    
    function editSocialNetwork(socialNetwork) {
        document.getElementById('edit_id_red').value = socialNetwork.id_red;
        document.getElementById('edit_nombre_red').value = socialNetwork.nombre_red;
        document.getElementById('edit_usuario_red').value = socialNetwork.usuario_red;
        document.getElementById('edit_contrasena_red').value = socialNetwork.contrasena_red;
        document.getElementById('edit_url_red').value = socialNetwork.url_red || '';
        document.getElementById('edit_notas_red').value = socialNetwork.notas || '';
        
        const tipoRedSelect = document.getElementById('edit_tipo_red');
        for (let i = 0; i < tipoRedSelect.options.length; i++) {
            if (tipoRedSelect.options[i].value === socialNetwork.tipo_red) {
                tipoRedSelect.selectedIndex = i;
                break;
            }
        }
        
        document.getElementById('social-network-edit-form').classList.remove('hidden');
        document.getElementById('social-network-edit-form').scrollIntoView({ behavior: 'smooth' });
    }

    function editAssignment(assignment) {
        document.querySelector('input[name="action"]').value = 'update_assignment';
        document.getElementById('id_relacion').value = assignment.id_relacion;
        document.getElementById('id_usuario').value = assignment.id_usuario;
        document.getElementById('tipo_servicio').value = assignment.tipo_servicio;
        document.getElementById('notas_asignacion').value = assignment.notas || '';
        document.getElementById('form-submit-text').textContent = 'Actualizar';
        
        document.getElementById('assignment-form').classList.remove('hidden');
        document.getElementById('assignment-form').scrollIntoView({ behavior: 'smooth' });
    }

    function toggleNotesForm() {
        const form = document.getElementById('notes-form');
        form.classList.toggle('hidden');
        
        if (!form.classList.contains('hidden')) {
            const textarea = form.querySelector('textarea');
            textarea.focus();
        }
    }
    
    function deleteNotes() {
        if (confirm('¿Estás seguro de que deseas eliminar las notas de este cliente?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_notes';
            
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    document.getElementById('tipo_red').addEventListener('change', function() {
        const nombreRedInput = document.getElementById('nombre_red');
        const selectedOption = this.options[this.selectedIndex];
        
        if (nombreRedInput.value === '' || nombreRedInput.value === this.getAttribute('data-previous-value')) {
            nombreRedInput.value = selectedOption.text;
        }
        
        this.setAttribute('data-previous-value', selectedOption.text);
    });

    function toggleNewNetworkType() {
        const container = document.getElementById('new-network-type-container');
        const tipoRedSelect = document.getElementById('tipo_red');
        
        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            tipoRedSelect.value = 'other';
            handleNetworkTypeChange();
        } else {
            container.classList.add('hidden');
            tipoRedSelect.value = '';
        }
    }

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
            
            document.getElementById('new_network_type').addEventListener('input', function() {
                nombreRedInput.value = this.value;
            });
        } else {
            newNetworkTypeContainer.classList.add('hidden');
            
            if (nombreRedInput.value === '' || nombreRedInput.value === tipoRedSelect.getAttribute('data-previous-value')) {
                nombreRedInput.value = selectedOption.text === 'Otro (Personalizado)' ? '' : selectedOption.text;
            }
        }
        
        tipoRedSelect.setAttribute('data-previous-value', selectedOption.text);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 300);
            }, 5000);
        }
    });
</script>

<?php
include 'includes/layout_footer.php';
?>
