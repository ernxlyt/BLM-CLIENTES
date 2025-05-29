<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'utils/session.php';

// Require login and admin
requireLogin();
requireAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize user object
$user = new User($db);

// Set page title
$page_title = "Usuarios";

// Get all users
$stmt = $user->read();

// Include header
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
.users-container {
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

/* Tabla de usuarios */
.users-table-container {
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

.users-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.users-table thead th {
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

.users-table tbody td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-gray);
    color: var(--dark-gray);
    vertical-align: middle;
}

.users-table tbody tr:hover {
    background: var(--light-gray);
}

.users-table tbody tr:last-child td {
    border-bottom: none;
}

/* Celdas especiales */
.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: var(--light-green);
    color: var(--primary-green);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.user-name {
    font-weight: 600;
    color: var(--dark-gray);
}

.user-email {
    color: var(--medium-gray);
    font-size: 0.875rem;
}

/* Badges mejorados */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.badge-admin {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: var(--white);
}

.badge-user {
    background: linear-gradient(135deg, var(--light-gray) 0%, #e5e7eb 100%);
    color: var(--dark-gray);
}

.badge-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: var(--white);
}

.badge-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: var(--white);
}

.stats-cell {
    text-align: center;
}

.stats-detail {
    font-size: 0.75rem;
    color: var(--medium-gray);
    margin-top: 0.25rem;
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

/* Empty state */
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
    .users-container {
        padding: 0 0.5rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .users-table {
        font-size: 0.875rem;
    }
    
    .users-table thead th,
    .users-table tbody td {
        padding: 0.75rem 1rem;
    }
    
    .user-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .actions-cell {
        flex-direction: column;
    }
}
</style>

<div class="users-container">
    <!-- Header de la página -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <div class="title-icon">
                    <i class="fas fa-users"></i>
                </div>
                Usuarios
            </h1>
            <a href="user-form.php" class="btn-minimal btn-primary">
                <i class="fas fa-plus"></i>
                Nuevo Usuario
            </a>
        </div>
    </div>

    <!-- Búsqueda -->
    <div class="search-section">
        <div class="search-input-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Buscar por correo, usuario o rol...">
            <i class="fas fa-search search-icon"></i>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="users-table-container">
        <div class="table-header">
            <h3 class="table-title">Lista de Usuarios</h3>
        </div>

        <?php if ($stmt->rowCount() > 0): ?>
        <table class="users-table" id="usersTable">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Clientes</th>
                    <th>Servicios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                    extract($row);
                    
                    // Obtener estadísticas de asignaciones para cada usuario
                    $tempUser = new User($db);
                    $tempUser->id_usuario = $id_usuario;
                    $stats = $tempUser->getAssignmentStats();
                ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="user-name"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="user-email"><?php echo htmlspecialchars($correo_usuario); ?></div>
                    </td>
                    <td>
                        <div class="badge <?php echo ($nombre_rol === 'Administrador') ? 'badge-admin' : 'badge-user'; ?>">
                            <i class="fas fa-<?php echo ($nombre_rol === 'Administrador') ? 'crown' : 'user'; ?>"></i>
                            <span><?php echo htmlspecialchars($nombre_rol); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="stats-cell">
                            <?php if ($stats['total_clientes'] > 0): ?>
                                <div class="badge badge-success">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $stats['total_clientes']; ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="stats-cell">
                            <?php if ($stats['total_servicios'] > 0): ?>
                                <div class="badge badge-info">
                                    <i class="fas fa-cogs"></i>
                                    <span><?php echo $stats['total_servicios']; ?></span>
                                </div>
                                <?php if ($stats['tipos_servicio_unicos'] > 0): ?>
                                <div class="stats-detail">
                                    <?php echo $stats['tipos_servicio_unicos']; ?> tipo<?php echo $stats['tipos_servicio_unicos'] > 1 ? 's' : ''; ?>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="user-view.php?id=<?php echo $id_usuario; ?>" 
                               class="btn-minimal btn-icon btn-secondary" title="Ver Usuario">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="user-form.php?id=<?php echo $id_usuario; ?>" 
                               class="btn-minimal btn-icon btn-warning" title="Editar Usuario">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="user-delete.php?id=<?php echo $id_usuario; ?>" 
                               class="btn-minimal btn-icon btn-danger" title="Eliminar Usuario"
                               onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="empty-title">No hay usuarios disponibles</h3>
            <p class="empty-description">Comienza creando tu primer usuario.</p>
            <a href="user-form.php" class="btn-minimal btn-primary">
                <i class="fas fa-plus"></i>
                Crear Primer Usuario
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Mensaje si no hay resultados de búsqueda -->
        <div id="noResultsMessage" class="empty-state" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="empty-title">No se encontraron resultados</h3>
            <p class="empty-description">Intenta con otros términos de búsqueda.</p>
            <button onclick="clearSearch()" class="btn-minimal btn-secondary">
                <i class="fas fa-times"></i>
                Limpiar Búsqueda
            </button>
        </div>
    </div>
</div>

<!-- Lógica de búsqueda mejorada -->
<script>
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.querySelector('#usersTable tbody');
    const tableRows = document.querySelectorAll('#usersTable tbody tr');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const usersTable = document.getElementById('usersTable');

    // Función para filtrar los usuarios
    function filterUsers() {
        const filter = searchInput.value.toLowerCase();
        let hasResults = false;
        
        // Si la tabla no existe, no hacer nada
        if (!tableRows || tableRows.length === 0) return;

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
        if (hasResults) {
            noResultsMessage.style.display = 'none';
            usersTable.style.display = '';
        } else {
            noResultsMessage.style.display = '';
            usersTable.style.display = 'none';
        }
    }

    // Función para limpiar la búsqueda
    function clearSearch() {
        searchInput.value = '';
        filterUsers();
        searchInput.focus();
    }

    // Evento de búsqueda
    searchInput.addEventListener('keyup', filterUsers);
    
    // Añadir animación al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.querySelector('.page-header');
        const searchSection = document.querySelector('.search-section');
        const tableContainer = document.querySelector('.users-table-container');
        
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