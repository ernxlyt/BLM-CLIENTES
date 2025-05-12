<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/SocialNetwork.php';
include_once 'utils/session.php';

// Require login
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize social network object
$social_network = new SocialNetwork($db);

// Check if ID is set
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: redes-sociales.php");
    exit();
}

// Set social network ID
$social_network->id_red = $_GET['id'];

// Check if social network exists
if(!$social_network->readOne()) {
    header("Location: redes-sociales.php");
    exit();
}

// Get client name
$query = "SELECT nombre_cliente FROM clientes WHERE id_cliente = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $social_network->id_cliente);
$stmt->execute();
$client_row = $stmt->fetch(PDO::FETCH_ASSOC);
$client_name = $client_row ? $client_row['nombre_cliente'] : 'Cliente Desconocido';

// Get network type info
$network_info = $social_network->getNetworkTypeInfo($social_network->tipo_red);

// Set page title
$page_title = "Detalles de Red Social";

// Include header
include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Detalles de Red Social</h2>
        <div>
            <a href="red-social-form.php?id=<?php echo $social_network->id_red; ?>" class="btn btn-warning">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
        </div>
    </div>
    
    <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Información General</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4" style="background-color: <?php echo $network_info['color']; ?>">
                            <i class="fab <?php echo $network_info['icono']; ?> text-2xl text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold"><?php echo $social_network->nombre_red; ?></h4>
                            <p class="text-gray-600"><?php echo $social_network->tipo_red; ?></p>
                        </div>
                    </div>
                    
                    <p class="mb-2"><span class="font-bold">Cliente:</span> <?php echo $client_name; ?></p>
                    <p class="mb-2"><span class="font-bold">Usuario:</span> <?php echo $social_network->usuario_red; ?></p>
                    <p class="mb-2">
                        <span class="font-bold">Contraseña:</span> 
                        <span id="password-hidden">••••••••</span>
                        <span id="password-visible" class="hidden"><?php echo $social_network->contrasena_red; ?></span>
                        <button type="button" class="ml-2 text-blue-600 hover:text-blue-800" onclick="togglePassword()">
                            <i id="eye-icon" class="fas fa-eye"></i>
                        </button>
                    </p>
                    
                    <?php if(!empty($social_network->url_red)): ?>
                    <p class="mb-2">
                        <span class="font-bold">URL:</span> 
                        <a href="<?php echo $social_network->url_red; ?>" target="_blank" class="text-blue-600 hover:underline">
                            <?php echo $social_network->url_red; ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <?php if(!empty($social_network->notas)): ?>
                    <div class="mt-4">
                        <p class="font-bold">Notas:</p>
                        <p class="bg-white p-3 rounded mt-1"><?php echo nl2br($social_network->notas); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <p class="mt-4 text-sm text-gray-500">
                        <span class="font-bold">Fecha de creación:</span> 
                        <?php echo date('d/m/Y H:i', strtotime($social_network->fecha_creacion)); ?>
                    </p>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold mb-4">Acciones Rápidas</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="grid grid-cols-1 gap-4">
                        <?php if(!empty($social_network->url_red)): ?>
                        <a href="<?php echo $social_network->url_red; ?>" target="_blank" class="btn btn-primary w-full flex items-center justify-center">
                            <i class="fas fa-external-link-alt mr-2"></i> Visitar Perfil
                        </a>
                        <?php endif; ?>
                        
                        <a href="client-view.php?id=<?php echo $social_network->id_cliente; ?>" class="btn btn-secondary w-full flex items-center justify-center">
                            <i class="fas fa-user mr-2"></i> Ver Cliente
                        </a>
                        
                        <a href="red-social-form.php?id=<?php echo $social_network->id_red; ?>" class="btn btn-warning w-full flex items-center justify-center">
                            <i class="fas fa-edit mr-2"></i> Editar Red Social
                        </a>
                        
                        <a href="red-social-delete.php?id=<?php echo $social_network->id_red; ?>" class="btn btn-danger w-full flex items-center justify-center" onclick="return confirm('¿Está seguro de eliminar esta red social?')">
                            <i class="fas fa-trash mr-2"></i> Eliminar Red Social
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-between mt-6">
            <a href="redes-sociales.php" class="btn btn-secondary">
                Volver a la Lista
            </a>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const hiddenPassword = document.getElementById('password-hidden');
        const visiblePassword = document.getElementById('password-visible');
        const eyeIcon = document.getElementById('eye-icon');
        
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
</script>

<?php
// Include footer
include 'includes/layout_footer.php';
?>
