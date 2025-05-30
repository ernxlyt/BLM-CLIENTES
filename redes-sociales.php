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

// Verificar si es una petición AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    // Manejar petición AJAX
    $records_per_page = 15;
    $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $current_page = max(1, $current_page);
    
    $search_term = isset($_POST['search']) ? trim($_POST['search']) : '';
    
    // Obtener datos
    if (!empty($search_term)) {
        $stmt = $social_network->search(getCurrentUserId(), isAdmin(), $search_term, $current_page, $records_per_page);
        $total_records = $social_network->countSocialNetworks(getCurrentUserId(), isAdmin(), $search_term);
    } else {
        $stmt = $social_network->read(getCurrentUserId(), isAdmin(), $current_page, $records_per_page);
        $total_records = $social_network->countSocialNetworks(getCurrentUserId(), isAdmin());
    }
    
    $total_pages = ceil($total_records / $records_per_page);
    $start_record = ($current_page - 1) * $records_per_page + 1;
    $end_record = min($current_page * $records_per_page, $total_records);
    
    // Generar HTML de la tabla
    ob_start();
    ?>
    <table class="social-networks-table" id="socialNetworksTable">
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
                <td>
                    <div class="social-cell">
                        <div class="social-avatar" style="background-color: <?php echo $bg_color; ?>;">
                            <i class="fab <?php echo $icon; ?>" style="color: <?php echo $text_color; ?>;"></i>
                        </div>
                        <div class="social-info">
                            <div class="social-name"><?php echo htmlspecialchars($nombre_red); ?></div>
                            <div class="social-type"><?php echo htmlspecialchars($tipo_red); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="client-badge">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($nombre_cliente); ?></span>
                    </div>
                </td>
                <td>
                    <div class="username-badge">
                        <i class="fas fa-at"></i>
                        <span><?php echo htmlspecialchars($usuario_red); ?></span>
                    </div>
                </td>
                <td>
                    <?php if(!empty($url_red)): ?>
                    <a href="<?php echo htmlspecialchars($url_red); ?>" target="_blank" class="url-link">
                        <i class="fas fa-external-link-alt"></i>
                        <span><?php echo strlen($url_red) > 30 ? substr(htmlspecialchars($url_red), 0, 30) . '...' : htmlspecialchars($url_red); ?></span>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">No disponible</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions-cell">
                        <a href="red-social-view.php?id=<?php echo $id_red; ?>" class="btn-minimal btn-icon btn-secondary" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if(isAdmin()): ?>
                        <a href="red-social-form.php?id=<?php echo $id_red; ?>" class="btn-minimal btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="red-social-delete.php?id=<?php echo $id_red; ?>" class="btn-minimal btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta red social?')">
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
                <td colspan="5" class="empty-row">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <h3 class="empty-title">
                            <?php if (!empty($search_term)): ?>
                                No se encontraron redes sociales que coincidan con "<?php echo htmlspecialchars($search_term); ?>"
                            <?php else: ?>
                                No hay redes sociales disponibles.
                            <?php endif; ?>
                        </h3>
                        <?php if (!empty($search_term)): ?>
                            <p class="empty-description">Intenta con otros términos de búsqueda</p>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php 
            }
            ?>
        </tbody>
    </table>
    <?php
    $table_html = ob_get_clean();
    
    // Generar HTML de paginación
    ob_start();
    if ($total_pages > 1):
    ?>
    <nav aria-label="Paginación de redes sociales" class="pagination-nav">
        <div class="pagination-container">
            <div class="pagination-info-mobile">
                <span class="current-page">Página <?php echo $current_page; ?> de <?php echo $total_pages; ?></span>
            </div>
            
            <ul class="pagination-modern">
                <!-- Botón Anterior -->
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $current_page - 1; ?>" <?php echo ($current_page <= 1) ? 'tabindex="-1"' : ''; ?>>
                        <i class="fas fa-chevron-left"></i>
                        <span class="page-text">Anterior</span>
                    </a>
                </li>

                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link page-dots">...</span></li>';
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = ($i == $current_page) ? 'active' : '';
                    echo '<li class="page-item ' . $active_class . '">';
                    echo '<a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>';
                    echo '</li>';
                }

                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link page-dots">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="#" data-page="' . $total_pages . '">' . $total_pages . '</a></li>';
                }
                ?>

                <!-- Botón Siguiente -->
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $current_page + 1; ?>" <?php echo ($current_page >= $total_pages) ? 'tabindex="-1"' : ''; ?>>
                        <span class="page-text">Siguiente</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
            
            <div class="pagination-summary">
                <small class="text-muted">
                    <?php echo $total_records; ?> redes sociales en total
                </small>
            </div>
        </div>
    </nav>
    <?php
    endif;
    $pagination_html = ob_get_clean();
    
    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'table_html' => $table_html,
        'pagination_html' => $pagination_html,
        'total_records' => $total_records,
        'current_page' => $current_page,
        'total_pages' => $total_pages
    ]);
    exit;
}

