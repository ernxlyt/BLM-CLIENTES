<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Report.php';
include_once 'utils/session.php';

// Require login
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize report object
$report = new Report($db);

// Check if ID is set
if(!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: reports.php");
  exit();
}

// Set report ID
$report->id_reporte = $_GET['id'];

// Check if report exists and user has access
if(!$report->readOne(getCurrentUserId(), isAdmin())) {
  header("Location: reports.php");
  exit();
}

// Set page title
$page_title = "Detalles del Reporte";

// Include header
include 'includes/layout_header.php';
?>

<style>
/* Usar los mismos estilos minimalistas del client-view */
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

.report-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.success-message {
    background-color: var(--light-green);
    border: 1px solid var(--primary-green);
    color: var(--dark-green);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.main-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header-minimal {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border-gray);
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title-minimal {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin: 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    padding: 2rem;
}

.info-card {
    background: var(--white);
    border: 2px solid var(--border-gray);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-green);
}

.info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--black);
    border-radius: 12px 12px 0 0;
}

.info-card.accent-green::before {
    background: var(--primary-green);
}

.info-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.2rem;
    background: var(--black);
    color: var(--white);
}

.info-icon.green {
    background: var(--primary-green);
}

.info-title {
    font-weight: 600;
    color: var(--medium-gray);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-content {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark-gray);
    line-height: 1.4;
}

.info-content.secondary {
    color: var(--medium-gray);
    font-style: italic;
    font-weight: 400;
}

.amount-display {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--primary-green);
}

