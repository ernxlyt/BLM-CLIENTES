<?php
include_once 'config/database.php';
include_once 'models/Plan.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);

$page_title = "Planes";

$stmt = $plan->read();

include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Planes</h2>
        <a href="plan-form.php" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Nuevo Plan
        </a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
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
                        <i class="fas fa-tag" style="color: #23D950;"></i>
                    </div>
                    <?php echo $nombre_plan; ?>
                </td>
                <td><?php echo $descripcion_plan; ?></td>
                <td>$<?php echo number_format($precio, 2); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="plan-form.php?id=<?php echo $id_plan; ?>" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="plan-delete.php?id=<?php echo $id_plan; ?>" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este plan?')">
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
                <td colspan="4" class="text-center">No hay planes disponibles.</td>
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
