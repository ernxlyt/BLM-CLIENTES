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

<style>
/* Dashboard específico */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.dashboard-subtitle {
    color: #6b7280;
    font-size: 1rem;
}

/* Stats Grid Mejorado */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #f3f4f6;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #23D950, #1ea83e);
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    color: #64748b;
    font-size: 1.5rem;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Cards Mejoradas */
.dashboard-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #f3f4f6;
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header-modern {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbfc;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title-modern {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-title-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #23D950;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

/* Tabla Moderna */
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead th {
    background: #fafbfc;
    padding: 1rem 2rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modern-table tbody td {
    padding: 1.25rem 2rem;
    border-bottom: 1px solid #f3f4f6;
    color: #374151;
    vertical-align: middle;
}

.modern-table tbody tr:hover {
    background: #fafbfc;
}

.modern-table tbody tr:last-child td {
    border-bottom: none;
}

/* Avatar Moderno */
.modern-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    background: #f1f5f9;
    color: #64748b;
    font-size: 1rem;
}

.user-cell-modern {
    display: flex;
    align-items: center;
    font-weight: 500;
}

/* Status Badges Modernos */
.status-modern {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-modern.pending {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fbbf24;
}

.status-modern.processing {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.status-modern.completed {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

/* Botones Modernos */
.btn-modern {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    font-size: 0.875rem;
}

.btn-modern-primary {
    background: #23D950;
    color: white;
    border-color: #23D950;
}

.btn-modern-primary:hover {
    background: #1ea83e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(35, 217, 80, 0.3);
}

.btn-modern-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    border-radius: 8px;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-modern-icon:hover {
    background: #23D950;
    color: white;
    border-color: #23D950;
    transform: translateY(-1px);
}

.action-buttons-modern {
    display: flex;
    gap: 0.5rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6b7280;
}

.empty-state-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stat-card {
        padding: 1.5rem;
    }
    
    .card-header-modern {
        padding: 1rem 1.5rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .modern-table thead th,
    .modern-table tbody td {
        padding: 0.75rem 1rem;
    }
    
    .dashboard-container {
        padding: 0 0.5rem;
    }
}
</style>

<div class="dashboard-container">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard</h1>
        <p class="dashboard-subtitle">Resumen general de tu gestión de clientes</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_clients; ?></div>
            <div class="stat-label">Total Clientes</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($upcoming_payments); ?></div>
            <div class="stat-label">Pagos Próximos (7 días)</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-birthday-cake"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($upcoming_birthdays); ?></div>
            <div class="stat-label">Cumpleaños Próximos (30 días)</div>
        </div>
    </div>

    <!-- Pagos Próximos -->
    <div class="dashboard-card">
        <div class="card-header-modern">
            <h2 class="card-title-modern">
                <div class="card-title-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                Pagos Próximos
            </h2>
            <a href="clients.php" class="btn-modern btn-modern-primary">Ver Todos</a>
        </div>

        <?php if(count($upcoming_payments) > 0): ?>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Día de Pago</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($upcoming_payments as $payment): ?>
                <tr>
                    <td>
                        <div class="user-cell-modern">
                            <div class="modern-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <?php echo htmlspecialchars($payment['nombre_cliente']); ?>
                        </div>
                    </td>
                    <td>
                        <strong><?php echo date('d', strtotime($payment['fecha_pago'])); ?></strong>
                        <span style="color: #6b7280; font-size: 0.875rem; margin-left: 0.5rem;">
                            de cada mes
                        </span>
                    </td>
                    <td>
                        <?php 
                            $today = new DateTime(date('Y-m-d'));
                            $tomorrow = new DateTime(date('Y-m-d'));
                            $tomorrow->modify('+1 day');
                            
                            $payment_day = date('d', strtotime($payment['fecha_pago']));
                            $current_month = date('m');
                            $current_year = date('Y');
                            
                            $payment_date = new DateTime("$current_year-$current_month-$payment_day");
                            
                            if ($payment_date < $today) {
                                $payment_date->modify('first day of next month');
                                $payment_date->modify(($payment_day - 1) . ' days');
                                
                                $last_day_of_month = $payment_date->format('t');
                                if ($payment_day > $last_day_of_month) {
                                    $payment_date = new DateTime($payment_date->format('Y-m-') . $last_day_of_month);
                                }
                            }
                            
                            if ($payment_date->format('Y-m-d') === $today->format('Y-m-d')) {
                                $status_class = 'pending';
                                $days_text = 'Hoy';
                            } elseif ($payment_date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                                $status_class = 'pending';
                                $days_text = 'Mañana';
                            } else {
                                $days_left = $today->diff($payment_date)->days;
                                
                                $status_class = 'completed';
                                if ($days_left <= 2) {
                                    $status_class = 'pending';
                                } elseif ($days_left <= 5) {
                                    $status_class = 'processing';
                                }
                                
                                $days_text = $days_left == 1 ? '1 día' : $days_left . ' días';
                            }
                        ?>
                        <span class="status-modern <?php echo $status_class; ?>">
                            <?php echo $days_text; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons-modern">
                            <a href="client-view.php?id=<?php echo $payment['id_cliente']; ?>" 
                               class="btn-modern btn-modern-icon" title="Ver Cliente">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <p>No hay pagos próximos en los siguientes 7 días</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cumpleaños Próximos -->
    <div class="dashboard-card">
        <div class="card-header-modern">
            <h2 class="card-title-modern">
                <div class="card-title-icon">
                    <i class="fas fa-birthday-cake"></i>
                </div>
                Cumpleaños Próximos
            </h2>
            <a href="clients.php" class="btn-modern btn-modern-primary">Ver Todos</a>
        </div>

        <?php if(count($upcoming_birthdays) > 0): ?>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Fecha de Cumpleaños</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($upcoming_birthdays as $birthday): ?>
                <tr>
                    <td>
                        <div class="user-cell-modern">
                            <div class="modern-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <?php echo htmlspecialchars($birthday['nombre_cliente']); ?>
                        </div>
                    </td>
                    <td>
                        <strong><?php echo date('d/m', strtotime($birthday['cumpleaños'])); ?></strong>
                    </td>
                    <td>
                        <?php 
                            $today = new DateTime(date('Y-m-d'));
                            $tomorrow = new DateTime(date('Y-m-d'));
                            $tomorrow->modify('+1 day');
                            
                            $birth_date = new DateTime(date('Y-m-d', strtotime($birthday['cumpleaños'])));
                            $birth_date->setDate(date('Y'), $birth_date->format('m'), $birth_date->format('d'));
                            
                            if ($birth_date < $today) {
                                $birth_date->modify('+1 year');
                            }
                            
                            if ($birth_date->format('Y-m-d') === $today->format('Y-m-d')) {
                                $status_class = 'pending';
                                $days_text = 'Hoy';
                            } elseif ($birth_date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                                $status_class = 'pending';
                                $days_text = 'Mañana';
                            } else {
                                $days_left = $today->diff($birth_date)->days;
                                
                                $status_class = 'completed';
                                if ($days_left <= 7) {
                                    $status_class = 'pending';
                                } else if ($days_left <= 14) {
                                    $status_class = 'processing';
                                }
                                
                                $days_text = $days_left == 1 ? '1 día' : $days_left . ' días';
                            }
                        ?>
                        <span class="status-modern <?php echo $status_class; ?>">
                            <?php echo $days_text; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons-modern">
                            <a href="client-view.php?id=<?php echo $birthday['id_cliente']; ?>" 
                               class="btn-modern btn-modern-icon" title="Ver Cliente">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-birthday-cake"></i>
            </div>
            <p>No hay cumpleaños próximos en los siguientes 30 días</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Clientes Recientes -->
    <div class="dashboard-card">
        <div class="card-header-modern">
            <h2 class="card-title-modern">
                <div class="card-title-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                Clientes Recientes
            </h2>
            <a href="clients.php" class="btn-modern btn-modern-primary">Ver Todos</a>
        </div>

        <?php if(count($recent_clients) > 0): ?>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Plan</th>
                    <th>Fecha de Inicio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_clients as $client_item): ?>
                <tr>
                    <td>
                        <div class="user-cell-modern">
                            <div class="modern-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <?php echo htmlspecialchars($client_item['nombre_cliente']); ?>
                        </div>
                    </td>
                    <td>
                        <?php if($client_item['nombre_plan']): ?>
                            <span style="color: #374151; font-weight: 500;">
                                <?php echo htmlspecialchars($client_item['nombre_plan']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #9ca3af; font-style: italic;">No asignado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo date('d/m/Y', strtotime($client_item['fecha_inicio'])); ?></strong>
                    </td>
                    <td>
                        <div class="action-buttons-modern">
                            <a href="client-view.php?id=<?php echo $client_item['id_cliente']; ?>" 
                               class="btn-modern btn-modern-icon" title="Ver Cliente">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if(isAdmin()): ?>
                            <a href="client-form.php?id=<?php echo $client_item['id_cliente']; ?>" 
                               class="btn-modern btn-modern-icon" title="Editar Cliente">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <p>No hay clientes recientes</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'includes/layout_footer.php';
?>
