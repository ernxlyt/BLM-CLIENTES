<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Plan.php';
include_once 'utils/session.php';

// Require login and admin
requireLogin();
requireAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize plan object
$plan = new Plan($db);

// Set page title
$page_title = "Planes";

// Get all plans
$stmt = $plan->read();

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
.plans-container {
    max-width: 1200px;
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

/* Tabla de planes */
.plans-table-container {
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

.plans-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.plans-table thead th {
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

.plans-table tbody td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-gray);
    color: var(--dark-gray);
    vertical-align: middle;
}

.plans-table tbody tr:hover {
    background: var(--light-gray);
}

.plans-table tbody tr:last-child td {
    border-bottom: none;
}

/* Celdas especiales */
.plan-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.plan-avatar {
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

.plan-name {
    font-weight: 600;
    color: var(--dark-gray);
}

.price-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: var(--white);
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(35, 217, 80, 0.2);
    transition: all 0.2s ease;
}

.price-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(35, 217, 80, 0.3);
}

.description-text {
    color: var(--medium-gray);
    font-size: 0.875rem;
    line-height: 1.4;
    max-width: 300px;
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
    .plans-container {
        padding: 0 0.5rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .plans-table {
        font-size: 0.875rem;
    }
    
    .plans-table thead th,
    .plans-table tbody td {
        padding: 0.75rem 1rem;
    }
    
    .plan-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .actions-cell {
        flex-direction: column;
    }
}
</style>

<div class="plans-container">
    <!-- Header de la página -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <div class="title-icon">
                    <i class="fas fa-tag"></i>
                </div>
                Planes
            </h1>
            <a href="plan-form.php" class="btn-minimal btn-primary">
                <i class="fas fa-plus"></i>
                Nuevo Plan
            </a>
        </div>
    </div>

    <!-- Búsqueda -->
    <div class="search-section">
        <div class="search-input-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Buscar por nombre o descripción...">
            <i class="fas fa-search search-icon"></i>
        </div>
    </div>

    <!-- Tabla de planes -->
    <div class="plans-table-container">
        <div class="table-header">
            <h3 class="table-title">Lista de Planes</h3>
        </div>

        <?php if ($stmt->rowCount() > 0): ?>
        <table class="plans-table" id="plansTable">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td>
                        <div class="plan-cell">
                            <div class="plan-avatar">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="plan-name"><?php echo htmlspecialchars($row['nombre_plan']); ?></div>
                        </div>
                    </td>
                    <td>
                        <div class="description-text">
                            <?php echo htmlspecialchars($row['descripcion_plan']); ?>
                        </div>
                    </td>
                    <td>
                        <div class="price-badge">
                            <i class="fas fa-dollar-sign"></i>
                            <span><?php echo number_format($row['precio'], 2); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <button class="btn-minimal btn-icon btn-secondary" title="Ver Plan">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="plan-form.php?id=<?php echo $row['id_plan']; ?>" 
                               class="btn-minimal btn-icon btn-warning" title="Editar Plan">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="plan-delete.php?id=<?php echo $row['id_plan']; ?>" 
                               class="btn-minimal btn-icon btn-danger" title="Eliminar Plan"
                               onclick="return confirm('¿Está seguro de eliminar este plan?')">
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
                <i class="fas fa-tag"></i>
            </div>
            <h3 class="empty-title">No hay planes disponibles</h3>
            <p class="empty-description">Comienza creando tu primer plan.</p>
            <a href="plan-form.php" class="btn-minimal btn-primary">
                <i class="fas fa-plus"></i>
                Crear Primer Plan
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
    const tableBody = document.querySelector('#plansTable tbody');
    const tableRows = document.querySelectorAll('#plansTable tbody tr');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const plansTable = document.getElementById('plansTable');

    // Función para filtrar los planes
    function filterPlans() {
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

        // Mostrar o ocultar el mensaje de "No se encuentran planes"
        if (hasResults) {
            noResultsMessage.style.display = 'none';
            plansTable.style.display = '';
        } else {
            noResultsMessage.style.display = '';
            plansTable.style.display = 'none';
        }
    }

    // Función para limpiar la búsqueda
    function clearSearch() {
        searchInput.value = '';
        filterPlans();
        searchInput.focus();
    }

    // Evento de búsqueda
    searchInput.addEventListener('keyup', filterPlans);
    
    // Añadir animación al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.querySelector('.page-header');
        const searchSection = document.querySelector('.search-section');
        const tableContainer = document.querySelector('.plans-table-container');
        
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