// Si no es AJAX, mostrar la página completa
$page_title = "Redes Sociales";

// Datos iniciales para la carga de la página
$records_per_page = 15;
$current_page = 1;
$stmt = $social_network->read(getCurrentUserId(), isAdmin(), $current_page, $records_per_page);
$total_records = $social_network->countSocialNetworks(getCurrentUserId(), isAdmin());

include 'includes/layout_header.php';
?>

<style>
/* Paleta de colores minimalista */
:root {
    --primary-green: #23D950;
    --dark-green: #1ea83e;
    --light-green: #f0fdf4;
    --black: #000000;
    --dark-gray: #1f2937;
    --medium-gray: #6b7280;
    --light-gray: #f8fafc;
    --border-gray: #e5e7eb;
    --white: #ffffff;
}

/* Contenedor principal */
.social-networks-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Header de la página */
.page-header {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
    position: relative;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-green);
    border-radius: 12px 12px 0 0;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-gray);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.title-icon {
    width: 32px;
    height: 32px;
    background: var(--primary-green);
    color: var(--white);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* Barra de búsqueda mejorada */
.search-section {
    background: var(--light-gray);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.search-section:focus-within {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--primary-green);
}

.search-input-container {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    background: var(--white);
    color: var(--dark-gray);
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--medium-gray);
    font-size: 1rem;
    pointer-events: none;
    transition: all 0.3s ease;
}

.search-input:focus + .search-icon {
    color: var(--primary-green);
}

.search-loading {
    position: absolute;
    right: 3rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-green);
}

.search-clear {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--medium-gray);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.search-clear:hover {
    background-color: var(--light-gray);
    color: #ef4444;
}

/* Botones */
.btn-minimal {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    font-size: 0.875rem;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary-green);
    color: var(--white);
    border-color: var(--primary-green);
}

.btn-primary:hover {
    background: var(--dark-green);
    border-color: var(--dark-green);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(35, 217, 80, 0.2);
}

.btn-secondary {
    background: var(--white);
    color: var(--dark-gray);
    border-color: var(--border-gray);
}

.btn-secondary:hover {
    background: var(--light-gray);
    border-color: var(--medium-gray);
}

.btn-warning {
    background: #f59e0b;
    color: var(--white);
    border-color: #f59e0b;
}

.btn-warning:hover {
    background: #d97706;
    border-color: #d97706;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.btn-danger {
    background: #ef4444;
    color: var(--white);
    border-color: #ef4444;
}

.btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
}

/* Tabla de redes sociales */
.social-networks-table-container {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    overflow: hidden;
}

.table-header {
    background: var(--light-gray);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-gray);
}

.table-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin: 0;
}

