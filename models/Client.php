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
        $stmt->bindParam(8, $this->pais);        // ✅ NUEVO

        if ($stmt->execute()) {
            return true;
        }
        
        // Para debugging, puedes descomentar esta línea:
        // print_r($stmt->errorInfo());
        
        return false;
    }

    public function createWithNamedParams() {
        $query = "INSERT INTO " . $this->table_name . " 
            (nombre_cliente, fecha_inicio, cumpleaños, fecha_pago, estado, id_plan, id_empresa, pais, provincia)
            VALUES (:nombre_cliente, :fecha_inicio, :cumpleanos, :fecha_pago, :estado, :id_plan, :id_empresa, :pais, :provincia)";

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

        $stmt->bindParam(':nombre_cliente', $this->nombre_cliente);
        $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
        $stmt->bindParam(':cumpleanos', $this->cumpleaños);
        $stmt->bindParam(':fecha_pago', $this->fecha_pago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id_plan', $id_plan);
        $stmt->bindParam(':id_empresa', $id_empresa);
        $stmt->bindParam(':pais', $this->pais);           
        $stmt->bindParam(':provincia', $this->provincia); 

        if ($stmt->execute()) {
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
                        $this->pais = $row['pais'] ?? null;
            $this->provincia = $row['provincia'] ?? null;
            
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
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      WHERE 
                        (DAY(c.fecha_pago) = DAY(CURDATE()))
                        OR
                        (
                            DAY(c.fecha_pago) > DAY(CURDATE())
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY)
                            AND
                            DAY(CURDATE() + INTERVAL ? DAY) <= DAY(LAST_DAY(CURDATE()))
                        )
                        OR
                        (
                            DAY(CURDATE() + INTERVAL ? DAY) > DAY(LAST_DAY(CURDATE()))
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY) - DAY(LAST_DAY(CURDATE()))
                        )
                      ORDER BY 
                        (DAY(c.fecha_pago) = DAY(CURDATE())) DESC,
                        CASE
                            WHEN DAY(c.fecha_pago) >= DAY(CURDATE()) THEN DAY(c.fecha_pago)
                            ELSE DAY(c.fecha_pago) + 100
                        END ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $days, PDO::PARAM_INT);
            $stmt->bindParam(2, $days, PDO::PARAM_INT);
            $stmt->bindParam(3, $days, PDO::PARAM_INT);
            $stmt->bindParam(4, $days, PDO::PARAM_INT);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ? AND (
                        (DAY(c.fecha_pago) = DAY(CURDATE()))
                        OR
                        (
                            DAY(c.fecha_pago) > DAY(CURDATE())
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY)
                            AND
                            DAY(CURDATE() + INTERVAL ? DAY) <= DAY(LAST_DAY(CURDATE()))
                        )
                        OR
                        (
                            DAY(CURDATE() + INTERVAL ? DAY) > DAY(LAST_DAY(CURDATE()))
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY) - DAY(LAST_DAY(CURDATE()))
                        )
                      )
                      ORDER BY 
                        (DAY(c.fecha_pago) = DAY(CURDATE())) DESC,
                        CASE
                            WHEN DAY(c.fecha_pago) >= DAY(CURDATE()) THEN DAY(c.fecha_pago)
                            ELSE DAY(c.fecha_pago) + 100
                        END ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $days, PDO::PARAM_INT);
            $stmt->bindParam(3, $days, PDO::PARAM_INT);
            $stmt->bindParam(4, $days, PDO::PARAM_INT);
            $stmt->bindParam(5, $days, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get upcoming birthdays
    public function getUpcomingBirthdays($user_id, $is_admin, $days = 30) {
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      WHERE DATE_FORMAT(c.cumpleaños, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
                      OR (
                          DATE_FORMAT(c.cumpleaños, '%m-%d') > DATE_FORMAT(CURDATE(), '%m-%d')
                          AND 
                          DATE_FORMAT(c.cumpleaños, '%m-%d') <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL ? DAY), '%m-%d')
                      )
                      ORDER BY 
                          DATE_FORMAT(c.cumpleaños, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') DESC,
                          DATE_FORMAT(c.cumpleaños, '%m-%d') ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $days, PDO::PARAM_INT);
        } else {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ? AND (
                          DATE_FORMAT(c.cumpleaños, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
                          OR (
                              DATE_FORMAT(c.cumpleaños, '%m-%d') > DATE_FORMAT(CURDATE(), '%m-%d')
                              AND 
                              DATE_FORMAT(c.cumpleaños, '%m-%d') <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL ? DAY), '%m-%d')
                          )
                      )
                      ORDER BY 
                          DATE_FORMAT(c.cumpleaños, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') DESC,
                          DATE_FORMAT(c.cumpleaños, '%m-%d') ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $days, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    
    // Obtener clientes por país
    public function getClientsByCountry($user_id, $is_admin) {
        if($is_admin) {
            $query = "SELECT pais, COUNT(*) as total_clientes
                      FROM " . $this->table_name . "
                      WHERE pais IS NOT NULL AND pais != ''
                      GROUP BY pais
                      ORDER BY total_clientes DESC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT c.pais, COUNT(*) as total_clientes
                      FROM " . $this->table_name . " c
                      JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = ? AND c.pais IS NOT NULL AND c.pais != ''
                      GROUP BY c.pais
                      ORDER BY total_clientes DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener clientes por provincia
    public function getClientsByProvince($user_id, $is_admin, $country = null) {
        if($is_admin) {
            if($country) {
                $query = "SELECT provincia, COUNT(*) as total_clientes
                          FROM " . $this->table_name . "
                          WHERE pais = ? AND provincia IS NOT NULL AND provincia != ''
                          GROUP BY provincia
                          ORDER BY total_clientes DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $country);
            } else {
                $query = "SELECT pais, provincia, COUNT(*) as total_clientes
                          FROM " . $this->table_name . "
                          WHERE provincia IS NOT NULL AND provincia != ''
                          GROUP BY pais, provincia
                          ORDER BY pais, total_clientes DESC";
                $stmt = $this->conn->prepare($query);
            }
        } else {
            if($country) {
                $query = "SELECT c.provincia, COUNT(*) as total_clientes
                          FROM " . $this->table_name . " c
                          JOIN relaciones r ON c.id_cliente = r.id_cliente
                          WHERE r.id_usuario = ? AND c.pais = ? AND c.provincia IS NOT NULL AND c.provincia != ''
                          GROUP BY c.provincia
                          ORDER BY total_clientes DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $user_id);
                $stmt->bindParam(2, $country);
            } else {
                $query = "SELECT c.pais, c.provincia, COUNT(*) as total_clientes
                          FROM " . $this->table_name . " c
                          JOIN relaciones r ON c.id_cliente = r.id_cliente
                          WHERE r.id_usuario = ? AND c.provincia IS NOT NULL AND c.provincia != ''
                          GROUP BY c.pais, c.provincia
                          ORDER BY c.pais, total_clientes DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $user_id);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>