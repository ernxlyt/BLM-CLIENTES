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
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre, empresa, país, ubicación, plan o estado...">
    </div>
    
    <table class="data-table" id="clientsTable">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Empresa</th>
                <th>País</th>
                <th>Ubicación</th>
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
                <td>
                    <?php if (!empty($pais)): ?>
                        <span class="location-badge">
                            <i class="fas fa-globe mr-1"></i>
                            <?php echo $pais; ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">No especificado</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($provincia)): ?>
                        <span class="location-badge">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?php echo $provincia; ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">No especificado</span>
                    <?php endif; ?>
                </td>
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
                <td colspan="8" class="text-center">No hay clientes disponibles.</td>
            </tr>
            <?php 
            }
            ?>
        </tbody>
        <!-- Mensaje si no hay resultados -->
        <tfoot>
            <tr id="noResultsMessage" style="display: none;">
                <td colspan="8" class="text-center">No se encuentran clientes por la búsqueda.</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Estilos para las etiquetas de ubicación -->
<style>
.location-badge {
    background-color: #f8f9fa;
    color: #495057;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
    border: 1px solid #dee2e6;
}

.location-badge i {
    color: #6c757d;
}

.text-muted {
    color: #6c757d !important;
    font-style: italic;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
    .data-table th:nth-child(3),
    .data-table td:nth-child(3),
    .data-table th:nth-child(4),
    .data-table td:nth-child(4) {
        display: none;
    }
}

@media (max-width: 576px) {
    .data-table th:nth-child(2),
    .data-table td:nth-child(2),
    .data-table th:nth-child(6),
    .data-table td:nth-child(6) {
        display: none;
    }
}
</style>

<!-- Lógica de búsqueda actualizada -->
<script>
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#clientsTable tbody tr');
    const noResultsMessage = document.getElementById('noResultsMessage');

    searchInput.addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        let hasResults = false;

        tableRows.forEach(row => {
            // Verificar si la fila tiene el mensaje "No hay clientes disponibles"
            if (row.querySelector('td[colspan]')) {
                return; // Saltar esta fila
            }

            const cells = row.querySelectorAll('td');
            
            // Extraer texto de cada celda relevante
            const cliente = cells[0]?.innerText.toLowerCase() || '';
            const empresa = cells[1]?.innerText.toLowerCase() || '';
            const pais = cells[2]?.innerText.toLowerCase() || '';
            const ubicacion = cells[3]?.innerText.toLowerCase() || '';
            const fechaPago = cells[4]?.innerText.toLowerCase() || '';
            const plan = cells[5]?.innerText.toLowerCase() || '';
            const estado = cells[6]?.innerText.toLowerCase() || '';

            // Combinar todo el contenido de texto para la búsqueda
            const textContent = `${cliente} ${empresa} ${pais} ${ubicacion} ${fechaPago} ${plan} ${estado}`;

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

<!-- Script adicional para filtros específicos (opcional) -->
<script>
    // Función para filtrar por país específico
    function filterByCountry(country) {
        const searchInput = document.getElementById('searchInput');
        searchInput.value = country;
        searchInput.dispatchEvent(new Event('keyup'));
    }

    // Función para filtrar por ubicación específica
    function filterByLocation(location) {
        const searchInput = document.getElementById('searchInput');
        searchInput.value = location;
        searchInput.dispatchEvent(new Event('keyup'));
    }

    // Agregar tooltips a las ubicaciones (opcional)
    document.addEventListener('DOMContentLoaded', function() {
        const locationBadges = document.querySelectorAll('.location-badge');
        locationBadges.forEach(badge => {
            badge.style.cursor = 'pointer';
            badge.title = 'Click para filtrar por esta ubicación';
            
            badge.addEventListener('click', function() {
                const text = this.innerText.trim();
                filterByLocation(text);
            });
        });
    });
</script>

<?php
include 'includes/layout_footer.php';
?>