.social-networks-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.social-networks-table thead th {
    background: var(--light-gray);
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    color: var(--dark-gray);
    border-bottom: 1px solid var(--border-gray);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.social-networks-table tbody td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-gray);
    color: var(--dark-gray);
    vertical-align: middle;
}

.social-networks-table tbody tr:hover {
    background: var(--light-gray);
}

.social-networks-table tbody tr:last-child td {
    border-bottom: none;
}

/* Celdas especiales */
.social-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.social-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.social-info {
    display: flex;
    flex-direction: column;
}

.social-name {
    font-weight: 600;
    color: var(--dark-gray);
}

.social-type {
    font-size: 0.875rem;
    color: var(--medium-gray);
}

.client-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #1565c0;
    font-size: 0.8rem;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.username-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    color: #7b1fa2;
    font-size: 0.8rem;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.url-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.url-link:hover {
    color: var(--dark-green);
    text-decoration: underline;
}

/* Acciones */
.actions-cell {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    justify-content: center;
    border-radius: 8px;
}

/* Paginación moderna */
.pagination-nav {
    margin-top: 2rem;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.pagination-modern {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    overflow: hidden;
}

.pagination-modern .page-item {
    margin: 0;
}

.pagination-modern .page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 12px 16px;
    color: var(--dark-gray);
    text-decoration: none;
    border: none;
    background: var(--white);
    transition: all 0.3s ease;
    font-weight: 500;
    min-width: 48px;
    height: 48px;
}

.pagination-modern .page-link:hover {
    background: var(--light-gray);
    color: var(--primary-green);
    transform: translateY(-1px);
}

.pagination-modern .page-item.active .page-link {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: var(--white);
    box-shadow: 0 2px 8px rgba(35, 217, 80, 0.3);
}

.pagination-modern .page-item.disabled .page-link {
    color: var(--medium-gray);
    background: var(--light-gray);
    cursor: not-allowed;
}

.pagination-modern .page-item.disabled .page-link:hover {
    transform: none;
    background: var(--light-gray);
    color: var(--medium-gray);
}

.pagination-info-mobile {
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 500;
    color: var(--dark-gray);
}

.pagination-summary {
    font-size: 0.875rem;
    color: var(--medium-gray);
}

/* Empty state */
.empty-row {
    padding: 0;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--medium-gray);
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: var(--light-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--medium-gray);
}

.empty-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 0.5rem;
}

