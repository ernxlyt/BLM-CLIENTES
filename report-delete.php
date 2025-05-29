<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Report.php';
include_once 'utils/session.php';

// Require login and admin
requireLogin();
requireAdmin();

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

// Check if report exists
if(!$report->readOne(getCurrentUserId(), isAdmin())) {
    header("Location: reports.php");
    exit();
}

// Store report info for confirmation
$report_info = [
    'id' => $report->id_reporte,
    'cliente' => $report->nombre_cliente,
    'fecha_transaccion' => $report->fecha_transaccion,
    'metodo_pago' => $report->metodo_pago,
    'monto' => $report->monto
];

// Process deletion
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if($report->delete()) {
        header("Location: reports.php?deleted=1");
        exit();
    } else {
        $error_message = "Error al eliminar el reporte.";
    }
}

// Set page title
$page_title = "Eliminar Reporte";

// Include header
include 'includes/layout_header.php';
?>

<style>
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
    --danger-red: #ef4444;
    --light-red: #fef2f2;
}

.delete-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 1rem;
}

.error-message {
    background-color: var(--light-red);
    border: 1px solid var(--danger-red);
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.delete-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 2px solid var(--danger-red);
    overflow: hidden;
}

.delete-header {
    background: var(--danger-red);
    color: var(--white);
    padding: 1.5rem 2rem;
    text-align: center;
}

.delete-icon {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
}

.delete-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.delete-content {
    padding: 2rem;
}

.warning-message {
    background: var(--light-red);
    border: 1px solid var(--danger-red);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: center;
}

.warning-text {
    color: #991b1b;
    font-weight: 500;
    margin-bottom: 1rem;
}

.warning-note {
    color: var(--medium-gray);
    font-size: 0.875rem;
}

.report-summary {
    background: var(--light-gray);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.summary-title {
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-gray);
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    color: var(--medium-gray);
    font-size: 0.875rem;
}

.summary-value {
    color: var(--dark-gray);
    font-weight: 500;
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

.btn-danger {
    background: var(--danger-red);
    color: var(--white);
    border-color: var(--danger-red);
}

.btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
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

.actions-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

@media (max-width: 768px) {
    .delete-container {
        padding: 0 0.5rem;
    }
    
    .delete-content {
        padding: 1rem;
    }
    
    .actions-row {
        flex-direction: column;
    }
}
</style>

<div class="delete-container">
    <!-- Mensaje de error -->
    <?php if(isset($error_message)): ?>
    <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- Card de confirmación de eliminación -->
    <div class="delete-card">
        <div class="delete-header">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="delete-title">Confirmar Eliminación</h1>
        </div>
        
        <div class="delete-content">
            <!-- Mensaje de advertencia -->
            <div class="warning-message">
                <div class="warning-text">
                    ¿Está seguro de que desea eliminar este reporte de transacción?
                </div>
                <div class="warning-note">
                    Esta acción no se puede deshacer y se perderán todos los datos asociados.
                </div>
            </div>

            <!-- Resumen del reporte -->
            <div class="report-summary">
                <h3 class="summary-title">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Detalles del Reporte a Eliminar
                </h3>
                
                <div class="summary-item">
                    <span class="summary-label">ID del Reporte:</span>
                    <span class="summary-value">#<?php echo $report_info['id']; ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">Cliente:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($report_info['cliente']); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">Fecha de Transacción:</span>
                    <span class="summary-value"><?php echo date('d/m/Y', strtotime($report_info['fecha_transaccion'])); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">Método de Pago:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($report_info['metodo_pago']); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">Monto:</span>
                    <span class="summary-value">
                        <?php if($report_info['monto']): ?>
                            $<?php echo number_format($report_info['monto'], 2); ?>
                        <?php else: ?>
                            No especificado
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Formulario de confirmación -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $report_info['id']); ?>">
                <div class="actions-row">
                    <a href="report-view.php?id=<?php echo $report_info['id']; ?>" class="btn-minimal btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    
                    <button type="submit" name="confirm_delete" class="btn-minimal btn-danger">
                        <i class="fas fa-trash"></i>
                        Eliminar Reporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include 'includes/layout_footer.php';
?>
