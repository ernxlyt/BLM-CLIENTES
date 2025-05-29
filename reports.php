<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Report.php';
include_once 'utils/session.php';

// Require login and admin access
requireLogin();
requireAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize report object
$report = new Report($db);

// Set page title
$page_title = "Reportes de Transacciones";

// Get all reports (no search processing on server side now)
$reports_stmt = $report->read(getCurrentUserId(), isAdmin());

// Get total count
$total_reports = $report->countReports(getCurrentUserId(), isAdmin());

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
.reports-container {
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

/* Sección de filtros mejorada */
.filters-section {
    background: var(--white);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.filters-section:focus-within {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: var(--primary-green);
}

.filters-grid {
    display: grid;
    grid-template-columns: 1fr auto auto auto;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--dark-gray);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.search-input-container {
    position: relative;
}

.search-input, .date-input {
    width: 100%;
    padding: 0.625rem 1rem 0.625rem 2.5rem;
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    background: var(--light-gray);
    color: var(--dark-gray);
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.date-input {
    padding-left: 1rem;
}

.search-input:focus, .date-input:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
    background: var(--white);
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--medium-gray);
    font-size: 0.875rem;
    pointer-events: none;
    transition: all 0.3s ease;
}

.search-input:focus + .search-icon {
    color: var(--primary-green);
}

/* Botones de filtro y exportación */
.filter-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-minimal {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    font-size: 0.75rem;
    cursor: pointer;
    white-space: nowrap;
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

.btn-success {
    background: #10b981;
    color: var(--white);
    border-color: #10b981;
}

.btn-success:hover {
    background: #059669;
    border-color: #059669;
    transform: translateY(-1px);
}

.btn-black {
    background: var(--black);
    color: var(--white);
    border-color: var(--black);
}

.btn-black:hover {
    background: var(--dark-gray);
    border-color: var(--dark-gray);
    transform: translateY(-1px);
}

/* Indicador de filtros activos */
.active-filters {
    margin-top: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.filter-tag {
    background: var(--light-green);
    color: var(--dark-green);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.filter-tag .remove-filter {
    cursor: pointer;
    color: var(--dark-green);
    font-weight: bold;
}

.filter-tag .remove-filter:hover {
    color: var(--primary-green);
}

/* Estadísticas */
.stats-section {
    background: var(--light-green);
    border: 1px solid var(--primary-green);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stats-icon {
    width: 36px;
    height: 36px;
    background: var(--primary-green);
    color: var(--white);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.stats-text {
    flex: 1;
}

.stats-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-gray);
    line-height: 1;
}

.stats-label {
    color: var(--medium-gray);
    font-size: 0.75rem;
    font-weight: 500;
}

/* Tabla de reportes - VERSIÓN COMPACTA */
.reports-table-container {
    background: var(--white);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    overflow: hidden;
}

.table-header {
    background: var(--light-gray);
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-gray);
}

.table-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin: 0;
}

.reports-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem;
}

.reports-table thead th {
    background: var(--light-gray);
    padding: 0.625rem 0.75rem;
    text-align: left;
    font-weight: 600;
    color: var(--dark-gray);
    border-bottom: 1px solid var(--border-gray);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.reports-table tbody td {
    padding: 0.625rem 0.75rem;
    border-bottom: 1px solid var(--border-gray);
    color: var(--dark-gray);
    vertical-align: middle;
}

.reports-table tbody tr:hover {
    background: var(--light-gray);
}

.reports-table tbody tr:last-child td {
    border-bottom: none;
}

/* Celdas especiales - VERSIÓN COMPACTA */
.client-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.client-avatar {
    width: 32px;
    height: 32px;
    background: var(--light-green);
    color: var(--primary-green);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}

.client-info {
    flex: 1;
    min-width: 0;
}

.client-name {
    font-weight: 500;
    color: var(--dark-gray);
    margin-bottom: 0.125rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.client-details {
    font-size: 0.6875rem;
    color: var(--medium-gray);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.amount-cell {
    font-weight: 600;
    color: var(--primary-green);
    font-size: 0.875rem;
    white-space: nowrap;
}

/* Método de pago mejorado - VERSIÓN COMPACTA */
.method-container {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.method-badge-improved {
    background: linear-gradient(135deg, var(--black) 0%, var(--dark-gray) 100%);
    color: var(--white);
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0.025em;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.method-badge-improved:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
}

.method-icon {
    font-size: 0.75rem;
    flex-shrink: 0;
}

.method-text {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.date-cell {
    font-weight: 500;
    color: var(--dark-gray);
    font-size: 0.75rem;
    white-space: nowrap;
}

.reference-cell {
    font-family: monospace;
    background: var(--light-gray);
    padding: 0.25rem 0.375rem;
    border-radius: 4px;
    font-size: 0.6875rem;
    color: var(--medium-gray);
    max-width: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Acciones - VERSIÓN COMPACTA */
.actions-cell {
    display: flex;
    gap: 0.25rem;
}

.btn-icon {
    width: 28px;
    height: 28px;
    padding: 0;
    justify-content: center;
    border-radius: 6px;
    font-size: 0.75rem;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--medium-gray);
}

.empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: var(--light-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--medium-gray);
}

.empty-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 0.5rem;
}

.empty-description {
    color: var(--medium-gray);
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
    .reports-container {
        padding: 0 0.5rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .filter-buttons {
        justify-content: stretch;
    }
    
    .filter-buttons .btn-minimal {
        flex: 1;
        justify-content: center;
    }
    
    .reports-table {
        font-size: 0.75rem;
    }
    
    .reports-table thead th,
    .reports-table tbody td {
        padding: 0.5rem 0.625rem;
    }
    
    .client-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .actions-cell {
        flex-direction: column;
    }
}

/* Ocultar columnas en móvil */
@media (max-width: 992px) {
    .reports-table .hide-mobile {
        display: none;
    }
}
</style>

<div class="reports-container">
    <!-- Header de la página -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <div class="title-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                Reportes de Transacciones
            </h1>
            <a href="report-form.php" class="btn-minimal btn-primary">
                <i class="fas fa-plus"></i>
                Nuevo Reporte
            </a>
        </div>
    </div>

    <!-- Sección de filtros con botones de exportación -->
    <div class="filters-section">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Búsqueda General</label>
                <div class="search-input-container">
                    <input type="text" id="searchInput" class="search-input" placeholder="Buscar por cliente, método de pago, referencia o notas...">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Fecha Desde</label>
                <input type="date" id="dateFrom" class="date-input">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Fecha Hasta</label>
                <input type="date" id="dateTo" class="date-input">
            </div>
            
            <div class="filter-buttons">
                <button onclick="exportToExcel()" class="btn-minimal btn-success" title="Exportar a Excel con filtros aplicados">
                    <i class="fas fa-file-excel"></i>
                    Exportar Excel
                </button>
                <button onclick="clearAllFilters()" class="btn-minimal btn-secondary" title="Limpiar todos los filtros">
                    <i class="fas fa-times"></i>
                    Limpiar
                </button>
            </div>
        </div>
        
        <!-- Indicadores de filtros activos -->
        <div id="activeFilters" class="active-filters" style="display: none;"></div>
    </div>

    <!-- Estadísticas -->
    <div class="stats-section">
        <div class="stats-icon">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="stats-text">
            <div class="stats-number" id="visibleCount"><?php echo $total_reports; ?></div>
            <div class="stats-label">
                <span id="statsLabel">
                    <?php echo $total_reports == 1 ? 'Reporte registrado' : 'Reportes registrados'; ?>
                </span>
                <span id="totalCount" style="color: var(--medium-gray); margin-left: 0.5rem;">
                    (de <?php echo $total_reports; ?> total)
                </span>
            </div>
        </div>
    </div>

    <!-- Tabla de reportes -->
    <div class="reports-table-container">
        <div class="table-header">
            <h3 class="table-title">Lista de Reportes</h3>
        </div>

        <?php if($reports_stmt->rowCount() > 0): ?>
        <table class="reports-table" id="reportsTable">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Método de Pago</th>
                    <th>Monto</th>
                    <th>Referencia</th>
                    <th class="hide-mobile">Período</th>
                    <th class="hide-mobile">Creado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $reports_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr data-date="<?php echo $row['fecha_transaccion']; ?>">
                    <td>
                        <div class="client-cell">
                            <div class="client-avatar">
                                <?php echo strtoupper(substr($row['nombre_cliente'], 0, 2)); ?>
                            </div>
                            <div class="client-info">
                                <div class="client-name"><?php echo htmlspecialchars($row['nombre_cliente']); ?></div>
                                <div class="client-details">
                                    <?php if($row['nombre_plan']): ?>
                                        <?php echo htmlspecialchars($row['nombre_plan']); ?>
                                    <?php endif; ?>
                                    <?php if($row['nombre_empresa']): ?>
                                        • <?php echo htmlspecialchars($row['nombre_empresa']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="date-cell">
                            <?php echo date('d/m/Y', strtotime($row['fecha_transaccion'])); ?>
                        </div>
                    </td>
                    <td>
                        <div class="method-container">
                            <span class="method-badge-improved">
                                <i class="fas fa-credit-card method-icon"></i>
                                <span class="method-text"><?php echo htmlspecialchars($row['metodo_pago']); ?></span>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?php if($row['monto']): ?>
                            <div class="amount-cell">
                                $<?php echo number_format($row['monto'], 2); ?>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--medium-gray); font-style: italic; font-size: 0.75rem;">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['numero_referencia']): ?>
                            <span class="reference-cell" title="<?php echo htmlspecialchars($row['numero_referencia']); ?>">
                                <?php echo htmlspecialchars($row['numero_referencia']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--medium-gray); font-style: italic; font-size: 0.75rem;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile">
                        <?php if($row['fecha_desde'] && $row['fecha_hasta']): ?>
                            <div style="font-size: 0.75rem;">
                                <?php echo date('d/m/Y', strtotime($row['fecha_desde'])); ?>
                                <span style="color: var(--medium-gray);">→</span>
                                <?php echo date('d/m/Y', strtotime($row['fecha_hasta'])); ?>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--medium-gray); font-style: italic; font-size: 0.75rem;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile">
                        <div style="font-size: 0.75rem;">
                            <?php echo htmlspecialchars($row['nombre_usuario'] ?? 'Sistema'); ?>
                            <div style="font-size: 0.6875rem; color: var(--medium-gray);">
                                <?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="report-view.php?id=<?php echo $row['id_reporte']; ?>" 
                               class="btn-minimal btn-icon btn-secondary" title="Ver Reporte">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if(isAdmin()): ?>
                            <a href="report-form.php?id=<?php echo $row['id_reporte']; ?>" 
                               class="btn-minimal btn-icon btn-primary" title="Editar Reporte">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="report-delete.php?id=<?php echo $row['id_reporte']; ?>" 
                               class="btn-minimal btn-icon btn-black" title="Eliminar Reporte"
                               onclick="return confirm('¿Está seguro de eliminar este reporte?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="empty-title">No hay reportes registrados</h3>
            <p class="empty-description">Comienza creando tu primer reporte de transacción.</p>
            <a href="report-form.php" class="btn-minimal btn-primary">
                <i class="fas fa-plus"></i>
                Crear Primer Reporte
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Mensaje si no hay resultados de búsqueda -->
        <div id="noResultsMessage" class="empty-state" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="empty-title">No se encontraron resultados</h3>
            <p class="empty-description">Intenta con otros términos de búsqueda o ajusta los filtros de fecha.</p>
            <button onclick="clearAllFilters()" class="btn-minimal btn-secondary">
                <i class="fas fa-times"></i>
                Limpiar Filtros
            </button>
        </div>
    </div>
</div>

<!-- JavaScript para filtros y exportación -->
<script>
    const searchInput = document.getElementById('searchInput');
    const dateFromInput = document.getElementById('dateFrom');
    const dateToInput = document.getElementById('dateTo');
    const tableBody = document.querySelector('#reportsTable tbody');
    const tableRows = document.querySelectorAll('#reportsTable tbody tr');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const reportsTable = document.getElementById('reportsTable');
    const activeFiltersContainer = document.getElementById('activeFilters');
    const visibleCountElement = document.getElementById('visibleCount');
    const statsLabelElement = document.getElementById('statsLabel');
    const totalCountElement = document.getElementById('totalCount');

    // Función para filtrar los reportes
    function filterReports() {
        const searchFilter = searchInput.value.toLowerCase();
        const dateFrom = dateFromInput.value;
        const dateTo = dateToInput.value;
        let hasResults = false;
        let visibleCount = 0;
        
        if (!tableRows || tableRows.length === 0) return;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const textContent = Array.from(cells).map(cell => cell.innerText.toLowerCase()).join(' ');
            const rowDate = row.getAttribute('data-date');
            
            let showRow = true;

            // Filtro de texto
            if (searchFilter && !textContent.includes(searchFilter)) {
                showRow = false;
            }

            // Filtro de fecha desde
            if (dateFrom && rowDate < dateFrom) {
                showRow = false;
            }

            // Filtro de fecha hasta
            if (dateTo && rowDate > dateTo) {
                showRow = false;
            }

            if (showRow) {
                row.style.display = '';
                hasResults = true;
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        updateVisibleCount(visibleCount);

        if (hasResults) {
            noResultsMessage.style.display = 'none';
            reportsTable.style.display = '';
        } else {
            noResultsMessage.style.display = '';
            reportsTable.style.display = 'none';
        }

        updateActiveFilters();
    }

    // Función para actualizar el contador de resultados visibles
    function updateVisibleCount(count) {
        visibleCountElement.textContent = count;
        statsLabelElement.textContent = count === 1 ? 'Reporte visible' : 'Reportes visibles';
        
        if (count === tableRows.length) {
            totalCountElement.style.display = 'none';
        } else {
            totalCountElement.style.display = 'inline';
        }
    }

    // Función para actualizar los indicadores de filtros activos
    function updateActiveFilters() {
        const activeFilters = [];
        
        if (searchInput.value.trim()) {
            activeFilters.push({
                type: 'search',
                label: `Búsqueda: "${searchInput.value}"`,
                value: searchInput.value
            });
        }
        
        if (dateFromInput.value) {
            const date = new Date(dateFromInput.value);
            activeFilters.push({
                type: 'dateFrom',
                label: `Desde: ${date.toLocaleDateString('es-ES')}`,
                value: dateFromInput.value
            });
        }
        
        if (dateToInput.value) {
            const date = new Date(dateToInput.value);
            activeFilters.push({
                type: 'dateTo',
                label: `Hasta: ${date.toLocaleDateString('es-ES')}`,
                value: dateToInput.value
            });
        }

        if (activeFilters.length > 0) {
            activeFiltersContainer.style.display = 'flex';
            activeFiltersContainer.innerHTML = activeFilters.map(filter => `
                <span class="filter-tag">
                    ${filter.label}
                    <span class="remove-filter" onclick="removeFilter('${filter.type}')">&times;</span>
                </span>
            `).join('');
        } else {
            activeFiltersContainer.style.display = 'none';
        }
    }

    // Función para remover un filtro específico
    function removeFilter(type) {
        switch(type) {
            case 'search':
                searchInput.value = '';
                break;
            case 'dateFrom':
                dateFromInput.value = '';
                break;
            case 'dateTo':
                dateToInput.value = '';
                break;
        }
        filterReports();
    }

    // Función para limpiar todos los filtros
    function clearAllFilters() {
        searchInput.value = '';
        dateFromInput.value = '';
        dateToInput.value = '';
        filterReports();
        searchInput.focus();
    }

    // FUNCIÓN DE EXPORTACIÓN A EXCEL
    function exportToExcel() {
        showLoadingNotification('Generando archivo Excel...');
        
        // Crear URL con parámetros para el script PHP
        const params = new URLSearchParams({
            search: searchInput.value,
            date_from: dateFromInput.value,
            date_to: dateToInput.value
        });
        
        // Crear un enlace temporal para la descarga
        const link = document.createElement('a');
        link.href = 'reports-export-excel.php?' + params.toString();
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Mostrar mensaje de éxito después de un breve delay
        setTimeout(() => {
            hideLoadingNotification();
            showNotification('Archivo Excel generado y descargado exitosamente', 'success');
        }, 2000);
    }

    // Funciones de notificación
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? 'var(--primary-green)' : 
                       type === 'warning' ? '#f59e0b' : 
                       type === 'error' ? '#ef4444' : 'var(--primary-green)';
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bgColor};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 300px;
        `;
        
        const icon = type === 'success' ? 'fa-check-circle' : 
                    type === 'warning' ? 'fa-exclamation-triangle' : 
                    type === 'error' ? 'fa-times-circle' : 'fa-info-circle';
        
        notification.innerHTML = `
            <i class="fas ${icon}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover después de 4 segundos
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    function showLoadingNotification(message) {
        const notification = document.createElement('div');
        notification.id = 'loadingNotification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--dark-gray);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        notification.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
    }

    function hideLoadingNotification() {
        const notification = document.getElementById('loadingNotification');
        if (notification) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }
    }

    // Eventos de filtrado en tiempo real
    searchInput.addEventListener('keyup', filterReports);
    dateFromInput.addEventListener('change', filterReports);
    dateToInput.addEventListener('change', filterReports);
    
    // Validación de fechas
    dateFromInput.addEventListener('change', function() {
        if (dateToInput.value && this.value > dateToInput.value) {
            showNotification('La fecha "Desde" no puede ser mayor que la fecha "Hasta"', 'warning');
            this.value = '';
            filterReports();
        }
    });

    dateToInput.addEventListener('change', function() {
        if (dateFromInput.value && this.value < dateFromInput.value) {
            showNotification('La fecha "Hasta" no puede ser menor que la fecha "Desde"', 'warning');
            this.value = '';
            filterReports();
        }
    });
    
    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.querySelector('.page-header');
        const filtersSection = document.querySelector('.filters-section');
        const statsSection = document.querySelector('.stats-section');
        const tableContainer = document.querySelector('.reports-table-container');
        
        [header, filtersSection, statsSection, tableContainer].forEach((element, index) => {
            if (element) {
                element.style.opacity = '0';
                element.style.transform = 'translateY(10px)';
                element.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 * (index + 1));
            }
        });

        updateVisibleCount(tableRows.length);
    });
</script>

<?php
include 'includes/layout_footer.php';
?>
