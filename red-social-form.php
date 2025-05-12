<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/SocialNetwork.php';
include_once 'models/Client.php';
include_once 'utils/session.php';

// Require login
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$social_network = new SocialNetwork($db);
$client = new Client($db);

// Set page title and action
$page_title = "Nueva Red Social";
$action = "create";

// Check if editing
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $social_network->id_red = $_GET['id'];
    
    // Check if social network exists
    if($social_network->readOne()) {
        $page_title = "Editar Red Social";
        $action = "update";
    } else {
        // Redirect if social network not found
        header("Location: redes-sociales.php");
        exit();
    }
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set social network properties
    $social_network->id_cliente = $_POST['id_cliente'];
    $social_network->tipo_red = $_POST['tipo_red'];
    if($_POST['tipo_red'] === 'other') {
        $social_network->tipo_red = $_POST['new_network_type'];
    }
    $social_network->nombre_red = $_POST['nombre_red'];
    $social_network->usuario_red = $_POST['usuario_red'];
    $social_network->contrasena_red = $_POST['contrasena_red'];
    $social_network->url_red = $_POST['url_red'];
    $social_network->notas = $_POST['notas'];
    
    // Create or update social network
    if($action === "create") {
        if($social_network->create()) {
            header("Location: redes-sociales.php");
            exit();
        }
    } else {
        if($social_network->update()) {
            header("Location: redes-sociales.php");
            exit();
        }
    }
}

// Get all clients for dropdown
$clients_stmt = $client->read(getCurrentUserId(), isAdmin());

// Get network types
$network_types = $social_network->getNetworkTypes();

// Include header
include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?php echo $page_title; ?></h2>
    </div>
    
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $social_network->id_red : "")); ?>">
        <div class="p-4">
            <div class="form-group">
                <label for="id_cliente" class="form-label">Cliente</label>
                <select id="id_cliente" name="id_cliente" required class="form-select">
                    <option value="">Seleccionar Cliente</option>
                    <?php 
                    while($row = $clients_stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                        $selected = ($action === "update" && $social_network->id_cliente == $id_cliente) ? "selected" : "";
                        echo "<option value='{$id_cliente}' {$selected}>{$nombre_cliente}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo_red" class="form-label">Tipo de Red Social</label>
                <div class="flex">
                    <select id="tipo_red" name="tipo_red" required class="form-select flex-1" onchange="handleNetworkTypeChange()">
                        <option value="">Seleccionar Tipo</option>
                        <?php 
                        foreach($network_types as $type) {
                            $selected = ($action === "update" && $social_network->tipo_red == $type['nombre_tipo']) ? "selected" : "";
                            echo "<option value='{$type['nombre_tipo']}' {$selected} data-icon='{$type['icono']}' data-color='{$type['color']}'>{$type['nombre_tipo']}</option>";
                        }
                        ?>
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
            
            <div class="form-group">
                <label for="nombre_red" class="form-label">Nombre de la Cuenta</label>
                <input type="text" id="nombre_red" name="nombre_red" required 
                       value="<?php echo $action === "update" ? $social_network->nombre_red : ''; ?>"
                       class="form-control" placeholder="Ej: Cuenta Principal, Cuenta Secundaria, etc.">
            </div>
            
            <div class="form-group">
                <label for="usuario_red" class="form-label">Usuario o Correo</label>
                <input type="text" id="usuario_red" name="usuario_red" required 
                       value="<?php echo $action === "update" ? $social_network->usuario_red : ''; ?>"
                       class="form-control" placeholder="Nombre de usuario o correo electrónico">
            </div>
            
            <div class="form-group">
                <label for="contrasena_red" class="form-label">Contraseña</label>
                <div class="relative">
                    <input type="password" id="contrasena_red" name="contrasena_red" 
                           value="<?php echo $action === "update" ? $social_network->contrasena_red : ''; ?>"
                           class="form-control" placeholder="Contraseña de la cuenta">
                    <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center" onclick="togglePasswordVisibility()">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="url_red" class="form-label">URL (opcional)</label>
                <input type="url" id="url_red" name="url_red" 
                       value="<?php echo $action === "update" ? $social_network->url_red : ''; ?>"
                       class="form-control" placeholder="https://ejemplo.com/perfil">
            </div>
            
            <div class="form-group">
                <label for="notas" class="form-label">Notas (opcional)</label>
                <textarea id="notas" name="notas" rows="3" 
                          class="form-control" placeholder="Información adicional sobre esta cuenta"><?php echo $action === "update" ? $social_network->notas : ''; ?></textarea>
            </div>
            
            <div class="flex justify-between mt-6">
                <a href="redes-sociales.php" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('contrasena_red');
        const toggleButton = document.getElementById('toggle-password');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            passwordInput.type = 'password';
            toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
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
