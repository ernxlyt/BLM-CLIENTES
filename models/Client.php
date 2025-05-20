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

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create client
    public function create() {
        // Código existente...
        // (Mantengo el resto del código igual)
    }

    // Read all clients
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

    // Delete client
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cliente", $this->id_cliente, PDO::PARAM_INT);

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

    // Get upcoming payments - MÉTODO CORREGIDO PARA PAGOS MENSUALES
    public function getUpcomingPayments($user_id, $is_admin, $days = 7) {
        // Usamos DATE_FORMAT para comparar solo el día del mes
        // Esto nos permite encontrar pagos que ocurren en el mismo día del mes actual o próximo
        
        if($is_admin) {
            $query = "SELECT c.*, p.nombre_plan, e.nombre_empresa, e.rubro as rubro_empresa
                      FROM " . $this->table_name . " c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      WHERE 
                        -- Pagos que ocurren hoy (mismo día del mes)
                        (DAY(c.fecha_pago) = DAY(CURDATE()))
                        OR
                        -- Pagos que ocurren en los próximos días de este mes
                        (
                            DAY(c.fecha_pago) > DAY(CURDATE())
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY)
                            AND
                            DAY(CURDATE() + INTERVAL ? DAY) <= DAY(LAST_DAY(CURDATE()))
                        )
                        OR
                        -- Pagos que ocurren a principios del próximo mes (si el rango cruza al siguiente mes)
                        (
                            DAY(CURDATE() + INTERVAL ? DAY) > DAY(LAST_DAY(CURDATE()))
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY) - DAY(LAST_DAY(CURDATE()))
                        )
                      ORDER BY 
                        -- Ordenar primero por si es hoy
                        (DAY(c.fecha_pago) = DAY(CURDATE())) DESC,
                        -- Luego por día del mes
                        CASE
                            WHEN DAY(c.fecha_pago) >= DAY(CURDATE()) THEN DAY(c.fecha_pago)
                            ELSE DAY(c.fecha_pago) + 100 -- Para que los del próximo mes aparezcan después
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
                        -- Pagos que ocurren hoy (mismo día del mes)
                        (DAY(c.fecha_pago) = DAY(CURDATE()))
                        OR
                        -- Pagos que ocurren en los próximos días de este mes
                        (
                            DAY(c.fecha_pago) > DAY(CURDATE())
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY)
                            AND
                            DAY(CURDATE() + INTERVAL ? DAY) <= DAY(LAST_DAY(CURDATE()))
                        )
                        OR
                        -- Pagos que ocurren a principios del próximo mes (si el rango cruza al siguiente mes)
                        (
                            DAY(CURDATE() + INTERVAL ? DAY) > DAY(LAST_DAY(CURDATE()))
                            AND
                            DAY(c.fecha_pago) <= DAY(CURDATE() + INTERVAL ? DAY) - DAY(LAST_DAY(CURDATE()))
                        )
                      )
                      ORDER BY 
                        -- Ordenar primero por si es hoy
                        (DAY(c.fecha_pago) = DAY(CURDATE())) DESC,
                        -- Luego por día del mes
                        CASE
                            WHEN DAY(c.fecha_pago) >= DAY(CURDATE()) THEN DAY(c.fecha_pago)
                            ELSE DAY(c.fecha_pago) + 100 -- Para que los del próximo mes aparezcan después
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
        // Primero, vamos a hacer una consulta de depuración para ver qué está pasando
        $debug_query = "SELECT id_cliente, nombre_cliente, cumpleaños, 
                       DATE_FORMAT(cumpleaños, '%m-%d') as fecha_cumple, 
                       DATE_FORMAT(CURDATE(), '%m-%d') as fecha_hoy
                       FROM " . $this->table_name . "
                       WHERE DATE_FORMAT(cumpleaños, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')";
        
        $debug_stmt = $this->conn->prepare($debug_query);
        $debug_stmt->execute();
        $debug_results = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Guardar resultados de depuración en un archivo para revisión
        file_put_contents('debug_birthdays.txt', print_r($debug_results, true));
        
        // Ahora la consulta principal con un enfoque más simple
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
}
?>
