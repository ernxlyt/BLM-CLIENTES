<?php
include_once 'config/database.php';
include_once 'models/Empresa.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$empresa = new Empresa($db);

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: empresas.php");
    exit();
}

$empresa->id_empresa = $_GET['id'];

if(!$empresa->readOne()) {
    header("Location: empresas.php");
    exit();
}

$clients = $empresa->getClients();

$page_title = "Detalles de Empresa";

include 'includes/layout_header.php';
?>

<div class="animate__animated animate__fadeIn">
    <!-- Cabecera con información principal -->
    <div class="card mb-6 overflow-hidden border-0 shadow-lg">
        <div class="card-header bg-gradient-to-r from-emerald-500 to-green-600 text-white flex justify-between items-center">
            <div class="flex items-center">
                <div class="bg-white/20 p-2 rounded-full mr-3">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div>
                    <h2 class="card-title text-2xl font-bold m-0"><?php echo htmlspecialchars($empresa->nombre_empresa); ?></h2>
                    <p class="text-white/80 text-sm m-0"><?php echo htmlspecialchars($empresa->rubro); ?></p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="empresa-form.php?id=<?php echo $empresa->id_empresa; ?>" class="btn bg-amber-500 hover:bg-amber-600 text-white border-0 flex items-center gap-2 transition-all">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="empresas.php" class="btn bg-white/20 hover:bg-white/30 text-white border-0 flex items-center gap-2 transition-all">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel de información -->
        <div class="card border-0 shadow-md hover:shadow-lg transition-all">
            <div class="card-header bg-white border-b border-gray-100">
                <h3 class="text-lg font-semibold flex items-center gap-2 m-0">
                    <i class="fas fa-info-circle text-emerald-500"></i> Información General
                </h3>
            </div>
            <div class="p-5">
                <div class="space-y-4">
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center mr-3">
                            <i class="fas fa-building text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 m-0">Nombre de la empresa</p>
                            <p class="font-medium text-gray-800 m-0"><?php echo htmlspecialchars($empresa->nombre_empresa); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-briefcase text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 m-0">Rubro</p>
                            <p class="font-medium text-gray-800 m-0"><?php echo htmlspecialchars($empresa->rubro); ?></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Panel de clientes asociados -->
        <div class="card border-0 shadow-md hover:shadow-lg transition-all lg:col-span-2">
            <div class="card-header bg-white border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold flex items-center gap-2 m-0">
                    <i class="fas fa-users text-blue-500"></i> Clientes Asociados
                </h3>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <?php echo $clients->rowCount(); ?> clientes
                </span>
            </div>
            
            <div class="p-0">
                <?php if($clients->rowCount() > 0): ?>
                    <div class="relative overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Cliente</th>
                                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $clients->fetch(PDO::FETCH_ASSOC)): 
                                    extract($row);
                                ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-user text-emerald-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 m-0"><?php echo htmlspecialchars($nombre_cliente); ?></p>
                                                <?php if(isset($email_cliente)): ?>
                                                <p class="text-sm text-gray-500 m-0"><?php echo htmlspecialchars($email_cliente); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex gap-2 justify-end">
                                            <a href="client-view.php?id=<?php echo $id_cliente; ?>" class="btn btn-sm bg-blue-500 hover:bg-blue-600 text-white border-0 flex items-center gap-1 transition-all">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                            <a href="client-form.php?id=<?php echo $id_cliente; ?>" class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white border-0 flex items-center gap-1 transition-all">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                        <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <i class="fas fa-users text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay clientes asociados</h3>
                        <p class="text-gray-500 max-w-md mb-6">Esta empresa aún no tiene clientes asociados. Puedes agregar nuevos clientes desde el formulario de clientes.</p>
                        <a href="client-form.php" class="btn bg-emerald-500 hover:bg-emerald-600 text-white border-0 flex items-center gap-2 transition-all">
                            <i class="fas fa-plus"></i> Agregar Cliente
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/layout_footer.php';
?>
