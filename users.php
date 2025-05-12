<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$page_title = "Usuarios";

$stmt = $user->read();

include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Usuarios</h2>
        <a href="user-form.php" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Nuevo Usuario
        </a>
    </div>

    <!-- Barra de búsqueda -->
    <div class="search-bar mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por correo, usuario o rol...">
    </div>

    <table class="data-table" id="usersTable">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
            ?>
            <tr>
                <td class="user-cell">
                    <div class="avatar" style="background-color: #d1fae5; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="color: #23D950;"></i>
                    </div>
                    <?php echo $nombre_usuario; ?>
                </td>
                <td><?php echo $correo_usuario; ?></td>
                <td><?php echo $nombre_rol; ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="user-view.php?id=<?php echo $id_usuario; ?>" class="btn btn-icon btn-secondary" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="user-form.php?id=<?php echo $id_usuario; ?>" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="user-delete.php?id=<?php echo $id_usuario; ?>" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este usuario?')">
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
                <td colspan="4" class="text-center">No hay usuarios disponibles.</td>
            </tr>
            <?php 
            }
            ?>
        </tbody>
        <!-- Mensaje si no hay resultados -->
        <tfoot>
            <tr id="noResultsMessage" style="display: none;">
                <td colspan="4" class="text-center">No se encuentran usuarios por la búsqueda.</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Lógica de búsqueda -->
<script>
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#usersTable tbody tr');
    const noResultsMessage = document.getElementById('noResultsMessage');

    searchInput.addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        let hasResults = false;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const textContent = Array.from(cells).map(cell => cell.innerText.toLowerCase()).join(' ');

            if (textContent.includes(filter)) {
                row.style.display = ''; // Mostrar fila
                hasResults = true;
            } else {
                row.style.display = 'none'; // Ocultar fila
            }
        });

        // Mostrar o ocultar el mensaje de "No se encuentran usuarios"
        noResultsMessage.style.display = hasResults ? 'none' : '';
    });
</script>


<?php

include 'includes/layout_footer.php';
?>
