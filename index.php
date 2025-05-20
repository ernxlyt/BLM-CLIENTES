<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'models/Client.php';
include_once 'utils/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$client = new Client($db);

$page_title = "Dashboard";

$total_clients = $client->countClients(getCurrentUserId(), isAdmin());

$upcoming_payments = $client->getUpcomingPayments(getCurrentUserId(), isAdmin(), 7);

$upcoming_birthdays = $client->getUpcomingBirthdays(getCurrentUserId(), isAdmin(), 30);

$recent_clients = $client->getRecentClients(getCurrentUserId(), isAdmin(), 5);

include 'includes/layout_header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="icon primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="value"><?php echo $total_clients; ?></div>
        <div class="label">Total Clientes</div>
    </div>
    
    <div class="stat-card">
        <div class="icon warning">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="value"><?php echo count($upcoming_payments); ?></div>
        <div class="label">Pagos Próximos (7 días)</div>
    </div>
    
    <div class="stat-card">
        <div class="icon info">
            <i class="fas fa-birthday-cake"></i>
        </div>
        <div class="value"><?php echo count($upcoming_birthdays); ?></div>
        <div class="label">Cumpleaños Próximos (30 días)</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Pagos Próximos</h2>
        <a href="clients.php" class="btn btn-primary">Ver Todos</a>
    </div>

    
    <table class="data-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha de Pago</th>
                <th>Días Restantes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($upcoming_payments) > 0): ?>
                <?php foreach($upcoming_payments as $payment): ?>
                <tr>
                    <td class="user-cell">
                        <div class="avatar" style="background-color: #d1fae5; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="color: #23D950;"></i>
                        </div>
                        <?php echo $payment['nombre_cliente']; ?>
                    </td>
                    <td><?php echo date('d/m', strtotime($payment['fecha_pago'])); ?></td>
                    <td>
                    <?php 
                        // Verificar si la fecha de pago es hoy o mañana
                        $today = new DateTime(date('Y-m-d')); // Fecha actual sin componente de hora
                        $tomorrow = new DateTime(date('Y-m-d'));
                        $tomorrow->modify('+1 day');
                        
                        // Crear fecha de pago para este mes
                        $payment_day = date('d', strtotime($payment['fecha_pago']));
                        $current_month = date('m');
                        $current_year = date('Y');
                        
                        // Crear fecha de pago para este mes
                        $payment_date = new DateTime("$current_year-$current_month-$payment_day");
                        
                        // Si la fecha ya pasó este mes, ajustar al próximo mes
                        if ($payment_date < $today) {
                            $payment_date->modify('first day of next month');
                            $payment_date->modify(($payment_day - 1) . ' days');
                            
                            // Verificar si el día existe en el próximo mes (por ejemplo, 31 de febrero)
                            $last_day_of_month = $payment_date->format('t');
                            if ($payment_day > $last_day_of_month) {
                                $payment_date = new DateTime($payment_date->format('Y-m-') . $last_day_of_month);
                            }
                        }
                        
                        // Verificar si es hoy, mañana o futuro
                        if ($payment_date->format('Y-m-d') === $today->format('Y-m-d')) {
                            // Es hoy
                            $status_class = 'pending';
                            $days_text = 'Hoy';
                        } elseif ($payment_date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                            // Es mañana
                            $status_class = 'pending';
                            $days_text = 'Mañana';
                        } else {
                            // Calcular la diferencia en días
                            $days_left = $today->diff($payment_date)->days;
                            
                            // Determinar la clase de estado
                            $status_class = 'completed';
                            if ($days_left <= 2) {
                                $status_class = 'pending';
                            } elseif ($days_left <= 5) {
                                $status_class = 'processing';
                            }
                            
                            // Usar singular o plural según corresponda
                            $days_text = $days_left == 1 ? '1 día' : $days_left . ' días';
                        }
                    ?>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo $days_text; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="client-view.php?id=<?php echo $payment['id_cliente']; ?>" class="btn btn-icon btn-secondary" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay pagos próximos en los siguientes 7 días.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Cumpleaños Próximos</h2>
        <a href="clients.php" class="btn btn-primary">Ver Todos</a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha de Cumpleaños</th>
                <th>Días Restantes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($upcoming_birthdays) > 0): ?>
                <?php foreach($upcoming_birthdays as $birthday): ?>
                <tr>
                    <td class="user-cell">
                        <div class="avatar" style="background-color: #dbeafe; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="color: #3b82f6;"></i>
                        </div>
                        <?php echo $birthday['nombre_cliente']; ?>
                    </td>
                    <td><?php echo date('d/m', strtotime($birthday['cumpleaños'])); ?></td>
                    <td>
                        <?php 
                            // Verificar si el cumpleaños es hoy o mañana
                            $today = new DateTime(date('Y-m-d')); // Fecha actual sin componente de hora
                            $tomorrow = new DateTime(date('Y-m-d'));
                            $tomorrow->modify('+1 day');
                            
                            $birth_date = new DateTime(date('Y-m-d', strtotime($birthday['cumpleaños']))); // Fecha de cumpleaños sin componente de hora
                            
                            // Ajustar al año actual
                            $birth_date->setDate(date('Y'), $birth_date->format('m'), $birth_date->format('d'));
                            
                            // Si la fecha ya pasó este año, ajustar al próximo año
                            if ($birth_date < $today) {
                                $birth_date->modify('+1 year');
                            }
                            
                            // Verificar si es hoy, mañana o futuro
                            if ($birth_date->format('Y-m-d') === $today->format('Y-m-d')) {
                                // Es hoy
                                $status_class = 'pending';
                                $days_text = 'Hoy';
                            } elseif ($birth_date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                                // Es mañana
                                $status_class = 'pending';
                                $days_text = 'Mañana';
                            } else {
                                // Calcular la diferencia en días
                                $days_left = $today->diff($birth_date)->days;
                                
                                // Determinar la clase de estado
                                $status_class = 'completed';
                                if ($days_left <= 7) {
                                    $status_class = 'pending';
                                } else if ($days_left <= 14) {
                                    $status_class = 'processing';
                                }
                                
                                // Usar singular o plural según corresponda
                                $days_text = $days_left == 1 ? '1 día' : $days_left . ' días';
                            }
                        ?>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo $days_text; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="client-view.php?id=<?php echo $birthday['id_cliente']; ?>" class="btn btn-icon btn-secondary" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay cumpleaños próximos en los siguientes 30 días.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Clientes Recientes</h2>
        <a href="clients.php" class="btn btn-primary">Ver Todos</a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Plan</th>
                <th>Fecha de Inicio</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($recent_clients) > 0): ?>
                <?php foreach($recent_clients as $client): ?>
                <tr>
                    <td class="user-cell">
                        <div class="avatar" style="background-color: #fee2e2; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="color: #ef4444;"></i>
                        </div>
                        <?php echo $client['nombre_cliente']; ?>
                    </td>
                    <td><?php echo $client['nombre_plan'] ?? 'No asignado'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($client['fecha_inicio'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="client-view.php?id=<?php echo $client['id_cliente']; ?>" class="btn btn-icon btn-secondary" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="client-form.php?id=<?php echo $client['id_cliente']; ?>" class="btn btn-icon btn-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay clientes recientes.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
include 'includes/layout_footer.php';
?>
