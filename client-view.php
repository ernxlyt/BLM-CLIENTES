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

// Get all social network types for dropdown
$social_network_types = $social_network->getNetworkTypes();

// Process social network form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
  if(isset($_POST['action'])) {
      $action = $_POST['action'];
      
      // Create social network
      if($action === 'create_social_network') {
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
      
      // Update social network
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
.location-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #0ea5e9;
    border-radius: 12px;
}

.location-icon {
    color: #0ea5e9;
    font-size: 1.2em;
}

.location-badge {
    background-color: #e0f2fe;
    color: #0369a1;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    border: 1px solid #0ea5e9;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.success-message {
    background-color: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}
</style>

<?php if(isset($_GET['success']) && $_GET['success'] == '1'): ?>
<div class="success-message">
    <i class="fas fa-check-circle mr-2"></i>
    Cliente actualizado exitosamente.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Detalles del Cliente</h2>
        <?php if(isAdmin()): ?>
        <a href="client-form.php?id=<?php echo $client->id_cliente; ?>" class="btn btn-warning">
            <i class="fas fa-edit mr-2"></i> Editar
        </a>
        <?php endif; ?>
    </div>
    
    <div class="p-4">
        <!-- Información del Cliente en tarjetas -->
        <h3 class="text-lg font-semibold mb-4">Información del Cliente</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Nombre -->
            <div class="bg-white p-4 rounded-lg shadow-sm border">
                <h4 class="text-gray-700 font-medium mb-2">
                    <i class="fas fa-user mr-2 text-gray-500"></i>Nombre
                </h4>
                <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($client->nombre_cliente); ?></p>
            </div>
            
            <!-- Fecha de Inicio -->
            <div class="bg-white p-4 rounded-lg shadow-sm border">
                <h4 class="text-gray-700 font-medium mb-2">
                    <i class="fas fa-calendar-plus mr-2 text-gray-500"></i>Fecha de Inicio
                </h4>
                <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($client->fecha_inicio)); ?></p>
            </div>
            
            <!-- Cumpleaños -->
            <div class="bg-white p-4 rounded-lg shadow-sm border">
                <h4 class="text-gray-700 font-medium mb-2">
                    <i class="fas fa-birthday-cake mr-2 text-gray-500"></i>Cumpleaños
                </h4>
                <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($client->cumpleaños)); ?></p>
            </div>
            
            <!-- Fecha de Pago -->
            <div class="bg-white p-4 rounded-lg shadow-sm border">
                <h4 class="text-gray-700 font-medium mb-2">
                    <i class="fas fa-credit-card mr-2 text-gray-500"></i>Fecha de Pago
                </h4>
                <?php if (!empty($client->fecha_pago) && $client->fecha_pago !== '0000-00-00' && strtotime($client->fecha_pago) !== false): ?>
                    <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($client->fecha_pago)); ?></p>
                <?php else: ?>
                    <p class="text-gray-500 italic">
                        <i class="fas fa-calendar-times mr-2"></i>No disponible
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Estado -->
            <div class="bg-white p-4 rounded-lg shadow-sm border">
                <h4 class="text-gray-700 font-medium mb-2">
                    <i class="fas fa-toggle-on mr-2 text-gray-500"></i>Estado
                </h4>
                <?php if(strtolower($client->estado) === 'activo'): ?>
                    <span class="bg-green-500 text-white px-3 py-1 rounded-md text-sm">
                        <i class="fas fa-check-circle mr-1"></i>Activo
                    </span>
                <?php else: ?>  
                    <span class="bg-red-500 text-white px-3 py-1 rounded-md text-sm">
                        <i class="fas fa-times-circle mr-1"></i>Inactivo
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Plan -->
            <div class="bg-white p-4 rounded-lg shadow-sm border">
                <h4 class="text-gray-700 font-medium mb-2">
                    <i class="fas fa-box mr-2 text-gray-500"></i>Plan Asignado
                </h4>
                <p class="text-gray-900"><?php echo $client->nombre_plan ? htmlspecialchars($client->nombre_plan) : 'No asignado'; ?></p>
            </div>
            
            <!-- Empresa -->
            <div class="bg-white p-4 rounded-lg shadow-sm border col-span-1 md:col-span-3">
                <h4 class="text-gray-700 font-medium mb-2">
                    <i class="fas fa-building mr-2 text-gray-500"></i>Empresa
                </h4>
                <p class="text-gray-900">
                    <?php echo $client->nombre_empresa ? htmlspecialchars($client->nombre_empresa) . ' (' . htmlspecialchars($client->rubro_empresa) . ')' : 'No asignada'; ?>
                </p>
            </div>
        </div>

        <!-- Sección de Ubicación Geográfica -->
        <div class="location-card p-6 mb-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-map-marked-alt location-icon mr-3"></i>
                <h3 class="text-lg font-semibold text-gray-800">Ubicación Geográfica</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- País -->
                <div class="bg-white p-4 rounded-lg border border-blue-200">
                    <h4 class="text-gray-700 font-medium mb-3">
                        <i class="fas fa-globe location-icon mr-2"></i>País
                    </h4>
                    <?php if (!empty($client->pais)): ?>
                        <div class="location-badge">
                            <i class="fas fa-flag"></i>
                            <?php echo htmlspecialchars($client->pais); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">
                            <i class="fas fa-question-circle mr-2"></i>No especificado
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Ubicación (Provincia/Estado) -->
                <div class="bg-white p-4 rounded-lg border border-blue-200">
                    <h4 class="text-gray-700 font-medium mb-3">
                        <i class="fas fa-map-marker-alt location-icon mr-2"></i>Ubicación
                    </h4>
                    <?php if (!empty($client->provincia)): ?>
                        <div class="location-badge">
                            <i class="fas fa-map-pin"></i>
                            <?php echo htmlspecialchars($client->provincia); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">
                            <i class="fas fa-question-circle mr-2"></i>No especificado
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Información adicional de ubicación -->
            <?php if (!empty($client->pais) && !empty($client->provincia)): ?>
            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Ubicación completa:</strong> 
                    <?php echo htmlspecialchars($client->provincia) . ', ' . htmlspecialchars($client->pais); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Botón para editar ubicación (solo para administradores) -->
            <?php if(isAdmin()): ?>
            <div class="mt-4 text-center">
                <a href="client-form.php?id=<?php echo $client->id_cliente; ?>#location-section" 
                   class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Ubicación
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Redes Sociales -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-share-alt mr-2 text-gray-600"></i>Redes Sociales
                </h3>
                <button onclick="toggleForm('social-network-form')" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i> Agregar Red Social
                </button>
            </div>
            
            <!-- Social Network Form -->
            <div id="social-network-form" class="hidden bg-gray-50 p-4 rounded mb-4">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $client->id_cliente); ?>">
                    <input type="hidden" name="action" value="create_social_network">
                    
                    <!-- Modificar la sección del formulario de red social para permitir agregar nuevos tipos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="tipo_red" class="form-label">Tipo de Red Social</label>
                            <div class="flex">
                                <select id="tipo_red" name="tipo_red" required class="form-select flex-1" onchange="handleNetworkTypeChange()">
                                    <option value="">Seleccionar Tipo</option>
                                    <?php foreach($social_network_types as $type): ?>
                                    <option value="<?php echo $type['nombre_tipo']; ?>" data-icon="<?php echo $type['icono']; ?>" data-color="<?php echo $type['color']; ?>"><?php echo $type['nombre_tipo']; ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Otro (Personalizado)</option>
                                </select>
                                <button type="button" onclick="toggleNewNetworkType()" class="ml-2 btn btn-sm btn-secondary" title="Agregar nuevo tipo">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <!-- Campo para nuevo tipo de red social (inicialmente oculto) -->
                            <div id="new-network-type-container" class="hidden mt-2">
                                <input type="text" id="new_network_type" name="new_network_type" class="form-control" placeholder="Nombre del nuevo tipo de red social">
                            </div>
                        </div>
                        <div>
                            <label for="nombre_red" class="form-label">Nombre de la Cuenta</label>
                            <input type="text" id="nombre_red" name="nombre_red" required class="form-control" placeholder="Ej: Cuenta Principal, Cuenta Secundaria, etc.">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="usuario_red" class="form-label">Usuario o Correo</label>
                            <input type="text" id="usuario_red" name="usuario_red" required class="form-control" placeholder="Nombre de usuario o correo electrónico">
                        </div>
                        <div>
                            <label for="contrasena_red" class="form-label">Contraseña</label>
                            <div class="relative">
                                <input type="password" id="contrasena_red" name="contrasena_red" required class="form-control" placeholder="Contraseña de la cuenta">
                                <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center" onclick="togglePasswordVisibility('contrasena_red')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="url_red" class="form-label">URL (opcional)</label>
                        <input type="url" id="url_red" name="url_red" class="form-control" placeholder="https://ejemplo.com/perfil">
                    </div>
                    
                    <div class="form-group">
                        <label for="notas" class="form-label">Notas (opcional)</label>
                        <textarea id="notas" name="notas" rows="3" class="form-control" placeholder="Información adicional sobre esta cuenta"></textarea>
                    </div>
                    
                    <div class="flex justify-end mt-4">
                        <button type="button" onclick="toggleForm('social-network-form')" class="btn btn-secondary mr-2">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Social Networks List -->
            <?php if($social_networks_stmt->rowCount() > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php while($row = $social_networks_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                    <div class="p-4 border-b flex items-center">
                        <div class="flex-1">
                            <h4 class="font-bold"><?php echo htmlspecialchars($row['nombre_red']); ?></h4>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($row['tipo_red']); ?></p>
                        </div>
                        <div class="flex">
                            <button onclick="editSocialNetwork(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-icon btn-warning mr-1" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="red-social-delete.php?id=<?php echo $row['id_red']; ?>&client=true" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta red social?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="mb-2"><span class="font-semibold">Usuario:</span> <?php echo htmlspecialchars($row['usuario_red']); ?></p>
                        <p class="mb-2">
                            <span class="font-semibold">Contraseña:</span> 
                            <span id="password-hidden-<?php echo $row['id_red']; ?>">••••••••</span>
                            <span id="password-visible-<?php echo $row['id_red']; ?>" class="hidden"><?php echo htmlspecialchars($row['contrasena_red']); ?></span>
                            <button type="button" class="ml-2 text-blue-600 hover:text-blue-800" onclick="toggleStoredPassword(<?php echo $row['id_red']; ?>)">
                                <i id="eye-icon-<?php echo $row['id_red']; ?>" class="fas fa-eye"></i>
                            </button>
                        </p>
                        
                        <?php if(!empty($row['url_red'])): ?>
                        <p class="mb-2">
                            <span class="font-semibold">URL:</span> 
                            <a href="<?php echo htmlspecialchars($row['url_red']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($row['url_red']); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                        
                        <?php if(!empty($row['notas'])): ?>
                        <div class="mt-3 pt-3 border-t">
                            <p class="font-semibold">Notas:</p>
                            <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($row['notas'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <i class="fas fa-share-alt text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No hay redes sociales registradas para este cliente.</p>
                <button onclick="toggleForm('social-network-form')" class="mt-3 btn btn-primary">
                    <i class="fas fa-plus mr-2"></i> Agregar Primera Red Social
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Social Network Edit Form (Hidden) -->
            <div id="social-network-edit-form" class="hidden bg-gray-50 p-4 rounded mt-4">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $client->id_cliente); ?>">
                    <input type="hidden" name="action" value="update_social_network">
                    <input type="hidden" id="edit_id_red" name="id_red">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="edit_tipo_red" class="form-label">Tipo de Red Social</label>
                            <select id="edit_tipo_red" name="tipo_red" required class="form-select">
                                <option value="">Seleccionar Tipo</option>
                                <?php 
                                // Reset the pointer to the beginning of the array
                                foreach($social_network_types as $type): ?>
                                <option value="<?php echo $type['nombre_tipo']; ?>" data-icon="<?php echo $type['icono']; ?>" data-color="<?php echo $type['color']; ?>"><?php echo $type['nombre_tipo']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="edit_nombre_red" class="form-label">Nombre de la Cuenta</label>
                            <input type="text" id="edit_nombre_red" name="nombre_red" required class="form-control">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="edit_usuario_red" class="form-label">Usuario o Correo</label>
                            <input type="text" id="edit_usuario_red" name="usuario_red" required class="form-control">
                        </div>
                        <div>
                            <label for="edit_contrasena_red" class="form-label">Contraseña</label>
                            <div class="relative">
                                <input type="password" id="edit_contrasena_red" name="contrasena_red" required class="form-control">
                                <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center" onclick="togglePasswordVisibility('edit_contrasena_red')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_url_red" class="form-label">URL (opcional)</label>
                        <input type="url" id="edit_url_red" name="url_red" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_notas" class="form-label">Notas (opcional)</label>
                        <textarea id="edit_notas" name="notas" rows="3" class="form-control"></textarea>
                    </div>
                    
                    <div class="flex justify-end mt-4">
                        <button type="button" onclick="hideEditForm()" class="btn btn-secondary mr-2">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="flex justify-between mt-6">
            <a href="clients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
            
            <!-- Botones adicionales para administradores -->
            <?php if(isAdmin()): ?>
            <div class="flex gap-2">
                <a href="client-form.php?id=<?php echo $client->id_cliente; ?>" class="btn btn-warning">
                    <i class="fas fa-edit mr-2"></i>Editar Cliente
                </a>
                <a href="client-delete.php?id=<?php echo $client->id_cliente; ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('¿Está seguro de eliminar este cliente? Esta acción no se puede deshacer.')">
                    <i class="fas fa-trash mr-2"></i>Eliminar Cliente
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleForm(formId) {
        const form = document.getElementById(formId);
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
        // Populate the edit form
        document.getElementById('edit_id_red').value = socialNetwork.id_red;
        document.getElementById('edit_nombre_red').value = socialNetwork.nombre_red;
        document.getElementById('edit_usuario_red').value = socialNetwork.usuario_red;
        document.getElementById('edit_contrasena_red').value = socialNetwork.contrasena_red;
        document.getElementById('edit_url_red').value = socialNetwork.url_red || '';
        document.getElementById('edit_notas').value = socialNetwork.notas || '';
        
        // Select the correct option in the dropdown
        const tipoRedSelect = document.getElementById('edit_tipo_red');
        for (let i = 0; i < tipoRedSelect.options.length; i++) {
            if (tipoRedSelect.options[i].value === socialNetwork.tipo_red) {
                tipoRedSelect.selectedIndex = i;
                break;
            }
        }
        
        // Show the edit form
        document.getElementById('social-network-edit-form').classList.remove('hidden');
        
        // Scroll to the edit form
        document.getElementById('social-network-edit-form').scrollIntoView({ behavior: 'smooth' });
    }
    
    // Actualizar el nombre de la red social automáticamente cuando se selecciona un tipo
    document.getElementById('tipo_red').addEventListener('change', function() {
        const nombreRedInput = document.getElementById('nombre_red');
        const selectedOption = this.options[this.selectedIndex];
        
        // Solo actualizar si el campo está vacío o es igual al tipo anterior
        if (nombreRedInput.value === '' || nombreRedInput.value === this.getAttribute('data-previous-value')) {
            nombreRedInput.value = selectedOption.text;
        }
        
        // Guardar el valor actual para la próxima comparación
        this.setAttribute('data-previous-value', selectedOption.text);
    });

    // Agregar las funciones JavaScript para manejar el nuevo tipo de red social
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
        
        // Mostrar/ocultar el campo de nuevo tipo según la selección
        if (tipoRedSelect.value === 'other') {
            newNetworkTypeContainer.classList.remove('hidden');
            
            // Enfocar el campo de nuevo tipo
            setTimeout(() => {
                document.getElementById('new_network_type').focus();
            }, 100);
            
            // Actualizar el nombre de la red con el valor del nuevo tipo
            document.getElementById('new_network_type').addEventListener('input', function() {
                nombreRedInput.value = this.value;
            });
        } else {
            newNetworkTypeContainer.classList.add('hidden');
            
            // Solo actualizar si el campo está vacío o es igual al tipo anterior
            if (nombreRedInput.value === '' || nombreRedInput.value === tipoRedSelect.getAttribute('data-previous-value')) {
                nombreRedInput.value = selectedOption.text === 'Otro (Personalizado)' ? '' : selectedOption.text;
            }
        }
        
        // Guardar el valor actual para la próxima comparación
        tipoRedSelect.setAttribute('data-previous-value', selectedOption.text);
    }

    // Función para mostrar mensaje de éxito y ocultarlo después de unos segundos
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
// Include footer
include 'includes/layout_footer.php';
?>