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
    public $estado; // Nuevo: estado del cliente

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create client - Versión simplificada para evitar errores
    public function create() {
        // Sanitize values
        $this->nombre_cliente = htmlspecialchars(strip_tags($this->nombre_cliente));
        $this->fecha_inicio = htmlspecialchars(strip_tags($this->fecha_inicio));
        $this->cumpleaños = htmlspecialchars(strip_tags($this->cumpleaños));
        $this->fecha_pago = htmlspecialchars(strip_tags($this->fecha_pago));
        $this->estado = htmlspecialchars(strip_tags($this->estado)); // Nuevo: estado del cliente
        
        // Consulta básica incluyendo el campo estado
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre_cliente, fecha_inicio, cumpleaños, fecha_pago, estado) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(1, $this->nombre_cliente);
        $stmt->bindParam(2, $this->fecha_inicio);
        $stmt->bindParam(3, $this->cumpleaños);
        $stmt->bindParam(4, $this->fecha_pago);
        $stmt->bindParam(5, $this->estado); // Nuevo: estado del cliente
        
        if ($stmt->execute()) {
            $this->id_cliente = $this->conn->lastInsertId();
            
            // Actualizar id_plan e id_empresa si es necesario
            if (!empty($this->id_plan) || !empty($this->id_empresa)) {
                $updateQuery = "UPDATE " . $this->table_name . " SET ";
                $params = array();
                
                if (!empty($this->id_plan)) {
                    $updateQuery .= "id_plan = ?, ";
                    $params[] = $this->id_plan;
                }
                
                if (!empty($this->id_empresa)) {
                    $updateQuery .= "id_empresa = ?, ";
                    $params[] = $this->id_empresa;
                }
                
                // Eliminar la última coma y espacio
                $updateQuery = rtrim($updateQuery, ", ");
                
                // Agregar la condición WHERE
                $updateQuery .= " WHERE id_cliente = ?";
                $params[] = $this->id_cliente;
                
                // Preparar y ejecutar la consulta de actualización
                $updateStmt = $this->conn->prepare($updateQuery);
                for ($i = 0; $i < count($params); $i++) {
                    $updateStmt->bindParam($i + 1, $params[$i]);
                }
                
                $updateStmt->execute();
            }
            
            return true;
        }
        
        return false;
    }
    

    public function read($user_id, $is_admin) {
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
            $stmt->bindParam(1, $user_id);
        }

        $stmt->execute();
        return $stmt;
    }

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
            return true;
        }

        return false;
    }

    public function update() {
        // Sanitize values
        $this->nombre_cliente = htmlspecialchars(strip_tags($this->nombre_cliente));
        $this->fecha_inicio = htmlspecialchars(strip_tags($this->fecha_inicio));
        $this->cumpleaños = htmlspecialchars(strip_tags($this->cumpleaños));
        $this->fecha_pago = htmlspecialchars(strip_tags($this->fecha_pago));
        $this->estado = htmlspecialchars(strip_tags($this->estado)); // Nuevo: Estado del cliente
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));
        
        // Consulta básica para actualizar campos obligatorios
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_cliente = ?, 
                      fecha_inicio = ?, 
                      cumpleaños = ?, 
                      fecha_pago = ?, 
                      estado = ?, 
                      id_plan = ?, 
                      id_empresa = ?
                  WHERE id_cliente = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Preparar valores para id_plan e id_empresa (pueden ser NULL)
        $id_plan = !empty($this->id_plan) ? $this->id_plan : null;
        $id_empresa = !empty($this->id_empresa) ? $this->id_empresa : null;
        
        // Bind values
        $stmt->bindParam(1, $this->nombre_cliente);
        $stmt->bindParam(2, $this->fecha_inicio);
        $stmt->bindParam(3, $this->cumpleaños);
        $stmt->bindParam(4, $this->fecha_pago);
        $stmt->bindParam(5, $this->estado); // Nuevo: Estado del cliente
        $stmt->bindParam(6, $id_plan);
        $stmt->bindParam(7, $id_empresa);
        $stmt->bindParam(8, $this->id_cliente);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
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
    
    // Count total clients
    public function countClients($user_id, $is_admin) {
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
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Get upcoming payments
    public function getUpcomingPayments($user_id, $is_admin, $days = 7) {
        // Calcula el rango dinámico de fechas
        $date = date('Y-m-d', strtotime('+' . $days . ' days'));
    
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      WHERE c.fecha_pago BETWEEN CURDATE() AND ?
                      ORDER BY c.fecha_pago ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $date, PDO::PARAM_STR);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ? AND c.fecha_pago BETWEEN CURDATE() AND ?
                      ORDER BY c.fecha_pago ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $date, PDO::PARAM_STR);
        }
    
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    
    // Get upcoming birthdays
    public function getUpcomingBirthdays($user_id, $is_admin, $days = 30) {
        // This is more complex because we need to handle year wrapping
        // For simplicity, we'll use a less precise approach
        $today_month = date('m');
        $today_day = date('d');
        $next_month = date('m', strtotime('+' . $days . ' days'));
        $next_day = date('d', strtotime('+' . $days . ' days'));
        
        if($is_admin) {
            // This is a simplified approach that doesn't handle year wrapping perfectly
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      WHERE (
                          (MONTH(c.cumpleaños) = ? AND DAY(c.cumpleaños) >= ?) OR
                          (MONTH(c.cumpleaños) = ? AND DAY(c.cumpleaños) <= ?) OR
                          (MONTH(c.cumpleaños) > ? AND MONTH(c.cumpleaños) < ?)
                      )
                      ORDER BY MONTH(c.cumpleaños), DAY(c.cumpleaños)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $today_month);
            $stmt->bindParam(2, $today_day);
            $stmt->bindParam(3, $next_month);
            $stmt->bindParam(4, $next_day);
            $stmt->bindParam(5, $today_month);
            $stmt->bindParam(6, $next_month);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ? AND (
                          (MONTH(c.cumpleaños) = ? AND DAY(c.cumpleaños) >= ?) OR
                          (MONTH(c.cumpleaños) = ? AND DAY(c.cumpleaños) <= ?) OR
                          (MONTH(c.cumpleaños) > ? AND MONTH(c.cumpleaños) < ?)
                      )
                      ORDER BY MONTH(c.cumpleaños), DAY(c.cumpleaños)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $today_month);
            $stmt->bindParam(3, $today_day);
            $stmt->bindParam(4, $next_month);
            $stmt->bindParam(5, $next_day);
            $stmt->bindParam(6, $today_month);
            $stmt->bindParam(7, $next_month);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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