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

// Set page title
$page_title = "Redes Sociales";

// Get all social networks based on user permissions
$stmt = $social_network->read(getCurrentUserId(), isAdmin());

// Include header
include 'includes/layout_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lista de Redes Sociales</h2>
        <?php if(isAdmin()): ?>
        <a href="red-social-form.php" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Nueva Red Social
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Barra de búsqueda -->
    <div class="search-bar mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por red social, cliente, usuario o URL...">
    </div>
    
    <table class="data-table" id="socialNetworksTable">
        <thead>
            <tr>
                <th>Red Social</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>URL</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if($stmt->rowCount() > 0) {
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    
                    // Determinar el icono y color basado en el tipo de red
                    $network_info = $social_network->getNetworkTypeInfo($tipo_red);
                    $icon = $network_info['icono'];
                    $bg_color = $network_info['color'];
                    $text_color = '#ffffff';
            ?>
            <tr>
                <td class="user-cell">
                    <div class="avatar" style="background-color: <?php echo $bg_color; ?>; display: flex; align-items: center; justify-content: center;">
                        <i class="fab <?php echo $icon; ?>" style="color: <?php echo $text_color; ?>;"></i>
                    </div>
                    <?php echo $nombre_red; ?> (<?php echo $tipo_red; ?>)
                </td>
                <td><?php echo $nombre_cliente; ?></td>
                <td><?php echo $usuario_red; ?></td>
                <td>
                    <?php if(!empty($url_red)): ?>
                    <a href="<?php echo $url_red; ?>" target="_blank" class="text-blue-600 hover:underline">
                        <?php echo $url_red; ?>
                    </a>
                    <?php else: ?>
                    <span class="text-gray-400">No disponible</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="red-social-view.php?id=<?php echo $id_red; ?>" class="btn btn-icon btn-secondary" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if(isAdmin()): ?>
                        <a href="red-social-form.php?id=<?php echo $id_red; ?>" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="red-social-delete.php?id=<?php echo $id_red; ?>" class="btn btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta red social?')">
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
                <td colspan="5" class="text-center">No hay redes sociales disponibles.</td>
            </tr>
            <?php 
            }
            ?>
        </tbody>
        <!-- Mensaje si no hay resultados -->
        <tfoot>
            <tr id="noResultsMessage" style="display: none;">
                <td colspan="5" class="text-center">No se encuentran redes sociales por la búsqueda.</td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Lógica de búsqueda -->
<script>
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#socialNetworksTable tbody tr');
    const noResultsMessage = document.getElementById('noResultsMessage');

    searchInput.addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        let hasResults = false;

        tableRows.forEach(row => {
            // Verificar si la fila contiene datos (no es el mensaje de "no hay datos")
            if (row.cells.length === 5) {
                const cells = row.querySelectorAll('td');
                const socialNetwork = cells[0]?.innerText.toLowerCase() || '';
                const client = cells[1]?.innerText.toLowerCase() || '';
                const user = cells[2]?.innerText.toLowerCase() || '';
                const url = cells[3]?.innerText.toLowerCase() || '';

                // Filtrar si el texto está presente en cualquier celda
                if (socialNetwork.includes(filter) || 
                    client.includes(filter) || 
                    user.includes(filter) || 
                    url.includes(filter)) {
                    row.style.display = ''; // Mostrar fila
                    hasResults = true;
                } else {
                    row.style.display = 'none'; // Ocultar fila
                }
            }
        });

        // Mostrar o ocultar el mensaje de "No se encuentran redes sociales"
        noResultsMessage.style.display = hasResults ? 'none' : '';
    });
</script>

<?php
// Include footer
include 'includes/layout_footer.php';
?>