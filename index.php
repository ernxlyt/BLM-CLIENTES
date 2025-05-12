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
                        // Cálculo de días restantes basado en la fecha exacta de pago registrada
                        $today = new DateTime(); // Fecha actual
                        $payment_date = new DateTime($payment['fecha_pago']); // Fecha de pago registrada
                        // Si la fecha de pago ya pasó este mes, ajusta al siguiente mes
                        if ($payment_date < $today) {
                            $payment_date->modify('+1 month');
                        }

                        // Calcula la diferencia en días
                        $days_left = $today->diff($payment_date)->days;

                        // Determina la clase de estado basada en los días restantes
                        $status_class = 'completed';
                        if ($days_left <= 2) {
                            $status_class = 'pending';
                        } elseif ($days_left <= 5) {
                            $status_class = 'processing';
                        }
                    ?>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo $days_left; ?> días
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

                            $birth_date = new DateTime($birthday['cumpleaños']);
                            $today = new DateTime();
                            $birth_date->setDate($today->format('Y'), $birth_date->format('m'), $birth_date->format('d'));
                            
                            if($birth_date < $today) {
                                $birth_date->modify('+1 year');
                            }
                            
                            $days_left = $today->diff($birth_date)->days;
                            
                            $status_class = 'completed';
                            if($days_left <= 7) {
                                $status_class = 'pending';
                            } else if($days_left <= 14) {
                                $status_class = 'processing';
                            }
                        ?>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo $days_left; ?> días
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