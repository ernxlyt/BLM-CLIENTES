<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'models/Client.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$client = new Client($db);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user->id_usuario = $_GET['id'];

if (!$user->readOne()) {
    header("Location: users.php");
    exit();
}

// ========== PROCESAR FORMULARIOS ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'assign_client') {
        $id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
        $tipo_servicio = isset($_POST['tipo_servicio']) ? trim($_POST['tipo_servicio']) : '';
        $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
        
        if ($id_cliente > 0 && !empty($tipo_servicio)) {
            if ($user->assignToClient($id_cliente, $tipo_servicio, $notas)) {
                header("Location: user-view.php?id=" . $user->id_usuario . "&success=assignment");
                exit();
            } else {
                $error_message = "Error al asignar el cliente.";
            }
        } else {
            $error_message = "Cliente y tipo de servicio son obligatorios.";
        }
    }
    
    else if ($action === 'update_assignment') {
        $id_relacion = isset($_POST['id_relacion']) ? intval($_POST['id_relacion']) : 0;
        $id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
        $tipo_servicio = isset($_POST['tipo_servicio']) ? trim($_POST['tipo_servicio']) : '';
        $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
        
        if ($id_relacion > 0 && $id_cliente > 0 && !empty($tipo_servicio)) {
            if ($user->updateAssignment($id_relacion, $id_cliente, $tipo_servicio, $notas)) {
                header("Location: user-view.php?id=" . $user->id_usuario . "&success=update");
                exit();
            } else {
                $error_message = "Error al actualizar la asignación.";
            }
        } else {
            $error_message = "Todos los campos son obligatorios.";
        }
    }
    
    else if ($action === 'remove_assignment') {
        $id_relacion = isset($_POST['id_relacion']) ? intval($_POST['id_relacion']) : 0;
        
        if ($id_relacion > 0) {
            if ($user->removeAssignment($id_relacion)) {
                header("Location: user-view.php?id=" . $user->id_usuario . "&success=remove");
                exit();
            } else {
                $error_message = "Error al eliminar la asignación.";
            }
        } else {
            $error_message = "ID de relación inválido.";
        }
    }
}

// Obtener datos para mostrar
$assigned_clients = $user->getAssignedClients();
$available_clients = $user->getAvailableClients();
$stats = $user->getAssignmentStats();

$page_title = "Detalles del Usuario";

include 'includes/layout_header.php';
?>

<style>
.stats-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #0ea5e9;
    border-radius: 12px;
}

.assignment-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #22c55e;
    border-radius: 12px;
}