.empty-description {
    color: var(--medium-gray);
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .social-networks-container {
        padding: 0 0.5rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .social-networks-table {
        font-size: 0.875rem;
    }
    
    .social-networks-table thead th,
    .social-networks-table tbody td {
        padding: 0.75rem 1rem;
    }
    
    .social-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .actions-cell {
        flex-direction: column;
    }
    
    .pagination-container {
        justify-content: center;
        text-align: center;
    }
    
    .page-text {
        display: none;
    }
    
    .social-networks-table th:nth-child(4),
    .social-networks-table td:nth-child(4) {
        display: none;
    }
}

@media (max-width: 576px) {
    .social-networks-table th:nth-child(3),
    .social-networks-table td:nth-child(3) {
        display: none;
    }
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease;
}
</style>

<div class="social-networks-container">
    <!-- Header de la página -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <div class="title-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                Redes Sociales
            </h1>
            <?php if(isAdmin()): ?>
            <a href="red-social-form.php" class="btn-minimal btn-primary">
                <i class="fas fa-plus"></i>
                Nueva Red Social
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Búsqueda -->
    <div class="search-section">
        <div class="search-input-container">
            <input type="text" 
                   id="searchInput" 
                   class="search-input" 
                   placeholder="Buscar por red social, cliente, usuario o URL..."
                   autocomplete="off">
            <i class="fas fa-search search-icon"></i>
            <div class="search-loading" id="searchLoading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <button type="button" class="search-clear" id="searchClear" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Tabla de redes sociales -->
    <div class="social-networks-table-container">
        <div class="table-header">
            <h3 class="table-title">Lista de Redes Sociales</h3>
        </div>

        <!-- Contenedor de resultados -->
        <div id="socialNetworksContainer">
            <!-- El contenido inicial se carga aquí -->
        </div>
    </div>
</div>

<!-- JavaScript para búsqueda en tiempo real -->
<script>
class SocialNetworkSearch {
    constructor() {
        this.searchInput = document.getElementById('searchInput');
        this.searchLoading = document.getElementById('searchLoading');
        this.searchClear = document.getElementById('searchClear');
        this.socialNetworksContainer = document.getElementById('socialNetworksContainer');
        this.currentPage = 1;
        this.searchTimeout = null;
        this.currentRequest = null;
        
        this.init();
        this.loadInitialData();
    }
    
    init() {
        // Event listeners
        this.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.clearSearch();
            }
        });
        
        this.searchClear.addEventListener('click', () => this.clearSearch());
        
        // Delegación de eventos para paginación
        this.socialNetworksContainer.addEventListener('click', (e) => {
            if (e.target.closest('.page-link') && !e.target.closest('.disabled')) {
                e.preventDefault();
                const page = e.target.closest('.page-link').dataset.page;
                if (page) {
                    this.loadPage(parseInt(page));
                }
            }
        });
    }
    
    loadInitialData() {
        this.performSearch('', 1);
    }
    
    handleSearch(searchTerm) {
        // Cancelar búsqueda anterior
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Mostrar/ocultar botón de limpiar
        this.searchClear.style.display = searchTerm ? 'block' : 'none';
        
        // Debounce la búsqueda
        this.searchTimeout = setTimeout(() => {
            this.currentPage = 1;
            this.performSearch(searchTerm, 1);
        }, 300);
    }
    
    loadPage(page) {
        this.currentPage = page;
        const searchTerm = this.searchInput.value.trim();
        this.performSearch(searchTerm, page);
    }
    
    performSearch(searchTerm, page) {
        // Cancelar petición anterior
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
        
        // Mostrar loading
        this.showLoading(true);
        
        // Crear FormData
        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('page', page);
        
        // Realizar petición AJAX al mismo archivo
        this.currentRequest = fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.updateResults(data);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            if (error.name !== 'AbortError') {
                console.error('Error en la búsqueda:', error);
                this.showError('Error al cargar los datos. Por favor, intenta de nuevo.');
            }
        })
        .finally(() => {
            this.showLoading(false);
            this.currentRequest = null;
        });
    }
    
    updateResults(data) {
        // Actualizar contenido con animación
        this.socialNetworksContainer.style.opacity = '0.5';
        
        setTimeout(() => {
            this.socialNetworksContainer.innerHTML = data.table_html + data.pagination_html;
            this.socialNetworksContainer.style.opacity = '1';
            this.socialNetworksContainer.classList.add('fade-in');
            
            // Scroll suave al inicio de la tabla si no es la primera página
            if (data.current_page > 1) {
                this.socialNetworksContainer.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        }, 150);
    }
    
    showLoading(show) {
        this.searchLoading.style.display = show ? 'block' : 'none';
    }
    
    showError(message) {
        this.socialNetworksContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="empty-title">Error</h3>
                <p class="empty-description">${message}</p>
            </div>
        `;
    }
    
    clearSearch() {
        this.socialNetworksContainer.value = '';
        this.searchClear.style.display = 'none';
        this.currentPage = 1;
        this.performSearch('', 1);
        this.searchInput.focus();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.socialNetworkSearch = new SocialNetworkSearch();
    
    // Añadir animación al cargar la página
    const header = document.querySelector('.page-header');
    const searchSection = document.querySelector('.search-section');
    const tableContainer = document.querySelector('.social-networks-table-container');
    
    // Aplicar animación de entrada
    [header, searchSection, tableContainer].forEach((element, index) => {
        if (element) {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        }
    });
});
</script>

<?php
include 'includes/layout_footer.php';
?>
