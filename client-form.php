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
requireAdmin(); // Solo administradores pueden acceder a este formulario

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
    
    // Check if client exists and user has access
    if($client->readOne(getCurrentUserId(), isAdmin())) {
        $page_title = "Editar Cliente";
        $action = "update";
    } else {
        // Redirect if client not found or no access
        header("Location: clients.php");
        exit();
    }
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set client properties
    $client->nombre_cliente = $_POST['nombre_cliente'];
    $client->fecha_inicio = $_POST['fecha_inicio'];
    $client->cumpleaños = $_POST['cumpleaños'];
    $client->fecha_pago = $_POST['fecha_pago'];
    $client->estado = $_POST['estado'];
    $client->id_plan = $_POST['id_plan'];
    $client->id_empresa = $_POST['id_empresa'];
    
    // Create or update client
    $client_success = false;
    if($action === "create") {
        if($client->create()) {
            $client_success = true;
        }
    } else {
        if($client->update()) {
            $client_success = true;
        }
    }
    
    // If client was created/updated successfully and social network data was provided
    if($client_success && isset($_POST['add_social_network']) && $_POST['add_social_network'] == '1') {
        // Set social network properties
        $social_network->id_cliente = $client->id_cliente;
        
        // Check if it's a new network type
        if(isset($_POST['new_network_type']) && !empty($_POST['new_network_type'])) {
            $social_network->tipo_red = $_POST['new_network_type'];
        } else {
            $social_network->tipo_red = $_POST['tipo_red'];
        }
        
        $social_network->nombre_red = $_POST['nombre_red'];
        $social_network->usuario_red = $_POST['usuario_red'];
        $social_network->contrasena_red = $_POST['contrasena_red'];
        $social_network->url_red = $_POST['url_red'] ?? '';
        $social_network->notas = $_POST['notas'] ?? '';
        
        // Create social network
        $social_network->create();
    }
    
    // Redirect after processing
    header("Location: client-view.php?id=" . $client->id_cliente);
    exit();
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

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?php echo $page_title; ?></h2>
    </div>
    
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $client->id_cliente : "")); ?>">
        <div class="p-4">
            <h3 class="text-lg font-semibold mb-4">Información del Cliente</h3>
            
            <!-- Información del Cliente en tarjetas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <!-- Nombre del Cliente -->
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <label for="nombre_cliente" class="block text-gray-700 font-medium mb-2">Nombre del Cliente</label>
                    <input type="text" id="nombre_cliente" name="nombre_cliente" required 
                           value="<?php echo $action === "update" ? $client->nombre_cliente : ''; ?>"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <!-- Fecha de Inicio -->
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <label for="fecha_inicio" class="block text-gray-700 font-medium mb-2">Fecha de Inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" required 
                           value="<?php echo $action === "update" ? $client->fecha_inicio : ''; ?>"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <!-- Cumpleaños -->
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <label for="cumpleaños" class="block text-gray-700 font-medium mb-2">Cumpleaños</label>
                    <input type="date" id="cumpleaños" name="cumpleaños" required 
                           value="<?php echo $action === "update" ? $client->cumpleaños : ''; ?>"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <!-- Fecha de Pago -->
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <label for="fecha_pago" class="block text-gray-700 font-medium mb-2">Fecha de Pago</label>
                    <input type="date" id="fecha_pago" name="fecha_pago" required 
                           value="<?php echo $action === "update" ? $client->fecha_pago : ''; ?>"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <!-- Estado del Cliente -->
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <label for="estado" class="block text-gray-700 font-medium mb-2">Estado del Cliente</label>
                    <select id="estado" name="estado" required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="activo" <?php echo ($action === "update" && $client->estado === "activo") ? "selected" : ""; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($action === "update" && $client->estado === "inactivo") ? "selected" : ""; ?>>Inactivo</option>
                    </select>
                </div>
                
                <!-- Tipo de Plan -->
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <label for="id_plan" class="block text-gray-700 font-medium mb-2">Tipo de Plan</label>
                    <select id="id_plan" name="id_plan" required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Seleccionar Plan</option>
                        <?php 
                        while($row = $planes_stmt->fetch(PDO::FETCH_ASSOC)) {
                            extract($row);
                            $selected = ($action === "update" && $client->id_plan == $id_plan) ? "selected" : "";
                            echo "<option value='{$id_plan}' {$selected}>{$nombre_plan} - $" . number_format($precio, 2) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Empresa -->
                <div class="bg-white p-4 rounded-lg shadow-sm border col-span-1 md:col-span-2 lg:col-span-3">
                    <label for="id_empresa" class="block text-gray-700 font-medium mb-2">Empresa</label>
                    <select id="id_empresa" name="id_empresa" 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Seleccionar Empresa</option>
                        <?php 
                        while($row = $empresas_stmt->fetch(PDO::FETCH_ASSOC)) {
                            extract($row);
                            $selected = ($action === "update" && $client->id_empresa == $id_empresa) ? "selected" : "";
                            echo "<option value='{$id_empresa}' {$selected}>{$nombre_empresa} - {$rubro}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <!-- Agregar Red Social (Colapsable) -->
            <div class="bg-white p-4 rounded-lg shadow-sm border mt-6">
                <div class="flex items-center mb-4">
                    <h3 class="text-lg font-semibold">Agregar Red Social</h3>
                    <button type="button" onclick="toggleSocialNetworkForm()" class="ml-3 px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <div id="social-network-form" class="hidden">
                    <input type="hidden" name="add_social_network" id="add_social_network" value="0">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="tipo_red" class="block text-gray-700 font-medium mb-2">Tipo de Red Social</label>
                            <div class="flex">
                                <select id="tipo_red" name="tipo_red" 
                                        class="flex-1 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                        onchange="handleNetworkTypeChange()">
                                    <option value="">Seleccionar Tipo</option>
                                    <?php foreach($network_types as $type): ?>
                                    <option value="<?php echo $type['nombre_tipo']; ?>" data-icon="<?php echo $type['icono']; ?>" data-color="<?php echo $type['color']; ?>"><?php echo $type['nombre_tipo']; ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Otro (Personalizado)</option>
                                </select>
                                <button type="button" onclick="toggleNewNetworkType()" 
                                        class="ml-2 px-2 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none" 
                                        title="Agregar nuevo tipo">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <!-- Campo para nuevo tipo de red social (inicialmente oculto) -->
                            <div id="new-network-type-container" class="hidden mt-2">
                                <input type="text" id="new_network_type" name="new_network_type" 
                                       class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                       placeholder="Nombre del nuevo tipo de red social">
                            </div>
                        </div>
                        <div>
                            <label for="nombre_red" class="block text-gray-700 font-medium mb-2">Nombre de la Cuenta</label>
                            <input type="text" id="nombre_red" name="nombre_red" 
                                   class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                   placeholder="Ej: Cuenta Principal, Cuenta Secundaria, etc.">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="usuario_red" class="block text-gray-700 font-medium mb-2">Usuario o Correo</label>
                            <input type="text" id="usuario_red" name="usuario_red" 
                                   class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                   placeholder="Nombre de usuario o correo electrónico">
                        </div>
                        <div>
                            <label for="contrasena_red" class="block text-gray-700 font-medium mb-2">Contraseña</label>
                            <div class="relative">
                                <input type="password" id="contrasena_red" name="contrasena_red" 
                                       class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                       placeholder="Contraseña de la cuenta">
                                <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center" 
                                        onclick="togglePasswordVisibility('contrasena_red')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="url_red" class="block text-gray-700 font-medium mb-2">URL (opcional)</label>
                        <input type="url" id="url_red" name="url_red" 
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                               placeholder="https://ejemplo.com/perfil">
                    </div>
                    
                    <div class="mb-4">
                        <label for="notas" class="block text-gray-700 font-medium mb-2">Notas (opcional)</label>
                        <textarea id="notas" name="notas" rows="3" 
                                  class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                  placeholder="Información adicional sobre esta cuenta"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-between mt-6">
                <a href="clients.php" 
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Guardar
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function toggleSocialNetworkForm() {
        const form = document.getElementById('social-network-form');
        const addSocialNetwork = document.getElementById('add_social_network');
        
        if (form.classList.contains('hidden')) {
            form.classList.remove('hidden');
            addSocialNetwork.value = '1';
        } else {
            form.classList.add('hidden');
            addSocialNetwork.value = '0';
        }
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
    
    // Actualizar el nombre de la red social automáticamente cuando se selecciona un tipo
    document.getElementById('tipo_red').addEventListener('change', function() {
        handleNetworkTypeChange();
    });
    
    // Actualizar el nombre de la red social cuando se escribe un nuevo tipo
    document.getElementById('new_network_type').addEventListener('input', function() {
        const nombreRedInput = document.getElementById('nombre_red');
        if (nombreRedInput.value === '' || nombreRedInput.value === document.getElementById('tipo_red').getAttribute('data-previous-value')) {
            nombreRedInput.value = this.value;
        }
    });
</script>

<?php
// Include footer
include 'includes/layout_footer.php';
?>