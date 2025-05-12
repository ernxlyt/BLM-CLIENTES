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

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?php echo $page_title; ?></h2>
    </div>
    
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($action === "update" ? "?id=" . $plan->id_plan : "")); ?>">
        <div class="p-4">
            <div class="form-group">
                <label for="nombre_plan" class="form-label">Nombre del Plan</label>
                <input type="text" id="nombre_plan" name="nombre_plan" required 
                       value="<?php echo $action === "update" ? $plan->nombre_plan : ''; ?>"
                       class="form-control">
            </div>
            
            <div class="form-group">
                <label for="descripcion_plan" class="form-label">Descripción</label>
                <textarea id="descripcion_plan" name="descripcion_plan" rows="4" 
                          class="form-control"><?php echo $action === "update" ? $plan->descripcion_plan : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="precio" class="form-label">Precio</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-700">$</span>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" required 
                           value="<?php echo $action === "update" ? $plan->precio : ''; ?>"
                           class="form-control pl-8">
                </div>
            </div>
            
            <div class="flex justify-between mt-6">
                <a href="planes.php" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar
                </button>
            </div>
        </div>
    </form>
</div>

<?php
include 'includes/layout_footer.php';
?>