.method-badge {
    background: var(--black);
    color: var(--white);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.reference-display {
    font-family: monospace;
    background: var(--light-gray);
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    color: var(--dark-gray);
    border: 1px solid var(--border-gray);
}

.period-display {
    background: var(--light-green);
    border: 1px solid var(--primary-green);
    border-radius: 8px;
    padding: 1rem;
}

.period-dates {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 500;
}

.period-arrow {
    color: var(--primary-green);
    font-size: 1.2rem;
}

.notes-display {
    background: var(--light-gray);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1rem;
    white-space: pre-wrap;
    word-wrap: break-word;
    line-height: 1.5;
}

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

@media (max-width: 768px) {
    .report-container {
        padding: 0 0.5rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        padding: 1rem;
    }
    
    .info-card {
        padding: 1rem;
    }
    
    .card-header-minimal {
        padding: 1rem 1.5rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.info-card {
    animation: fadeIn 0.3s ease-out;
}
</style>

<div class="report-container">
    <!-- Mensaje de éxito -->
    <?php if(isset($_GET['success'])): ?>
    <div class="success-message">
        <i class="fas fa-check-circle"></i>
        Reporte actualizado exitosamente.
    </div>
    <?php endif; ?>

    <!-- Card principal -->
    <div class="main-card">
        <div class="card-header-minimal">
            <h2 class="card-title-minimal">Detalles del Reporte de Transacción</h2>
            <?php if(isAdmin()): ?>
            <a href="report-form.php?id=<?php echo $report->id_reporte; ?>" class="btn-minimal btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Grid de información del reporte -->
        <div class="info-grid">
            <!-- Cliente -->
            <div class="info-card accent-green">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Cliente</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php echo htmlspecialchars($report->nombre_cliente); ?>
                    <?php if($report->nombre_plan || $report->nombre_empresa): ?>
                    <div style="font-size: 0.875rem; color: var(--medium-gray); margin-top: 0.5rem;">
                        <?php if($report->nombre_plan): ?>
                            Plan: <?php echo htmlspecialchars($report->nombre_plan); ?>
                        <?php endif; ?>
                        <?php if($report->nombre_empresa): ?>
                            <?php echo $report->nombre_plan ? ' • ' : ''; ?>
                            Empresa: <?php echo htmlspecialchars($report->nombre_empresa); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Fecha de Transacción -->
            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Fecha de Transacción</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php echo date('d/m/Y', strtotime($report->fecha_transaccion)); ?>
                </div>
            </div>
            
            <!-- Método de Pago -->
            <div class="info-card accent-green">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Método de Pago</h4>
                    </div>
                </div>
                <div class="info-content">
                    <span class="method-badge">
                        <i class="fas fa-money-bill-wave"></i>
                        <?php echo htmlspecialchars($report->metodo_pago); ?>
                    </span>
                </div>
            </div>
            
            <!-- Monto -->
            <div class="info-card">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Monto</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php if($report->monto): ?>
                        <div class="amount-display">
                            $<?php echo number_format($report->monto, 2); ?>
                        </div>
                    <?php else: ?>
                        <span class="info-content secondary">
                            <i class="fas fa-minus-circle" style="margin-right: 0.5rem;"></i>
                            No especificado
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Número de Referencia -->
            <div class="info-card accent-green" style="grid-column: span 2;">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Número de Referencia</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php if($report->numero_referencia): ?>
                        <div class="reference-display">
                            <?php echo htmlspecialchars($report->numero_referencia); ?>
                        </div>
                    <?php else: ?>
                        <span class="info-content secondary">
                            <i class="fas fa-minus-circle" style="margin-right: 0.5rem;"></i>
                            Sin número de referencia
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Período de Servicio -->
            <div class="info-card" style="grid-column: span 2;">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Período de Servicio</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php if($report->fecha_desde && $report->fecha_hasta): ?>
                        <div class="period-display">
                            <div class="period-dates">
                                <span><?php echo date('d/m/Y', strtotime($report->fecha_desde)); ?></span>
                                <i class="fas fa-arrow-right period-arrow"></i>
                                <span><?php echo date('d/m/Y', strtotime($report->fecha_hasta)); ?></span>
                            </div>
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--medium-gray);">
                                <?php 
                                $fecha_desde = new DateTime($report->fecha_desde);
                                $fecha_hasta = new DateTime($report->fecha_hasta);
                                $diferencia = $fecha_desde->diff($fecha_hasta);
                                echo $diferencia->days + 1 . ' días de servicio';
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="info-content secondary">
                            <i class="fas fa-minus-circle" style="margin-right: 0.5rem;"></i>
                            Período no especificado
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notas -->
            <div class="info-card" style="grid-column: span 2;">
                <div class="info-header">
                    <div class="info-icon">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Notas Adicionales</h4>
                    </div>
                </div>
                <div class="info-content">
                    <?php if($report->notas): ?>
                        <div class="notes-display">
                            <?php echo nl2br(htmlspecialchars($report->notas)); ?>
                        </div>
                    <?php else: ?>
                        <span class="info-content secondary">
                            <i class="fas fa-minus-circle" style="margin-right: 0.5rem;"></i>
                            Sin notas adicionales
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información de Creación -->
            <div class="info-card accent-green" style="grid-column: span 2;">
                <div class="info-header">
                    <div class="info-icon green">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h4 class="info-title">Información de Creación</h4>
                    </div>
                </div>
                <div class="info-content">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <div style="font-size: 0.875rem; color: var(--medium-gray); margin-bottom: 0.25rem;">Creado por:</div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($report->nombre_usuario_creador ?? 'Sistema'); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.875rem; color: var(--medium-gray); margin-bottom: 0.25rem;">Fecha de creación:</div>
                            <div style="font-weight: 500;"><?php echo date('d/m/Y H:i', strtotime($report->fecha_creacion)); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de navegación -->
    <div style="display: flex; justify-content: space-between; margin-top: 2rem;">
        <a href="reports.php" class="btn-minimal btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Reportes
        </a>
        
        <?php if(isAdmin()): ?>
        <div style="display: flex; gap: 0.5rem;">
            <a href="report-form.php?id=<?php echo $report->id_reporte; ?>" class="btn-minimal btn-primary">
                <i class="fas fa-edit"></i> Editar Reporte
            </a>
            <a href="report-delete.php?id=<?php echo $report->id_reporte; ?>" 
               class="btn-minimal btn-black" 
               onclick="return confirm('¿Está seguro de eliminar este reporte? Esta acción no se puede deshacer.')">
                <i class="fas fa-trash"></i> Eliminar Reporte
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Mostrar mensaje de éxito y ocultarlo después de unos segundos
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 300);
            }, 5000);
        }
    });
</script>

<?php
include 'includes/layout_footer.php';
?>