.service-badge {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.success-message {
    background-color: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.error-message {
    background-color: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}
</style>

<?php if(isset($_GET['success'])): ?>
<div class="success-message">
    <i class="fas fa-check-circle mr-2"></i>
    <?php 
    switch($_GET['success']) {
        case 'assignment':
            echo 'Cliente asignado exitosamente.';
            break;
        case 'update':
            echo 'Asignación actualizada exitosamente.';
            break;
        case 'remove':
            echo 'Asignación eliminada exitosamente.';
            break;
        default:
            echo 'Operación completada exitosamente.';
    }
    ?>
</div>
<?php endif; ?>

<?php if(isset($error_message)): ?>
<div class="error-message">
    <i class="fas fa-exclamation-circle mr-2"></i>
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Detalles del Usuario</h2>
        <a href="user-form.php?id=<?php echo $user->id_usuario; ?>" class="btn btn-warning">
            <i class="fas fa-edit mr-2"></i> Editar
        </a>
    </div>
    
    <div class="p-4">
        <!-- Información General -->
        <div class="bg-gray-50 p-4 rounded mb-6">
            <h3 class="text-lg font-semibold mb-4">Información General</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="mb-2"><span class="font-bold">Nombre:</span> <?php echo htmlspecialchars($user->nombre_usuario); ?></p>
                    <p class="mb-2"><span class="font-bold">Correo:</span> <?php echo htmlspecialchars($user->correo_usuario); ?></p>
                </div>
                <div>
                    <p class="mb-2"><span class="font-bold">Rol:</span> <?php echo htmlspecialchars($user->nombre_rol); ?></p>
                    <p class="mb-2"><span class="font-bold">Contraseña:</span> 
                        <input type="password" id="passwordField" value="<?php echo htmlspecialchars($user->contrasena); ?>" readonly class="inline-block w-32 px-2 py-1 border rounded">
                        <button type="button" id="toggleBtn" class="ml-2 text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i> Mostrar
                        </button>
                    </p>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Asignaciones -->
        <div class="stats-card p-6 mb-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-chart-bar text-blue-600 mr-3 text-xl"></i>
                <h3 class="text-lg font-semibold text-gray-800">Estadísticas de Asignaciones</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-lg border border-blue-200 text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $stats['total_clientes']; ?></div>
                    <div class="text-sm text-gray-600">Clientes Asignados</div>
                </div>
                <div class="bg-white p-4 rounded-lg border border-blue-200 text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $stats['total_servicios']; ?></div>
                    <div class="text-sm text-gray-600">Total Servicios</div>
                </div>
                <div class="bg-white p-4 rounded-lg border border-blue-200 text-center">
                    <div class="text-2xl font-bold text-purple-600"><?php echo $stats['tipos_servicio_unicos']; ?></div>
                    <div class="text-sm text-gray-600">Tipos de Servicio</div>
                </div>
            </div>
        </div>

        <!-- Clientes Asignados con Servicios -->
        <div class="assignment-card p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <i class="fas fa-users text-green-600 mr-3 text-xl"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Clientes Asignados</h3>
                </div>
                <button onclick="toggleForm('assignment-form')" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i> Asignar Cliente
                </button>
            </div>
            
            <!-- Formulario de Asignación -->
            <div id="assignment-form" class="hidden bg-white p-4 rounded-lg border border-green-200 mb-4">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $user->id_usuario); ?>">
                    <input type="hidden" name="action" value="assign_client">
                    <input type="hidden" name="id_relacion" id="id_relacion" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="id_cliente" class="form-label">Cliente:</label>
                            <select class="form-control" id="id_cliente" name="id_cliente" required>
                                <option value="">Seleccione un cliente</option>
                                <?php foreach ($available_clients as $cliente): ?>
                                <option value="<?php echo $cliente['id_cliente']; ?>">
                                    <?php echo htmlspecialchars($cliente['nombre_cliente']); ?>
                                    <?php if ($cliente['nombre_empresa']): ?>
                                        (<?php echo htmlspecialchars($cliente['nombre_empresa']); ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="tipo_servicio" class="form-label">Tipo de Servicio:</label>
                            <input type="text" class="form-control" id="tipo_servicio" name="tipo_servicio" 
                                   placeholder="Ej: Social Media, Diseño de Stories, etc." required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notas" class="form-label">Notas:</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3" 
                                  placeholder="Detalles adicionales sobre el servicio..."></textarea>
                    </div>
                    
                    <div class="flex justify-end mt-4">
                        <button type="button" onclick="toggleForm('assignment-form')" class="btn btn-secondary mr-2">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span id="form-submit-text">Asignar</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de Clientes Asignados -->
            <?php if (empty($assigned_clients)): ?>
                <div class="text-center py-8 bg-white rounded-lg border border-green-200">
                    <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No hay clientes asignados a este usuario.</p>
                    <button onclick="toggleForm('assignment-form')" class="mt-3 btn btn-primary">
                        <i class="fas fa-plus mr-2"></i> Asignar Primer Cliente
                    </button>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg border border-green-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Asignación</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($assigned_clients as $asignacion): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i class="fas fa-user text-blue-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($asignacion['nombre_cliente']); ?>
                                                </div>
                                                <?php if ($asignacion['nombre_empresa']): ?>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($asignacion['nombre_empresa']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="service-badge">
                                            <i class="fas fa-cog"></i>
                                            <?php echo htmlspecialchars($asignacion['tipo_servicio']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($asignacion['fecha_asignacion'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo nl2br(htmlspecialchars($asignacion['notas'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="client-view.php?id=<?php echo $asignacion['id_cliente']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver Cliente">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="editAssignment(<?php echo htmlspecialchars(json_encode($asignacion)); ?>)" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta asignación?')">
                                            <input type="hidden" name="action" value="remove_assignment">
                                            <input type="hidden" name="id_relacion" value="<?php echo $asignacion['id_relacion']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botón para Volver -->
        <div class="flex justify-between mt-6">
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById("toggleBtn").addEventListener("click", function() {
        var field = document.getElementById("passwordField");
        var icon = this.querySelector('i');
        
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            this.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar';
        } else {
            field.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            this.innerHTML = '<i class="fas fa-eye"></i> Mostrar';
        }
    });

    // Toggle assignment form
    function toggleForm(formId) {
        const form = document.getElementById(formId);
        const isHidden = form.classList.contains('hidden');
        
        if (isHidden) {
            // Resetear formulario al mostrar
            form.querySelector('form').reset();
            document.querySelector('input[name="action"]').value = 'assign_client';
            document.getElementById('id_relacion').value = '';
            document.getElementById('form-submit-text').textContent = 'Asignar';
        }
        
        form.classList.toggle('hidden');
    }

    // Edit assignment
    function editAssignment(assignment) {
        // Cambiar el formulario a modo edición
        document.querySelector('input[name="action"]').value = 'update_assignment';
        document.getElementById('id_relacion').value = assignment.id_relacion;
        document.getElementById('id_cliente').value = assignment.id_cliente;
        document.getElementById('tipo_servicio').value = assignment.tipo_servicio;
        document.getElementById('notas').value = assignment.notas || '';
        document.getElementById('form-submit-text').textContent = 'Actualizar';
        
        // Mostrar el formulario
        document.getElementById('assignment-form').classList.remove('hidden');
        
        // Scroll al formulario
        document.getElementById('assignment-form').scrollIntoView({ behavior: 'smooth' });
    }

    // Auto-hide success messages
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