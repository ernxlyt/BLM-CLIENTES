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
/* Estilos adicionales para asegurar visibilidad */
.btn-primary {
    background-color: #10b981 !important;
    border-color: #10b981 !important;
    color: white !important;
    padding: 12px 24px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    border-radius: 6px !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.btn-primary:hover {
    background-color: #059669 !important;
    border-color: #059669 !important;
    transform: translateY(-1px) !important;
}

.btn-secondary {
    background-color: #6b7280 !important;
    border-color: #6b7280 !important;
    color: white !important;
    padding: 12px 24px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    border-radius: 6px !important;
    text-decoration: none !important;
    display: inline-block !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.btn-secondary:hover {
    background-color: #4b5563 !important;
    border-color: #4b5563 !important;
    color: white !important;
    text-decoration: none !important;
    transform: translateY(-1px) !important;
}

.form-actions {
    background-color: #f9fafb;
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    margin-top: 30px;
    border-radius: 0 0 8px 8px;
}

.location-section {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #0ea5e9;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
}

.location-icon {
    color: #0ea5e9;
    font-size: 1.2em;
}

/* Estilos para el formulario de red social */
.social-network-section {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
}

.social-network-form {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
    border: 1px solid #e5e7eb;
}

.toggle-button {
    transition: all 0.3s ease;
    transform: scale(1);
}

.toggle-button:hover {
    transform: scale(1.05);
}

.toggle-button.active {
    background-color: #ef4444 !important;
}

.toggle-button.active:hover {
    background-color: #dc2626 !important;
}

/* Animaciones para mostrar/ocultar formulario */
.social-form-enter {
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.social-form-enter-active {
    opacity: 1;
    transform: translateY(0);
}

.social-form-exit {
    opacity: 1;
    transform: translateY(0);
    transition: all 0.3s ease;
}

.social-form-exit-active {
    opacity: 0;
    transform: translateY(-10px);
}
</style>

<?php if(isset($error_message)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-green-500 text-white px-6 py-4">
            <h1 class="text-2xl font-bold"><?php echo $page_title; ?></h1>
        </div>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $client->id_cliente : "")); ?>" id="clientForm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-6 text-gray-800">Información del Cliente</h3>
                
                <!-- Información del Cliente en tarjetas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Nombre del Cliente -->
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <label for="nombre_cliente" class="block text-gray-700 font-medium mb-2">
                            Nombre del Cliente <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nombre_cliente" name="nombre_cliente" required 
                               value="<?php echo $action === "update" ? htmlspecialchars($client->nombre_cliente ?? '') : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <!-- Fecha de Inicio -->
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <label for="fecha_inicio" class="block text-gray-700 font-medium mb-2">
                            Fecha de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" required 
                               value="<?php echo $action === "update" ? ($client->fecha_inicio ?? '') : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <!-- Cumpleaños -->
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <label for="cumpleaños" class="block text-gray-700 font-medium mb-2">
                            Cumpleaños <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="cumpleaños" name="cumpleaños" required 
                               value="<?php echo $action === "update" ? ($client->cumpleaños ?? '') : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <!-- Fecha de Pago -->
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <label for="fecha_pago" class="block text-gray-700 font-medium mb-2">
                            Fecha de Pago
                        </label>
                        <input type="date" id="fecha_pago" name="fecha_pago" 
                               value="<?php echo $action === "update" ? ($client->fecha_pago ?? '') : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    
                    <!-- Estado del Cliente -->
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <label for="estado" class="block text-gray-700 font-medium mb-2">
                            Estado del Cliente <span class="text-red-500">*</span>
                        </label>
                        <select id="estado" name="estado" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="Activo" <?php echo ($action === "update" && ($client->estado ?? '') === "Activo") ? "selected" : ""; ?>>Activo</option>
                            <option value="Inactivo" <?php echo ($action === "update" && ($client->estado ?? '') === "Inactivo") ? "selected" : ""; ?>>Inactivo</option>
                        </select>
                    </div>
                    
                    <!-- Tipo de Plan -->
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <label for="id_plan" class="block text-gray-700 font-medium mb-2">Tipo de Plan</label>
                        <select id="id_plan" name="id_plan" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
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
                    <div class="bg-gray-50 p-4 rounded-lg border col-span-1 md:col-span-2 lg:col-span-3">
                        <label for="id_empresa" class="block text-gray-700 font-medium mb-2">Empresa</label>
                        <select id="id_empresa" name="id_empresa" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
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

                <!-- Sección de Ubicación Geográfica -->
                <div class="location-section">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-map-marked-alt location-icon mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Ubicación Geográfica</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- País -->
                        <div class="bg-white p-4 rounded-lg border border-blue-200">
                            <label for="pais" class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-globe location-icon mr-2"></i>País
                            </label>
                            <input type="text" id="pais" name="pais" 
                                   value="<?php echo $action === "update" && isset($client->pais) ? htmlspecialchars($client->pais) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Ej: México, Colombia, Argentina...">
                            <small class="text-gray-500 mt-1 block">
                                <i class="fas fa-info-circle mr-1"></i>
                                Escriba el nombre del país
                            </small>
                        </div>
                        
                        <!-- Ubicación (Provincia/Estado) -->
                        <div class="bg-white p-4 rounded-lg border border-blue-200">
                            <label for="provincia" class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-map-marker-alt location-icon mr-2"></i>Ubicación (Estado/Provincia/Ciudad)
                            </label>
                            <input type="text" id="provincia" name="provincia" 
                                   value="<?php echo $action === "update" && isset($client->provincia) ? htmlspecialchars($client->provincia) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Ej: Ciudad de México, Buenos Aires, Bogotá...">
                            <small class="text-gray-500 mt-1 block">
                                <i class="fas fa-info-circle mr-1"></i>
                                Puede incluir estado, provincia, ciudad o región
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Agregar Red Social (Mejorado con el componente del attachment) -->
                <div class="social-network-section">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-share-alt text-blue-600 mr-3" style="font-size: 1.2em;"></i>
                            <h3 class="text-lg font-semibold text-blue-800">Agregar Red Social (Opcional)</h3>
                        </div>
                        <button type="button" 
                                id="toggleSocialButton"
                                onclick="toggleSocialNetworkForm()" 
                                class="toggle-button px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 font-medium shadow-md">
                            <i class="fas fa-plus mr-2"></i>Agregar Red Social
                        </button>
                    </div>
                    
                    <div id="social-network-form" class="social-network-form hidden">
                        <input type="hidden" name="add_social_network" id="add_social_network" value="0">
                        
                        <div class="mb-6">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-network-wired text-blue-600 mr-2"></i>
                                <h4 class="text-md font-semibold text-gray-700">Información de la Red Social</h4>
                            </div>
                            <div class="h-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full mb-4"></div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="tipo_red" class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-tags text-blue-500 mr-2"></i>
                                    Tipo de Red Social <span class="text-red-500">*</span>
                                </label>
                                <div class="flex">
                                    <select id="tipo_red" name="tipo_red" 
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                            onchange="handleNetworkTypeChange()">
                                        <option value="">Seleccionar Tipo</option>
                                        <?php foreach($network_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['nombre_tipo']); ?>" 
                                                data-icon="<?php echo htmlspecialchars($type['icono']); ?>" 
                                                data-color="<?php echo htmlspecialchars($type['color']); ?>">
                                            <?php echo htmlspecialchars($type['nombre_tipo']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <option value="other">🔧 Otro (Personalizado)</option>
                                    </select>
                                    <button type="button" onclick="toggleNewNetworkType()" 
                                            class="ml-3 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none transition-all" 
                                            title="Agregar nuevo tipo">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <!-- Campo para nuevo tipo de red social -->
                                <div id="new-network-type-container" class="hidden mt-3">
                                    <input type="text" id="new_network_type" name="new_network_type" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" 
                                           placeholder="Nombre del nuevo tipo de red social">
                                </div>
                            </div>
                            <div>
                                <label for="nombre_red" class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                                    Nombre de la Cuenta <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="nombre_red" name="nombre_red" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                       placeholder="Ej: Cuenta Principal, Cuenta Secundaria, etc.">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="usuario_red" class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-at text-blue-500 mr-2"></i>
                                    Usuario o Correo
                                </label>
                                <input type="text" id="usuario_red" name="usuario_red" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                       placeholder="Nombre de usuario o correo electrónico">
                            </div>
                            <div>
                                <label for="contrasena_red" class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-lock text-blue-500 mr-2"></i>
                                    Contraseña
                                </label>
                                <div class="relative">
                                    <input type="password" id="contrasena_red" name="contrasena_red" 
                                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                           placeholder="Contraseña de la cuenta">
                                    <button type="button" class="absolute inset-y-0 right-0 px-4 flex items-center" 
                                            onclick="togglePasswordVisibility('contrasena_red')">
                                        <i class="fas fa-eye text-gray-400 hover:text-gray-600 transition-colors"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="url_red" class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-link text-blue-500 mr-2"></i>
                                URL (opcional)
                            </label>
                            <input type="url" id="url_red" name="url_red" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                   placeholder="https://ejemplo.com/perfil">
                        </div>
                        
                        <div class="mb-6">
                            <label for="notas" class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-sticky-note text-blue-500 mr-2"></i>
                                Notas (opcional)
                            </label>
                            <textarea id="notas" name="notas" rows="4" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none" 
                                      placeholder="Información adicional sobre esta cuenta..."></textarea>
                        </div>
                        
                        <!-- Botones de acción del formulario de red social -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" onclick="clearSocialNetworkForm()" 
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none transition-all">
                                <i class="fas fa-eraser mr-2"></i>Limpiar
                            </button>
                            <button type="button" onclick="toggleSocialNetworkForm()" 
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none transition-all">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botones de acción principales -->
            <div class="form-actions">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <a href="clients.php" class="btn-secondary w-full sm:w-auto text-center">
                        <i class="fas fa-arrow-left mr-2"></i>Cancelar
                    </a>
                    
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button type="button" onclick="resetForm()" 
                                class="px-6 py-3 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 font-semibold transition-colors">
                            <i class="fas fa-undo mr-2"></i>Limpiar Formulario
                        </button>
                        
                        <button type="submit" class="btn-primary w-full sm:w-auto" id="saveButton">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo $action === "create" ? "Crear Cliente" : "Actualizar Cliente"; ?>
                        </button>
                    </div>
                </div>
                
                <!-- Indicador de campos requeridos -->
                <div class="mt-4 text-sm text-gray-600 text-center">
                    <i class="fas fa-info-circle mr-1"></i>
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios. La fecha de pago es opcional.
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
            form.classList.add('social-form-enter');
            
            setTimeout(() => {
                form.classList.remove('social-form-enter');
                form.classList.add('social-form-enter-active');
            }, 10);
            
            addSocialNetwork.value = '1';
            button.innerHTML = '<i class="fas fa-minus mr-2"></i>Ocultar Red Social';
            button.classList.remove('bg-blue-500', 'hover:bg-blue-600');
            button.classList.add('bg-red-500', 'hover:bg-red-600', 'active');
            socialFormVisible = true;
            
            // Focus en el primer campo
            setTimeout(() => {
                document.getElementById('tipo_red').focus();
            }, 300);
            
        } else {
            // Ocultar formulario
            form.classList.add('social-form-exit');
            
            setTimeout(() => {
                form.classList.remove('social-form-exit', 'social-form-enter-active');
                form.classList.add('hidden');
                clearSocialNetworkForm();
            }, 300);
            
            addSocialNetwork.value = '0';
            button.innerHTML = '<i class="fas fa-plus mr-2"></i>Agregar Red Social';
            button.classList.remove('bg-red-500', 'hover:bg-red-600', 'active');
            button.classList.add('bg-blue-500', 'hover:bg-blue-600');
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
            saveButton.innerHTML = '<i class="fas fa-save mr-2"></i><?php echo $action === "create" ? "Crear Cliente" : "Actualizar Cliente"; ?>';
            saveButton.disabled = false;
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
            eyeIcon.classList.add('text-blue-600');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash', 'text-blue-600');
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
                nombreRedInput.value = selectedOption.text.replace('🔧 ', '');
            }
        }
    }
    
    document.getElementById('clientForm').addEventListener('submit', function(e) {
        if (formSubmitted) {
            e.preventDefault();
            return false;
        }
        
        const saveButton = document.getElementById('saveButton');
        
        // Cambiar el botón para mostrar que se está procesando
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        saveButton.disabled = true;
        
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
        saveButton.innerHTML = '<i class="fas fa-save mr-2"></i><?php echo $action === "create" ? "Crear Cliente" : "Actualizar Cliente"; ?>';
        saveButton.disabled = false;
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
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-blue-200');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-blue-200');
            });
        });
    });
</script>

<?php
include 'includes/layout_footer.php';
?>