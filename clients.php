<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'models/Client.php';
include_once 'utils/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$client = new Client($db);

$page_title = "Clientes";

$stmt = $client->read(getCurrentUserId(), isAdmin());

include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Clientes</h2>
        <?php if (isAdmin()): ?>
        <a href="client-form.php" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Nuevo Cliente
        </a>
        <?php endif; ?>
    </div>

    <!-- Barra de búsqueda -->
    <div class="search-bar mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre, estado, o plan...">
    </div>
    
    <table class="data-table" id="clientsTable">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Empresa</th>
                <th>Fecha Pago</th>
                <th>Plan</th>
                <th>Estado</th>
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
                    <?php echo $nombre_cliente; ?>
                </td>
                <td><?php echo $nombre_empresa ? $nombre_empresa : 'No asignada'; ?></td>
                <td><?php echo date('d', strtotime($fecha_pago)); ?></td>
                <td><?php echo $nombre_plan ? $nombre_plan : 'No asignado'; ?></td>
                <td>
                    <?php if ($estado === 'Activo'): ?>
                        <span style="color: white; background-color: #28a745; padding: 5px 10px; border-radius: 5px;">
                            Activo
                        </span>
                    <?php else: ?>
                        <span style="color: white; background-color: #dc3545; padding: 5px 10px; border-radius: 5px;">
                            Inactivo
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="client-view.php?id=<?php echo $id_cliente; ?>" class="btn btn-icon btn-secondary" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if (isAdmin()): ?>
                        <a href="client-form.php?id=<?php echo $id_cliente; ?>" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="client-delete.php?id=<?php echo $id_cliente; ?>" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este cliente?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php 
                }
            } else {
            ?>
            <tr>
                <td colspan="6" class="text-center">No hay clientes disponibles.</td>
            </tr>
            <?php 
            }
            ?>
        </tbody>
        <!-- Mensaje si no hay resultados -->
        <tfoot>
            <tr id="noResultsMessage" style="display: none;">
                <td colspan="6" class="text-center">No se encuentran clientes por la búsqueda.</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Lógica de búsqueda -->
<script>
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#clientsTable tbody tr');
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

        // Mostrar o ocultar el mensaje de "No se encuentran clientes"
        noResultsMessage.style.display = hasResults ? 'none' : '';
    });
</script>


<!-- Agregar lógica de búsqueda -->
<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#clientsTable tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const name = cells[0]?.innerText.toLowerCase() || '';
            const company = cells[1]?.innerText.toLowerCase() || '';
            const payment = cells[2]?.innerText.toLowerCase() || '';
            const plan = cells[3]?.innerText.toLowerCase() || '';
            const state = cells[4]?.innerText.toLowerCase() || '';

            // Filtrar si el texto está presente en cualquier celda
            if (name.includes(filter) || company.includes(filter) || plan.includes(filter) || state.includes(filter)) {
                row.style.display = ''; // Mostrar fila
            } else {
                row.style.display = 'none'; // Ocultar fila
            }
        });
    });
</script>




<?php
include 'includes/layout_footer.php';
?>
