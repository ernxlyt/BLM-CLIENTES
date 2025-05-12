<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'models/Relation.php';
include_once 'models/Client.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$relation = new Relation($db);
$client = new Client($db);


if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user->id_usuario = $_GET['id'];
$relation->id_usuario = $_GET['id'];

if (!$user->readOne()) {
    header("Location: users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_client'])) {
    $relation->id_cliente = $_POST['client_id'];
    
    if (!$relation->exists()) {
        if ($relation->create()) {
            header("Location: user-view.php?id=" . $user->id_usuario);
            exit();
        }
    }
}

$assigned_clients = $relation->readByUser();
$all_clients = $client->read(getCurrentUserId(), true);

$page_title = "Detalles del Usuario";

include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Detalles del Usuario</h2>
        <a href="user-form.php?id=<?php echo $user->id_usuario; ?>" class="btn btn-warning">
            <i class="fas fa-edit mr-2"></i> Editar
        </a>
    </div>
    
    <div class="p-4">
        <!-- Información General -->
        <div class="bg-gray-50 p-4 rounded">
        <p class="mb-2"><span class="font-bold">Nombre:</span> <?php echo $user->nombre_usuario; ?></p>
        <p class="mb-2"><span class="font-bold">Correo:</span> <?php echo $user->correo_usuario; ?></p>
        <p class="mb-2"><span class="font-bold">Rol:</span> <?php echo $user->nombre_rol; ?></p>
        <p class="mb-2"><span class="font-bold">Contraseña:</span> 
        <?php 
        // Mostrar la contraseña (texto cifrado recuperado)
        echo !empty($user->contrasena) ? $user->contrasena : 'No disponible'; 
        ?>
    </p>
</div>

        
        <!-- Clientes Asignados -->
        <h3 class="text-lg font-semibold mb-4">Clientes Asignados</h3>
        <div class="bg-gray-50 p-4 rounded mb-4">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $user->id_usuario); ?>" class="flex items-end gap-4">
                <input type="hidden" name="assign_client" value="1">
                
                <div class="flex-1">
                    <label for="client_id" class="form-label">Asignar Cliente</label>
                    <select id="client_id" name="client_id" required class="form-select">
                        <option value="">Seleccionar Cliente</option>
                        <?php 
                        while ($row = $all_clients->fetch(PDO::FETCH_ASSOC)) {
                            extract($row);
                            echo "<option value='{$id_cliente}'>{$nombre_cliente}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Asignar</button>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($assigned_clients->rowCount() > 0) {
                    while ($row = $assigned_clients->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                ?>
                <tr>
                    <td class="user-cell">
                        <div class="avatar" style="background-color: #d1fae5; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="color: #23D950;"></i>
                        </div>
                        <?php echo $nombre_cliente; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="client-view.php?id=<?php echo $id_cliente; ?>" class="btn btn-icon btn-secondary" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="relation-delete.php?id=<?php echo $id_relacion; ?>&user_id=<?php echo $user->id_usuario; ?>" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta asignación?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="2" class="text-center">No hay clientes asignados.</td>
                </tr>
                <?php 
                }
                ?>
            </tbody>
        </table>
        
        <!-- Botón para Volver -->
        <div class="flex justify-between mt-6">
            <a href="users.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php
include 'includes/layout_footer.php';
?>
