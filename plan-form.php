<?php
include_once 'config/database.php';
include_once 'models/Plan.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);

$page_title = "Nuevo Plan";
$action = "create";

if(isset($_GET['id']) && !empty($_GET['id'])) {
    $plan->id_plan = $_GET['id'];
    
    if($plan->readOne()) {
        $page_title = "Editar Plan";
        $action = "update";
    } else {
        header("Location: planes.php");
        exit();
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan->nombre_plan = $_POST['nombre_plan'];
    $plan->descripcion_plan = $_POST['descripcion_plan'];
    $plan->precio = $_POST['precio'];
    
    if($action === "create") {
        if($plan->create()) {
            header("Location: planes.php");
            exit();
        }
    } else {
        if($plan->update()) {
            header("Location: planes.php");
            exit();
        }
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
    --red: #ef4444;
}

/* Contenedor principal */
.plan-form-container {
    max-width: 800px;
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
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
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

/* Formulario */
.form-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border-gray);
    overflow: hidden;
    margin-bottom: 2rem;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-header {
    background: var(--light-green);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border-gray);
}

.form-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-green);
    margin: 0;
}

.form-body {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark-gray);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-gray);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--dark-gray);
    font-size: 0.875rem;
}

.required-indicator {
    color: var(--red);
    margin-left: 0.25rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    background: var(--white);
    color: var(--dark-gray);
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(35, 217, 80, 0.1);
}

.form-text {
    font-size: 0.75rem;
    color: var(--medium-gray);
    margin-top: 0.5rem;
}

/* Campo de precio */
.price-input-container {
    position: relative;
}

.price-symbol {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--medium-gray);
    font-weight: 500;
}

.price-input {
    padding-left: 2rem;
}

/* Botones */
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    gap: 1rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
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
    box-shadow: 0 4px 6px rgba(35, 217, 80, 0.1);
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

.btn-danger {
    background: var(--white);
    color: var(--red);
    border-color: var(--red);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .plan-form-container {
        padding: 0 0.5rem;
    }
    
    .form-body {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<div class="plan-form-container">
    <!-- Header de la página -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <div class="title-icon">
                    <i class="fas fa-tag"></i>
                </div>
                <?php echo $page_title; ?>
            </h1>
        </div>
    </div>

    <!-- Formulario -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $plan->id_plan : "")); ?>" id="planForm">
        <div class="form-card">
            <div class="form-header">
                <h2 class="form-title">Información del Plan</h2>
            </div>
            
            <div class="form-body">
                <div class="form-section">
                    <div class="form-group">
                        <label for="nombre_plan" class="form-label">
                            Nombre del Plan
                            <span class="required-indicator">*</span>
                        </label>
                        <input type="text" id="nombre_plan" name="nombre_plan" required 
                               value="<?php echo $action === "update" ? htmlspecialchars($plan->nombre_plan) : ''; ?>"
                               class="form-control" placeholder="Ej: Plan Básico, Plan Premium, etc."
                               autofocus>
                        <div class="form-text">El nombre debe ser descriptivo y único.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion_plan" class="form-label">Descripción</label>
                        <textarea id="descripcion_plan" name="descripcion_plan" rows="4" 
                                  class="form-control" placeholder="Describe las características y beneficios del plan..."><?php echo $action === "update" ? htmlspecialchars($plan->descripcion_plan) : ''; ?></textarea>
                        <div class="form-text">Proporciona detalles sobre lo que incluye este plan.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio" class="form-label">
                            Precio
                            <span class="required-indicator">*</span>
                        </label>
                        <div class="price-input-container">
                            <span class="price-symbol">$</span>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" required 
                                   value="<?php echo $action === "update" ? htmlspecialchars($plan->precio) : ''; ?>"
                                   class="form-control price-input" placeholder="0.00">
                        </div>
                        <div class="form-text">Ingresa el precio sin símbolos de moneda.</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="planes.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        <?php echo $action === "update" ? 'Actualizar Plan' : 'Crear Plan'; ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prevenir envío doble del formulario
    const form = document.getElementById('planForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    });
    
    // Advertir antes de salir si hay cambios sin guardar
    let formChanged = false;
    
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            formChanged = true;
        });
    });
    
    
    // Validación básica
    form.addEventListener('submit', function(e) {
        const nombrePlan = document.getElementById('nombre_plan').value.trim();
        const precio = document.getElementById('precio').value;
        
        if (nombrePlan === '') {
            e.preventDefault();
            alert('Por favor ingrese un nombre para el plan.');
            document.getElementById('nombre_plan').focus();
        }
        
        if (precio === '' || parseFloat(precio) < 0) {
            e.preventDefault();
            alert('Por favor ingrese un precio válido.');
            document.getElementById('precio').focus();
        }
    });
});
</script>

<?php
include 'includes/layout_footer.php';
?>