<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/Report.php';
include_once 'models/Client.php';
include_once 'utils/session.php';

// Require login
requireLogin();
requireAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$report = new Report($db);
$client = new Client($db);

// Set page title and action
$page_title = "Nuevo Reporte de Transacción";
$action = "create";

// Check if editing
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $report->id_reporte = $_GET['id'];
    
    if($report->readOne(getCurrentUserId(), isAdmin())) {
        $page_title = "Editar Reporte de Transacción";
        $action = "update";
    } else {
        header("Location: reports.php");
        exit();
    }
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Set report properties
        $report->id_cliente = $_POST['id_cliente'];
        $report->fecha_transaccion = $_POST['fecha_transaccion'];
        $report->metodo_pago = $_POST['metodo_pago'];
        $report->numero_referencia = !empty($_POST['numero_referencia']) ? $_POST['numero_referencia'] : null;
        $report->fecha_desde = !empty($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
        $report->fecha_hasta = !empty($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;
        $report->monto = !empty($_POST['monto']) ? $_POST['monto'] : null;
        $report->notas = !empty($_POST['notas']) ? $_POST['notas'] : null;
        
        if($action === "create") {
            $report->id_usuario_creador = getCurrentUserId();
            
            if($report->create()) {
                header("Location: reports.php?success=1");
                exit();
            } else {
                $error_message = "Error al crear el reporte.";
            }
        } else {
            if($report->update()) {
                header("Location: report-view.php?id=" . $report->id_reporte . "&success=1");
                exit();
            } else {
                $error_message = "Error al actualizar el reporte.";
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get available clients
$available_clients = $report->getAvailableClients(getCurrentUserId(), isAdmin());

// Include header
include 'includes/layout_header.php';
?>

<style>
/* Usar los mismos estilos minimalistas del client-form */
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

.form-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
}

.error-message {
    background-color: #fef2f2;
    border: 1px solid #ef4444;
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    overflow: hidden;
}

.form-header {
    background: var(--black);
    color: var(--white);
    padding: 1.5rem 2rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.form-content {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-icon {
    width: 24px;
    height: 24px;
    color: var(--primary-green);
}

.fields-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.field-card {
    background: var(--light-gray);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.2s ease;
}

.field-card:hover {
    border-color: var(--primary-green);
}

.field-card.full-width {
    grid-column: 1 / -1;
}

.field-label {
    display: block;
    color: var(--dark-gray);
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.field-required {
    color: #ef4444;
}

.field-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-gray);
    border-radius: 6px;
    background: var(--white);
    color: var(--dark-gray);
    transition: border-color 0.2s ease;
    font-size: 0.875rem;
}

.field-input:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
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

.form-actions {
    background: var(--light-gray);
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--border-gray);
    margin-top: 2rem;
}

.actions-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.actions-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.actions-group {
    display: flex;
    gap: 0.75rem;
}

.form-note {
    text-align: center;
    font-size: 0.875rem;
    color: var(--medium-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .form-container {
        padding: 0 0.5rem;
    }
    
    .form-content {
        padding: 1rem;
    }
    
    .fields-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .actions-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .actions-group {
        flex-direction: column;
    }
}
</style>

<div class="form-container">
    <!-- Mensaje de error -->
    <?php if(isset($error_message)): ?>
    <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- Card principal del formulario -->
    <div class="form-card">
        <div class="form-header">
            <h1 class="form-title"><?php echo $page_title; ?></h1>
        </div>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $report->id_reporte : "")); ?>" id="reportForm">
            <div class="form-content">
                <!-- Información de la Transacción -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-invoice-dollar section-icon"></i>
                        Información de la Transacción
                    </h3>
                    
                    <div class="fields-grid">
                        <!-- Cliente -->
                        <div class="field-card">
                            <label for="id_cliente" class="field-label">
                                Cliente <span class="field-required">*</span>
                            </label>
                            <select id="id_cliente" name="id_cliente" required class="field-input">
                                <option value="">Seleccionar Cliente</option>
                                <?php foreach($available_clients as $client_option): ?>
                                <option value="<?php echo $client_option['id_cliente']; ?>" 
                                        <?php echo ($action === "update" && $report->id_cliente == $client_option['id_cliente']) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($client_option['nombre_cliente']); ?>
                                    <?php if($client_option['nombre_plan']): ?>
                                        - <?php echo htmlspecialchars($client_option['nombre_plan']); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Fecha de Transacción -->
                        <div class="field-card">
                            <label for="fecha_transaccion" class="field-label">
                                Fecha de Transacción <span class="field-required">*</span>
                            </label>
                            <input type="date" id="fecha_transaccion" name="fecha_transaccion" required 
                                   value="<?php echo $action === "update" ? ($report->fecha_transaccion ?? '') : ''; ?>"
                                   class="field-input">
                        </div>
                        
                        <!-- Método de Pago -->
                        <div class="field-card">
                            <label for="metodo_pago" class="field-label">
                                Método de Pago <span class="field-required">*</span>
                            </label>
                            <input type="text" id="metodo_pago" name="metodo_pago" required 
                                   value="<?php echo $action === "update" ? htmlspecialchars($report->metodo_pago ?? '') : ''; ?>"
                                   class="field-input" placeholder="Ej: Transferencia, Efectivo, Tarjeta, PayPal...">
                        </div>
                        
                        <!-- Monto -->
                        <div class="field-card">
                            <label for="monto" class="field-label">
                                Monto
                            </label>
                            <input type="number" id="monto" name="monto" step="0.01" min="0"
                                   value="<?php echo $action === "update" ? ($report->monto ?? '') : ''; ?>"
                                   class="field-input" placeholder="0.00">
                        </div>
                        
                        <!-- Número de Referencia -->
                        <div class="field-card full-width">
                            <label for="numero_referencia" class="field-label">
                                Número de Referencia
                            </label>
                            <input type="text" id="numero_referencia" name="numero_referencia" 
                                   value="<?php echo $action === "update" ? htmlspecialchars($report->numero_referencia ?? '') : ''; ?>"
                                   class="field-input" placeholder="Número de referencia o comprobante">
                        </div>
                    </div>
                </div>

                <!-- Período de Servicio -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-alt section-icon"></i>
                        Período de Servicio (Opcional)
                    </h3>
                    
                    <div class="fields-grid">
                        <!-- Fecha Desde -->
                        <div class="field-card">
                            <label for="fecha_desde" class="field-label">
                                Fecha Desde
                            </label>
                            <input type="date" id="fecha_desde" name="fecha_desde" 
                                   value="<?php echo $action === "update" ? ($report->fecha_desde ?? '') : ''; ?>"
                                   class="field-input">
                        </div>
                        
                        <!-- Fecha Hasta -->
                        <div class="field-card">
                            <label for="fecha_hasta" class="field-label">
                                Fecha Hasta
                            </label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" 
                                   value="<?php echo $action === "update" ? ($report->fecha_hasta ?? '') : ''; ?>"
                                   class="field-input">
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-sticky-note section-icon"></i>
                        Notas Adicionales
                    </h3>
                    
                    <div class="field-card">
                        <label for="notas" class="field-label">
                            Notas
                        </label>
                        <textarea id="notas" name="notas" rows="4" 
                                  class="field-input" style="resize: vertical;" 
                                  placeholder="Información adicional sobre la transacción..."><?php echo $action === "update" ? htmlspecialchars($report->notas ?? '') : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Acciones del formulario -->
            <div class="form-actions">
                <div class="actions-content">
                    <div class="actions-row">
                        <a href="reports.php" class="btn-minimal btn-secondary">
                            <i class="fas fa-arrow-left"></i>Cancelar
                        </a>
                        
                        <div class="actions-group">
                            <button type="button" onclick="resetForm()" class="btn-minimal btn-secondary">
                                <i class="fas fa-undo"></i>Limpiar Formulario
                            </button>
                            
                            <button type="submit" class="btn-minimal btn-primary" id="saveButton">
                                <i class="fas fa-save"></i>
                                <?php echo $action === "create" ? "Crear Reporte" : "Actualizar Reporte"; ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-note">
                        <i class="fas fa-info-circle"></i>
                        Los campos marcados con <span class="field-required">*</span> son obligatorios.
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let formSubmitted = false;

    function resetForm() {
        if (confirm('¿Está seguro de que desea limpiar todos los campos del formulario?')) {
            document.getElementById('reportForm').reset();
            formSubmitted = false;
            const saveButton = document.getElementById('saveButton');
            saveButton.innerHTML = '<i class="fas fa-save"></i><?php echo $action === "create" ? "Crear Reporte" : "Actualizar Reporte"; ?>';
            saveButton.disabled = false;
        }
    }

    // Validación de fechas
    document.getElementById('fecha_desde').addEventListener('change', function() {
        const fechaDesde = this.value;
        const fechaHasta = document.getElementById('fecha_hasta').value;
        
        if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
            alert('La fecha "Desde" no puede ser posterior a la fecha "Hasta"');
            this.value = '';
        }
    });

    document.getElementById('fecha_hasta').addEventListener('change', function() {
        const fechaDesde = document.getElementById('fecha_desde').value;
        const fechaHasta = this.value;
        
        if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
            alert('La fecha "Hasta" no puede ser anterior a la fecha "Desde"');
            this.value = '';
        }
    });

    // Manejo del envío del formulario
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        if (formSubmitted) {
            e.preventDefault();
            return false;
        }
        
        const saveButton = document.getElementById('saveButton');
        
        // Cambiar el botón para mostrar que se está procesando
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Guardando...';
        saveButton.disabled = true;
        
        formSubmitted = true;
    });

    // Prevenir cierre accidental si hay datos en el formulario
    window.addEventListener('beforeunload', function(e) {
        const form = document.getElementById('reportForm');
        const formData = new FormData(form);
        let hasData = false;
        
        for (let [key, value] of formData.entries()) {
            if (value && value.toString().trim() !== '') {
                hasData = true;
                break;
            }
        }
        
        if (hasData && !formSubmitted) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Inicialización cuando se carga la página
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-focus en el primer campo
        const firstField = document.getElementById('id_cliente');
        if (firstField && !firstField.value) {
            firstField.focus();
        }
        
        // Agregar efectos de hover a los campos
        const fieldCards = document.querySelectorAll('.field-card');
        fieldCards.forEach(card => {
            const input = card.querySelector('.field-input');
            if (input) {
                input.addEventListener('focus', function() {
                    card.style.borderColor = 'var(--primary-green)';
                });
                
                input.addEventListener('blur', function() {
                    card.style.borderColor = 'var(--border-gray)';
                });
            }
        });
    });
</script>

<?php
include 'includes/layout_footer.php';
?>
