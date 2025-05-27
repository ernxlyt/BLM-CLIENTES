<?php
class Client {
    private $conn;
    private $table_name = "clientes";

    public $id_cliente;
    public $nombre_cliente;
    public $fecha_inicio;
    public $cumpleaños;
    public $fecha_pago;
    public $id_plan;
    public $nombre_plan; 
    public $id_empresa;
    public $nombre_empresa; 
    public $rubro_empresa;
    public $estado;
    public $pais;
    public $provincia;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
            (nombre_cliente, fecha_inicio, cumpleaños, fecha_pago, estado, id_plan, id_empresa, pais, provincia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar valores
        $this->nombre_cliente = htmlspecialchars(strip_tags($this->nombre_cliente));
        $this->fecha_inicio = htmlspecialchars(strip_tags($this->fecha_inicio));
        $this->cumpleaños = htmlspecialchars(strip_tags($this->cumpleaños));
        $this->fecha_pago = htmlspecialchars(strip_tags($this->fecha_pago));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        
        $this->pais = $this->pais ? htmlspecialchars(strip_tags($this->pais)) : null;
        $this->provincia = $this->provincia ? htmlspecialchars(strip_tags($this->provincia)) : null;
        
        // Preparar valores para id_plan e id_empresa (pueden ser NULL)
        $id_plan = !empty($this->id_plan) ? $this->id_plan : null;
        $id_empresa = !empty($this->id_empresa) ? $this->id_empresa : null;

        $stmt->bindParam(1, $this->nombre_cliente);
        $stmt->bindParam(2, $this->fecha_inicio);
        $stmt->bindParam(3, $this->cumpleaños);
        $stmt->bindParam(4, $this->fecha_pago);
        $stmt->bindParam(5, $this->estado);
        $stmt->bindParam(6, $id_plan);
        $stmt->bindParam(7, $id_empresa);
        $stmt->bindParam(8, $this->pais);
        $stmt->bindParam(9, $this->provincia);

        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Read all clients with pagination
    public function read($user_id, $is_admin, $page = 1, $records_per_page = 15) {
        $offset = ($page - 1) * $records_per_page;
        
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      ORDER BY c.id_cliente DESC
                      LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $records_per_page, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ?
                      ORDER BY c.id_cliente DESC
                      LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
            $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt;
    }

    // Search clients with pagination
    public function search($user_id, $is_admin, $search_term, $page = 1, $records_per_page = 15) {
        $offset = ($page - 1) * $records_per_page;
        $search_term = "%{$search_term}%";
        
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      WHERE (c.nombre_cliente LIKE ? 
                         OR e.nombre_empresa LIKE ? 
                         OR c.pais LIKE ? 
                         OR c.provincia LIKE ? 
                         OR p.nombre_plan LIKE ? 
                         OR c.estado LIKE ?)
                      ORDER BY c.id_cliente DESC
                      LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $search_term);
            $stmt->bindParam(2, $search_term);
            $stmt->bindParam(3, $search_term);
            $stmt->bindParam(4, $search_term);
            $stmt->bindParam(5, $search_term);
            $stmt->bindParam(6, $search_term);
            $stmt->bindParam(7, $records_per_page, PDO::PARAM_INT);
            $stmt->bindParam(8, $offset, PDO::PARAM_INT);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ?
                        AND (c.nombre_cliente LIKE ? 
                         OR e.nombre_empresa LIKE ? 
                         OR c.pais LIKE ? 
                         OR c.provincia LIKE ? 
                         OR p.nombre_plan LIKE ? 
                         OR c.estado LIKE ?)
                      ORDER BY c.id_cliente DESC
                      LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $search_term);
            $stmt->bindParam(3, $search_term);
            $stmt->bindParam(4, $search_term);
            $stmt->bindParam(5, $search_term);
            $stmt->bindParam(6, $search_term);
            $stmt->bindParam(7, $search_term);
            $stmt->bindParam(8, $records_per_page, PDO::PARAM_INT);
            $stmt->bindParam(9, $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt;
    }

