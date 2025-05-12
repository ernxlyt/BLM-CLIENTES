<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Empresa.php';
include_once 'utils/session.php';

// Require login and admin
requireLogin();
requireAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize empresa object
$empresa = new Empresa($db);

// Set page title
$page_title = "Empresas";

// Get all empresas
$stmt = $empresa->read();

// Include header
include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Empresas</h2>
        <a href="empresa-form.php" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Nueva Empresa
        </a>
    </div>

    <!-- Barra de búsqueda -->
    <div class="search-bar mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o rubro...">
    </div>

    <table class="data-table" id="companiesTable">
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Rubro</th>
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
                        <i class="fas fa-building" style="color: #23D950;"></i>
                    </div>
                    <?php echo $nombre_empresa; ?>
                </td>
                <td><?php echo $rubro; ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="empresa-view.php?id=<?php echo $id_empresa; ?>" class="btn btn-icon btn-secondary" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="empresa-form.php?id=<?php echo $id_empresa; ?>" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="empresa-delete.php?id=<?php echo $id_empresa; ?>" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta empresa?')">
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
                <td colspan="3" class="text-center">No hay empresas disponibles.</td>
            </tr>
            <?php 
            }
            ?>
        </tbody>
        <!-- Mensaje si no hay resultados -->
        <tfoot>
            <tr id="noResultsMessage" style="display: none;">
                <td colspan="3" class="text-center">No se encuentran empresas por la búsqueda.</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Lógica de búsqueda -->
<script>
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#companiesTable tbody tr');
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

        // Mostrar o ocultar el mensaje de "No se encuentran empresas"
        noResultsMessage.style.display = hasResults ? 'none' : '';
    });
</script>


<?php

include 'includes/layout_footer.php';
?>
