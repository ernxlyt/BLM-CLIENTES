<?php
include_once 'config/database.php';
include_once 'models/Role.php';
include_once 'utils/session.php';

// Require login and admin
requireLogin();
requireAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize role object
$role = new Role($db);

// Set page title
$page_title = "Roles";

// Get all roles
$stmt = $role->read();

// Include header
include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Roles</h2>
        <a href="role-form.php" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Nuevo Rol
        </a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if($stmt->rowCount() > 0) {
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
            ?>
            <tr>
                <td class="user-cell">
                    <div class="avatar" style="background-color: #d1fae5; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user-tag" style="color: #23D950;"></i>
                    </div>
                    <?php echo $nombre_rol; ?>
                </td>
                <td><?php echo $descripcion_rol; ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="role-form.php?id=<?php echo $id_rol; ?>" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="role-delete.php?id=<?php echo $id_rol; ?>" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este rol?')">
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
                <td colspan="3" class="text-center">No hay roles disponibles.</td>
            </tr>
            <?php 
            }
            ?>
        </tbody>
    </table>
</div>

<?php
// Include footer
include 'includes/layout_footer.php';
?>