    // Count total clients for pagination
    public function countClients($user_id, $is_admin, $search_term = '') {
        if (!empty($search_term)) {
            $search_term = "%{$search_term}%";
            
            if($is_admin) {
                $query = "SELECT COUNT(*) as total 
                          FROM " . $this->table_name . " c
                          LEFT JOIN planes p ON c.id_plan = p.id_plan
                          LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                          WHERE (c.nombre_cliente LIKE ? 
                             OR e.nombre_empresa LIKE ? 
                             OR c.pais LIKE ? 
                             OR c.provincia LIKE ? 
                             OR p.nombre_plan LIKE ? 
                             OR c.estado LIKE ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $search_term);
                $stmt->bindParam(2, $search_term);
                $stmt->bindParam(3, $search_term);
                $stmt->bindParam(4, $search_term);
                $stmt->bindParam(5, $search_term);
                $stmt->bindParam(6, $search_term);
            } else {
                $query = "SELECT COUNT(DISTINCT c.id_cliente) as total 
                          FROM " . $this->table_name . " c
                          LEFT JOIN planes p ON c.id_plan = p.id_plan
                          LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                          JOIN relaciones r ON c.id_cliente = r.id_cliente
                          WHERE r.id_usuario = ?
                            AND (c.nombre_cliente LIKE ? 
                             OR e.nombre_empresa LIKE ? 
                             OR c.pais LIKE ? 
                             OR c.provincia LIKE ? 
                             OR p.nombre_plan LIKE ? 
                             OR c.estado LIKE ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $user_id);
                $stmt->bindParam(2, $search_term);
                $stmt->bindParam(3, $search_term);
                $stmt->bindParam(4, $search_term);
                $stmt->bindParam(5, $search_term);
                $stmt->bindParam(6, $search_term);
                $stmt->bindParam(7, $search_term);
            }
        } else {
            if($is_admin) {
                $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
                $stmt = $this->conn->prepare($query);
            } else {
                $query = "SELECT COUNT(DISTINCT c.id_cliente) as total 
                          FROM " . $this->table_name . " c
                          JOIN relaciones r ON c.id_cliente = r.id_cliente
                          WHERE r.id_usuario = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $user_id);
            }
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Read one client
    public function readOne($user_id, $is_admin) {
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      WHERE c.id_cliente = ? 
                      LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id_cliente);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE c.id_cliente = ? AND r.id_usuario = ?
                      LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id_cliente);
            $stmt->bindParam(2, $user_id);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_cliente = $row['id_cliente'];
            $this->nombre_cliente = $row['nombre_cliente'];
            $this->fecha_inicio = $row['fecha_inicio'];
            $this->cumpleaños = $row['cumpleaños'];
            $this->fecha_pago = $row['fecha_pago'];
            $this->estado = $row['estado'];
            $this->id_plan = $row['id_plan'];
            $this->nombre_plan = $row['nombre_plan'];
            $this->id_empresa = $row['id_empresa'];
            $this->nombre_empresa = $row['nombre_empresa'];
            $this->rubro_empresa = $row['rubro_empresa'];
            $this->pais = $row['pais'] ?? null;
            $this->provincia = $row['provincia'] ?? null;
            
