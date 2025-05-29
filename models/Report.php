<?php
class Report {
    private $conn;
    private $table_name = "reportes_transacciones";
    
    public $id_reporte;
    public $id_cliente;
    public $fecha_transaccion;
    public $metodo_pago;
    public $numero_referencia;
    public $fecha_desde;
    public $fecha_hasta;
    public $monto;
    public $notas;
    public $fecha_creacion;
    public $id_usuario_creador;
    
    // Propiedades para joins
    public $nombre_cliente;
    public $nombre_plan;
    public $nombre_empresa;
    public $nombre_usuario_creador;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Crear nuevo reporte
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_cliente=:id_cliente, 
                      fecha_transaccion=:fecha_transaccion, 
                      metodo_pago=:metodo_pago, 
                      numero_referencia=:numero_referencia, 
                      fecha_desde=:fecha_desde, 
                      fecha_hasta=:fecha_hasta, 
                      monto=:monto, 
                      notas=:notas, 
                      id_usuario_creador=:id_usuario_creador";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));
        $this->fecha_transaccion = htmlspecialchars(strip_tags($this->fecha_transaccion));
        $this->metodo_pago = htmlspecialchars(strip_tags($this->metodo_pago));
        $this->numero_referencia = htmlspecialchars(strip_tags($this->numero_referencia));
        $this->fecha_desde = htmlspecialchars(strip_tags($this->fecha_desde));
        $this->fecha_hasta = htmlspecialchars(strip_tags($this->fecha_hasta));
        $this->monto = htmlspecialchars(strip_tags($this->monto));
        $this->notas = htmlspecialchars(strip_tags($this->notas));
        $this->id_usuario_creador = htmlspecialchars(strip_tags($this->id_usuario_creador));
        
        // Bind valores
        $stmt->bindParam(":id_cliente", $this->id_cliente);
        $stmt->bindParam(":fecha_transaccion", $this->fecha_transaccion);
        $stmt->bindParam(":metodo_pago", $this->metodo_pago);
        $stmt->bindParam(":numero_referencia", $this->numero_referencia);
        $stmt->bindParam(":fecha_desde", $this->fecha_desde);
        $stmt->bindParam(":fecha_hasta", $this->fecha_hasta);
        $stmt->bindParam(":monto", $this->monto);
        $stmt->bindParam(":notas", $this->notas);
        $stmt->bindParam(":id_usuario_creador", $this->id_usuario_creador);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Leer todos los reportes con información del cliente
    public function read($user_id = null, $is_admin = false) {
        $query = "SELECT 
                    r.id_reporte,
                    r.id_cliente,
                    r.fecha_transaccion,
                    r.metodo_pago,
                    r.numero_referencia,
                    r.fecha_desde,
                    r.fecha_hasta,
                    r.monto,
                    r.notas,
                    r.fecha_creacion,
                    c.nombre_cliente,
                    p.nombre_plan,
                    e.nombre_empresa,
                    u.nombre_usuario as nombre_usuario_creador
                  FROM " . $this->table_name . " r
                  LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                  LEFT JOIN planes p ON c.id_plan = p.id_plan
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  LEFT JOIN usuarios u ON r.id_usuario_creador = u.id_usuario";
        
        if (!$is_admin && $user_id) {
            $query .= " INNER JOIN relaciones rel ON c.id_cliente = rel.id_cliente 
                        WHERE rel.id_usuario = :user_id";
        }
        
        $query .= " ORDER BY r.fecha_transaccion DESC, r.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$is_admin && $user_id) {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->execute();
        
        return $stmt;
    }
    
