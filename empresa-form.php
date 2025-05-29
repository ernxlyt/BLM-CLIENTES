<?php
include_once 'config/database.php';
include_once 'models/Empresa.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$empresa = new Empresa($db);

$page_title = "Nueva Empresa";
$action = "create";

if(isset($_GET['id']) && !empty($_GET['id'])) {
    $empresa->id_empresa = $_GET['id'];
    
    if($empresa->readOne()) {
        $page_title = "Editar Empresa";
        $action = "update";
    } else {
        header("Location: empresas.php");
        exit();
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $empresa->nombre_empresa = trim($_POST['nombre_empresa']);
        $empresa->rubro = trim($_POST['rubro']);
        
        if($action === "create") {
            if($empresa->create()) {
                header("Location: empresas.php?success=1");
                exit();
            } else {
                $error_message = "Error al crear la empresa.";
            }
        } else {
            if($empresa->update()) {
                header("Location: empresa-view.php?id=" . $empresa->id_empresa . "&success=1");
                exit();
            } else {
                $error_message = "Error al actualizar la empresa.";
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

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
.form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Mensaje de error */
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

/* Card principal del formulario */
.form-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    overflow: hidden;
}

.form-header {
    background: var(--primary-green);
    color: var(--white);
    padding: 1.5rem 2rem;
    position: relative;
}

.form-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
}

.form-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.form-icon {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.form-content {
    padding: 2rem;
}

/* Secciones del formulario */
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

/* Grid de campos */
.fields-grid {
    display: grid;
    grid-template-columns: 1fr;
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

.field-card:focus-within {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

/* Labels y campos */
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

.field-input:valid {
    border-color: var(--primary-green);
}

.field-help {
    font-size: 0.75rem;
    color: var(--medium-gray);
    margin-top: 0.25rem;
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
    box-shadow: 0 4px 12px rgba(35, 217, 80, 0.3);
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

/* Área de acciones */
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

/* Animaciones */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.5s ease;
}

.fade-in.active {
    opacity: 1;
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 768px) {
    .form-container {
        padding: 0 0.5rem;
    }
    
    .form-content {
        padding: 1rem;
    }
    
    .actions-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .actions-group {
        flex-direction: column;
    }
}

/* Estados de validación */
.field-input.error {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.field-input.success {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

/* Loading state */
.btn-loading {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-loading:hover {
    transform: none;
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
    <div class="form-card fade-in">
        <div class="form-header">
            <h1 class="form-title">
                <div class="form-icon">
                    <i class="fas fa-building"></i>
                </div>
                <?php echo $page_title; ?>
            </h1>
        </div>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $empresa->id_empresa : "")); ?>" id="empresaForm">
            <div class="form-content">
                <!-- Información de la Empresa -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle section-icon"></i>
                        Información de la Empresa
                    </h3>
                    
                    <div class="fields-grid">
                        <!-- Nombre de la Empresa -->
                        <div class="field-card">
                            <label for="nombre_empresa" class="field-label">
                                Nombre de la Empresa <span class="field-required">*</span>
                            </label>
                            <input type="text" id="nombre_empresa" name="nombre_empresa" required 
                                   value="<?php echo $action === "update" ? htmlspecialchars($empresa->nombre_empresa ?? '') : ''; ?>"
                                   class="field-input" placeholder="Ej: Tecnología Avanzada S.A.">
                            <div class="field-help">Ingrese el nombre completo de la empresa</div>
                        </div>
                        
                        <!-- Rubro -->
                        <div class="field-card">
                            <label for="rubro" class="field-label">
                                Rubro <span class="field-required">*</span>
                            </label>
                            <input type="text" id="rubro" name="rubro" required 
                                   value="<?php echo $action === "update" ? htmlspecialchars($empresa->rubro ?? '') : ''; ?>"
                                   class="field-input" placeholder="Ej: Tecnología, Salud, Educación, Comercio...">
                            <div class="field-help">Especifique el sector o industria de la empresa</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones del formulario -->
            <div class="form-actions">
                <div class="actions-content">
                    <div class="actions-row">
                        <a href="empresas.php" class="btn-minimal btn-secondary">
                            <i class="fas fa-arrow-left"></i>Cancelar
                        </a>
                        
                        <div class="actions-group">
                            <button type="button" onclick="resetForm()" class="btn-minimal btn-secondary">
                                <i class="fas fa-undo"></i>Limpiar
                            </button>
                            
                            <button type="submit" class="btn-minimal btn-primary" id="saveButton">
                                <i class="fas fa-save"></i>
                                <?php echo $action === "create" ? "Crear Empresa" : "Actualizar Empresa"; ?>
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

    // Función para limpiar el formulario
    function resetForm() {
        if (confirm('¿Está seguro de que desea limpiar todos los campos del formulario?')) {
            document.getElementById('empresaForm').reset();
            formSubmitted = false;
            const saveButton = document.getElementById('saveButton');
            saveButton.innerHTML = '<i class="fas fa-save"></i><?php echo $action === "create" ? "Crear Empresa" : "Actualizar Empresa"; ?>';
            saveButton.disabled = false;
            saveButton.classList.remove('btn-loading');
        }
    }

    // Manejo del envío del formulario
    document.getElementById('empresaForm').addEventListener('submit', function(e) {
        if (formSubmitted) {
            e.preventDefault();
            return false;
        }
        
        const saveButton = document.getElementById('saveButton');
        
        // Cambiar el botón para mostrar que se está procesando
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Guardando...';
        saveButton.disabled = true;
        saveButton.classList.add('btn-loading');
        
        formSubmitted = true;
    });

    // Prevenir cierre accidental si hay datos en el formulario
    window.addEventListener('beforeunload', function(e) {
        const form = document.getElementById('empresaForm');
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
        const firstField = document.getElementById('nombre_empresa');
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

        // Animación de entrada
        setTimeout(() => {
            document.querySelector('.fade-in').classList.add('active');
        }, 100);
    });
</script>

<?php
include 'includes/layout_footer.php';
?>