            return true;
        }

        return false;
    }

    // Update client
    public function update() {
        // Sanitize values
        $this->nombre_cliente = htmlspecialchars(strip_tags($this->nombre_cliente));
        $this->fecha_inicio = htmlspecialchars(strip_tags($this->fecha_inicio));
        $this->cumpleaños = htmlspecialchars(strip_tags($this->cumpleaños));
        $this->fecha_pago = htmlspecialchars(strip_tags($this->fecha_pago));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));
        
        $this->pais = $this->pais ? htmlspecialchars(strip_tags($this->pais)) : null;
        $this->provincia = $this->provincia ? htmlspecialchars(strip_tags($this->provincia)) : null;
        
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_cliente = ?, 
                      fecha_inicio = ?, 
                      cumpleaños = ?, 
                      fecha_pago = ?, 
                      estado = ?, 
                      id_plan = ?, 
                      id_empresa = ?,
                      pais = ?,
                      provincia = ?
                  WHERE id_cliente = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Preparar valores para id_plan e id_empresa (pueden ser NULL)
        $id_plan = !empty($this->id_plan) ? $this->id_plan : null;
        $id_empresa = !empty($this->id_empresa) ? $this->id_empresa : null;
        
        $stmt->bindParam(1, $this->nombre_cliente);
        $stmt->bindParam(2, $this->fecha_inicio);
        $stmt->bindParam(3, $this->cumpleaños);
        $stmt->bindParam(4, $this->fecha_pago);
        $stmt->bindParam(5, $this->estado);
        $stmt->bindParam(6, $id_plan);
        $stmt->bindParam(7, $id_empresa);
        $stmt->bindParam(8, $this->pais);
        $stmt->bindParam(9, $this->provincia);
        $stmt->bindParam(10, $this->id_cliente);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete client
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_cliente = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_cliente, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Get social networks for a client
    public function getSocialNetworks() {
        $social_networks = array();

        // Get Instagram
        $query = "SELECT * FROM instagram WHERE id_cliente = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_cliente);
        $stmt->execute();
        $social_networks['instagram'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get Facebook
        $query = "SELECT * FROM facebook WHERE id_cliente = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_cliente);
        $stmt->execute();
        $social_networks['facebook'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get YouTube
        $query = "SELECT * FROM youtube WHERE id_cliente = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_cliente);
        $stmt->execute();
        $social_networks['youtube'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $social_networks;
    }

    // Get upcoming payments
    public function getUpcomingPayments($user_id, $is_admin, $days = 7) {
        // Obtener todos los clientes primero
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      ORDER BY c.id_cliente DESC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ?
                      ORDER BY c.id_cliente DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $all_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $upcoming_payments = [];
        $today = new DateTime();
        $end_date = new DateTime();
        $end_date->modify("+{$days} days");
        
        foreach ($all_clients as $client) {
            if (empty($client['fecha_pago'])) continue;
            
            // Obtener el día del mes de la fecha de pago
            $payment_day = (int)date('d', strtotime($client['fecha_pago']));
            
            // Verificar los próximos meses para encontrar fechas dentro del rango
            for ($month_offset = 0; $month_offset <= 2; $month_offset++) {
                $check_date = new DateTime();
                $check_date->modify("+{$month_offset} months");
                
                // Obtener el último día del mes
                $last_day_of_month = (int)$check_date->format('t');
                
                // Ajustar el día si es mayor al último día del mes
                $actual_payment_day = min($payment_day, $last_day_of_month);
                
                // Crear la fecha de pago para este mes
                $payment_date = new DateTime($check_date->format('Y-m-') . sprintf('%02d', $actual_payment_day));
                
                // Verificar si está dentro del rango
                if ($payment_date >= $today && $payment_date <= $end_date) {
                    // Agregar información de la fecha calculada al cliente
                    $client['calculated_payment_date'] = $payment_date->format('Y-m-d');
                    $client['days_until_payment'] = $today->diff($payment_date)->days;
                    
                    $upcoming_payments[] = $client;
                    break; // Solo agregar una vez por cliente
                }
            }
        }
        
        // Ordenar por fecha de pago más cercana
        usort($upcoming_payments, function($a, $b) {
            return strtotime($a['calculated_payment_date']) - strtotime($b['calculated_payment_date']);
        });
        
        return $upcoming_payments;
    }
    
    // Get upcoming birthdays
    public function getUpcomingBirthdays($user_id, $is_admin, $days = 30) {
        // Obtener todos los clientes primero
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      ORDER BY c.id_cliente DESC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ?
                      ORDER BY c.id_cliente DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $all_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $upcoming_birthdays = [];
        $today = new DateTime();
        $end_date = new DateTime();
        $end_date->modify("+{$days} days");
        
        foreach ($all_clients as $client) {
            if (empty($client['cumpleaños'])) continue;
            
            // Obtener mes y día del cumpleaños
            $birth_month = (int)date('m', strtotime($client['cumpleaños']));
            $birth_day = (int)date('d', strtotime($client['cumpleaños']));
            
            // Verificar este año y el próximo
            for ($year_offset = 0; $year_offset <= 1; $year_offset++) {
                $check_year = (int)$today->format('Y') + $year_offset;
                
                // Crear la fecha de cumpleaños para este año
                $birthday_date = new DateTime();
                $birthday_date->setDate($check_year, $birth_month, $birth_day);
                
                // Verificar si está dentro del rango
                if ($birthday_date >= $today && $birthday_date <= $end_date) {
                    // Agregar información de la fecha calculada al cliente
                    $client['calculated_birthday_date'] = $birthday_date->format('Y-m-d');
                    $client['days_until_birthday'] = $today->diff($birthday_date)->days;
                    
                    $upcoming_birthdays[] = $client;
                    break; // Solo agregar una vez por cliente
                }
            }
        }
        
        // Ordenar por fecha de cumpleaños más cercana
        usort($upcoming_birthdays, function($a, $b) {
            return strtotime($a['calculated_birthday_date']) - strtotime($b['calculated_birthday_date']);
        });
        
        return $upcoming_birthdays;
    }
    
    // Get recent clients
    public function getRecentClients($user_id, $is_admin, $limit = 5) {
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      ORDER BY c.id_cliente DESC
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ?
                      ORDER BY c.id_cliente DESC
                      LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>