    // Leer un reporte específico
    public function readOne($user_id = null, $is_admin = false) {
        $query = "SELECT 
                    r.id_reporte,
                    r.id_cliente,
                    r.fecha_transaccion,
                    r.metodo_pago,
                    r.numero_referencia,
                    r.fecha_desde,
                    r.fecha_hasta,
                    r.monto,
                    r.notas,
                    r.fecha_creacion,
                    c.nombre_cliente,
                    p.nombre_plan,
                    e.nombre_empresa,
                    u.nombre_usuario as nombre_usuario_creador
                  FROM " . $this->table_name . " r
                  LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                  LEFT JOIN planes p ON c.id_plan = p.id_plan
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  LEFT JOIN usuarios u ON r.id_usuario_creador = u.id_usuario
                  WHERE r.id_reporte = :id_reporte";
        
        if (!$is_admin && $user_id) {
            $query .= " AND EXISTS (
                          SELECT 1 FROM relaciones rel 
                          WHERE rel.id_cliente = c.id_cliente 
                          AND rel.id_usuario = :user_id
                        )";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_reporte', $this->id_reporte);
        
        if (!$is_admin && $user_id) {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id_reporte = $row['id_reporte'];
            $this->id_cliente = $row['id_cliente'];
            $this->fecha_transaccion = $row['fecha_transaccion'];
            $this->metodo_pago = $row['metodo_pago'];
            $this->numero_referencia = $row['numero_referencia'];
            $this->fecha_desde = $row['fecha_desde'];
            $this->fecha_hasta = $row['fecha_hasta'];
            $this->monto = $row['monto'];
            $this->notas = $row['notas'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->nombre_cliente = $row['nombre_cliente'];
            $this->nombre_plan = $row['nombre_plan'];
            $this->nombre_empresa = $row['nombre_empresa'];
            $this->nombre_usuario_creador = $row['nombre_usuario_creador'];
            
            return true;
        }
        
        return false;
    }
    
    // Actualizar reporte
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET id_cliente=:id_cliente, 
                      fecha_transaccion=:fecha_transaccion, 
                      metodo_pago=:metodo_pago, 
                      numero_referencia=:numero_referencia, 
                      fecha_desde=:fecha_desde, 
                      fecha_hasta=:fecha_hasta, 
                      monto=:monto, 
                      notas=:notas
                  WHERE id_reporte=:id_reporte";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));
        $this->fecha_transaccion = htmlspecialchars(strip_tags($this->fecha_transaccion));
        $this->metodo_pago = htmlspecialchars(strip_tags($this->metodo_pago));
        $this->numero_referencia = htmlspecialchars(strip_tags($this->numero_referencia));
        $this->fecha_desde = htmlspecialchars(strip_tags($this->fecha_desde));
        $this->fecha_hasta = htmlspecialchars(strip_tags($this->fecha_hasta));
        $this->monto = htmlspecialchars(strip_tags($this->monto));
        $this->notas = htmlspecialchars(strip_tags($this->notas));
        $this->id_reporte = htmlspecialchars(strip_tags($this->id_reporte));
        
        // Bind valores
        $stmt->bindParam(":id_cliente", $this->id_cliente);
        $stmt->bindParam(":fecha_transaccion", $this->fecha_transaccion);
        $stmt->bindParam(":metodo_pago", $this->metodo_pago);
        $stmt->bindParam(":numero_referencia", $this->numero_referencia);
        $stmt->bindParam(":fecha_desde", $this->fecha_desde);
        $stmt->bindParam(":fecha_hasta", $this->fecha_hasta);
        $stmt->bindParam(":monto", $this->monto);
        $stmt->bindParam(":notas", $this->notas);
        $stmt->bindParam(":id_reporte", $this->id_reporte);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Eliminar reporte
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_reporte = :id_reporte";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_reporte', $this->id_reporte);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Obtener clientes disponibles para el usuario
    public function getAvailableClients($user_id = null, $is_admin = false) {
        $query = "SELECT DISTINCT c.id_cliente, c.nombre_cliente, p.nombre_plan, e.nombre_empresa
                  FROM clientes c
                  LEFT JOIN planes p ON c.id_plan = p.id_plan
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa";
        
        if (!$is_admin && $user_id) {
            $query .= " INNER JOIN relaciones r ON c.id_cliente = r.id_cliente 
                        WHERE r.id_usuario = :user_id AND c.estado = 'Activo'";
        } else {
            $query .= " WHERE c.estado = 'Activo'";
        }
        
        $query .= " ORDER BY c.nombre_cliente ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$is_admin && $user_id) {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Contar reportes
    public function countReports($user_id = null, $is_admin = false) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " r
                  LEFT JOIN clientes c ON r.id_cliente = c.id_cliente";
        
        if (!$is_admin && $user_id) {
            $query .= " INNER JOIN relaciones rel ON c.id_cliente = rel.id_cliente 
                        WHERE rel.id_usuario = :user_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if (!$is_admin && $user_id) {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Buscar reportes
    public function search($search_term, $user_id = null, $is_admin = false) {
        $query = "SELECT 
                    r.id_reporte,
                    r.id_cliente,
                    r.fecha_transaccion,
                    r.metodo_pago,
                    r.numero_referencia,
                    r.fecha_desde,
                    r.fecha_hasta,
                    r.monto,
                    r.notas,
                    r.fecha_creacion,
                    c.nombre_cliente,
                    p.nombre_plan,
                    e.nombre_empresa,
                    u.nombre_usuario as nombre_usuario_creador
                  FROM " . $this->table_name . " r
                  LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                  LEFT JOIN planes p ON c.id_plan = p.id_plan
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  LEFT JOIN usuarios u ON r.id_usuario_creador = u.id_usuario
                  WHERE (c.nombre_cliente LIKE :search_term 
                         OR r.metodo_pago LIKE :search_term 
                         OR r.numero_referencia LIKE :search_term 
                         OR r.notas LIKE :search_term)";
        
        if (!$is_admin && $user_id) {
            $query .= " AND EXISTS (
                          SELECT 1 FROM relaciones rel 
                          WHERE rel.id_cliente = c.id_cliente 
                          AND rel.id_usuario = :user_id
                        )";
        }
        
        $query .= " ORDER BY r.fecha_transaccion DESC, r.fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        $search_term = "%{$search_term}%";
        $stmt->bindParam(':search_term', $search_term);
        
        if (!$is_admin && $user_id) {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->execute();
        
        return $stmt;
    }
}
?>
