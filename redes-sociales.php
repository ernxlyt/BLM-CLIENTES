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
    <!-- Información de paginación -->
    

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
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($nombre_red); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($tipo_red); ?></div>
                    </div>
                </td>
                <td>
                    <span class="client-badge">
                        <i class="fas fa-user mr-1"></i>
                        <?php echo htmlspecialchars($nombre_cliente); ?>
                    </span>
                </td>
                <td>
                    <span class="username-badge">
                        <i class="fas fa-at mr-1"></i>
                        <?php echo htmlspecialchars($usuario_red); ?>
                    </span>
                </td>
                <td>
                    <?php if(!empty($url_red)): ?>
                    <a href="<?php echo htmlspecialchars($url_red); ?>" target="_blank" class="url-link">
                        <i class="fas fa-external-link-alt mr-1"></i>
                        <?php echo strlen($url_red) > 30 ? substr(htmlspecialchars($url_red), 0, 30) . '...' : htmlspecialchars($url_red); ?>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">No disponible</span>
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
                <td colspan="5" class="text-center no-results">
                    <div class="no-results-content">
                        <i class="fas fa-share-alt fa-3x mb-3 text-muted"></i>
                        <h5 class="text-muted">
                            <?php if (!empty($search_term)): ?>
                                No se encontraron redes sociales que coincidan con "<?php echo htmlspecialchars($search_term); ?>"
                            <?php else: ?>
                                No hay redes sociales disponibles.
                            <?php endif; ?>
                        </h5>
                        <?php if (!empty($search_term)): ?>
                            <p class="text-muted">Intenta con otros términos de búsqueda</p>
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
    <nav aria-label="Paginación de redes sociales" class="mt-4">
        <div class="pagination-container">
            <div class="pagination-info-mobile d-md-none">
                <span class="current-page">Página <?php echo $current_page; ?> de <?php echo $total_pages; ?></span>
            </div>
            
            <ul class="pagination-modern">
                <!-- Botón Anterior -->
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $current_page - 1; ?>" <?php echo ($current_page <= 1) ? 'tabindex="-1"' : ''; ?>>
                        <i class="fas fa-chevron-left"></i>
                        <span class="d-none d-sm-inline ml-1">Anterior</span>
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
                        <span class="d-none d-sm-inline mr-1">Siguiente</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
            
            <div class="pagination-summary d-none d-md-block">
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

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-share-alt mr-2"></i>
            Lista de Redes Sociales
        </h2>
        <?php if(isAdmin()): ?>
        <a href="red-social-form.php" class="btn btn-primary btn-modern">
            <i class="fas fa-plus mr-2"></i> Nueva Red Social
        </a>
        <?php endif; ?>
    </div>

    <!-- Barra de búsqueda mejorada -->
    <div class="search-container mb-4">
        <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" 
                   id="searchInput" 
                   class="form-control search-input" 
                   placeholder="Buscar por red social, cliente, usuario o URL..."
                   autocomplete="off">
            <div class="search-loading" id="searchLoading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <button type="button" class="search-clear" id="searchClear" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Contenedor de resultados -->
    <div id="socialNetworksContainer">
        <!-- El contenido inicial se carga aquí -->
    </div>
</div>

<!-- Estilos CSS -->
<style>
/* Estilos para la búsqueda */
.search-container {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
    padding: 0 20px;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 15px;
    color: #6c757d;
    z-index: 2;
}

.search-input {
    padding: 12px 50px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    font-size: 16px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-input:focus {
    border-color: #28a745;
    box-shadow: 0 4px 12px rgba(40,167,69,0.15);
    outline: none;
}

.search-loading {
    position: absolute;
    right: 45px;
    color: #28a745;
}

.search-clear {
    position: absolute;
    right: 15px;
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.search-clear:hover {
    background-color: #f8f9fa;
    color: #dc3545;
}

/* Estilos para la tabla de redes sociales */
.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
    color: #495057;
}

.user-role {
    font-size: 0.875rem;
    color: #6c757d;
}

.client-badge {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #1565c0;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.875rem;
    border: 1px solid #90caf9;
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}

.username-badge {
    background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    color: #7b1fa2;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.875rem;
    border: 1px solid #ce93d8;
    display: inline-flex;
    align-items: center;
    font-weight: 500;
}

.url-link {
    color: #28a745;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.url-link:hover {
    color: #1e7e34;
    text-decoration: underline;
}

/* Estilos para el paginador moderno - TEMA VERDE */
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
    background: white;
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
    padding: 12px 16px;
    color: #495057;
    text-decoration: none;
    border: none;
    background: white;
    transition: all 0.3s ease;
    font-weight: 500;
    min-width: 48px;
    height: 48px;
}

.pagination-modern .page-link:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #28a745;
    transform: translateY(-1px);
}

.pagination-modern .page-item.active .page-link {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(40,167,69,0.3);
}

.pagination-modern .page-item.disabled .page-link {
    color: #adb5bd;
    background: #f8f9fa;
    cursor: not-allowed;
}

.pagination-modern .page-item.disabled .page-link:hover {
    transform: none;
    background: #f8f9fa;
    color: #adb5bd;
}

.page-dots {
    cursor: default !important;
}

.pagination-info-mobile {
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 500;
    color: #495057;
}

.pagination-summary {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Estilos para no resultados */
.no-results {
    padding: 3rem 1rem;
}

.no-results-content {
    text-align: center;
}

.no-results-content i {
    opacity: 0.5;
}

/* Botón moderno */
.btn-modern {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Responsive */
@media (max-width: 768px) {
    .search-container {
        padding: 0 15px;
    }
    
    .search-input {
        font-size: 16px;
    }
    
    .pagination-container {
        justify-content: center;
        text-align: center;
    }
    
    .pagination-modern .page-link {
        padding: 10px 12px;
        min-width: 40px;
        height: 40px;
        font-size: 0.875rem;
    }
    
    .user-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .data-table th:nth-child(4),
    .data-table td:nth-child(4) {
        display: none;
    }
}

@media (max-width: 576px) {
    .data-table th:nth-child(3),
    .data-table td:nth-child(3) {
        display: none;
    }
    
    .pagination-modern .page-link span {
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
        this.searchInput.style.paddingRight = show ? '80px' : '50px';
    }
    
    showError(message) {
        this.socialNetworksContainer.innerHTML = `
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                ${message}
            </div>
        `;
    }
    
    clearSearch() {
        this.searchInput.value = '';
        this.searchClear.style.display = 'none';
        this.currentPage = 1;
        this.performSearch('', 1);
        this.searchInput.focus();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.socialNetworkSearch = new SocialNetworkSearch();
});
</script>

<?php
// Include footer
include 'includes/layout_footer.php';
?>