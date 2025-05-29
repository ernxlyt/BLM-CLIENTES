<?php
// Actualización del modelo Report con los nombres reales de la BD

class Report {
    private $conn;
    private $table_name = "reportes_transacciones";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para leer reportes con filtros aplicados
    public function readWithFilters($userId, $isAdmin, $search = '', $dateFrom = '', $dateTo = '') {
        $query = "SELECT rt.*, 
                         c.nombre_cliente,
                         p.nombre_plan,
                         e.nombre_empresa,
                         u.nombre_usuario
                  FROM " . $this->table_name . " rt
                  LEFT JOIN clientes c ON rt.id_cliente = c.id_cliente
                  LEFT JOIN planes p ON c.id_plan = p.id_plan  
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  LEFT JOIN usuarios u ON rt.id_usuario_creador = u.id_usuario
                  WHERE 1=1";
        
        $params = [];
        
        // Filtro por usuario si no es admin
        if (!$isAdmin) {
            $query .= " AND rt.id_usuario_creador = :user_id";
            $params[':user_id'] = $userId;
        }
        
        // Filtro de búsqueda
        if (!empty($search)) {
            $query .= " AND (c.nombre_cliente LIKE :search 
                        OR rt.metodo_pago LIKE :search 
                        OR rt.numero_referencia LIKE :search 
                        OR rt.notas LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        // Filtro de fecha desde
        if (!empty($dateFrom)) {
            $query .= " AND rt.fecha_transaccion >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        // Filtro de fecha hasta
        if (!empty($dateTo)) {
            $query .= " AND rt.fecha_transaccion <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $query .= " ORDER BY rt.fecha_transaccion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt;
    }

    // Método para leer todos los reportes
    public function read($userId, $isAdmin) {
        $query = "SELECT rt.*, 
                         c.nombre_cliente,
                         p.nombre_plan,
                         e.nombre_empresa,
                         u.nombre_usuario
                  FROM " . $this->table_name . " rt
                  LEFT JOIN clientes c ON rt.id_cliente = c.id_cliente
                  LEFT JOIN planes p ON c.id_plan = p.id_plan  
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  LEFT JOIN usuarios u ON rt.id_usuario_creador = u.id_usuario";
        
        if (!$isAdmin) {
            $query .= " WHERE rt.id_usuario_creador = :user_id";
        }
        
        $query .= " ORDER BY rt.fecha_transaccion DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!$isAdmin) {
            $stmt->bindParam(':user_id', $userId);
        }
        
        $stmt->execute();
        return $stmt;
    }

    // Método para contar reportes
    public function countReports($userId, $isAdmin) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        if (!$isAdmin) {
            $query .= " WHERE id_usuario_creador = :user_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if (!$isAdmin) {
            $stmt->bindParam(':user_id', $userId);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Método para crear un nuevo reporte
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_cliente, fecha_transaccion, metodo_pago, numero_referencia, 
                   fecha_desde, fecha_hasta, monto, notas, id_usuario_creador) 
                  VALUES 
                  (:id_cliente, :fecha_transaccion, :metodo_pago, :numero_referencia, 
                   :fecha_desde, :fecha_hasta, :monto, :notas, :id_usuario_creador)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id_cliente', $data['id_cliente']);
        $stmt->bindParam(':fecha_transaccion', $data['fecha_transaccion']);
        $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
        $stmt->bindParam(':numero_referencia', $data['numero_referencia']);
        $stmt->bindParam(':fecha_desde', $data['fecha_desde']);
        $stmt->bindParam(':fecha_hasta', $data['fecha_hasta']);
        $stmt->bindParam(':monto', $data['monto']);
        $stmt->bindParam(':notas', $data['notas']);
        $stmt->bindParam(':id_usuario_creador', $data['id_usuario_creador']);
        
        return $stmt->execute();
    }

    // Método para obtener un reporte por ID
    public function readOne($user_id = null, $is_admin = false) {
        $query = "SELECT 
                rt.*, 
                c.nombre_cliente,
                p.nombre_plan,
                e.nombre_empresa,
                u.nombre_usuario as nombre_usuario_creador
              FROM " . $this->table_name . " rt
              LEFT JOIN clientes c ON rt.id_cliente = c.id_cliente
              LEFT JOIN planes p ON c.id_plan = p.id_plan  
              LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
              LEFT JOIN usuarios u ON rt.id_usuario_creador = u.id_usuario
              WHERE rt.id_reporte = :id_reporte";
    
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

    // Método para actualizar un reporte
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET id_cliente = :id_cliente,
                      fecha_transaccion = :fecha_transaccion,
                      metodo_pago = :metodo_pago,
                      numero_referencia = :numero_referencia,
                      fecha_desde = :fecha_desde,
                      fecha_hasta = :fecha_hasta,
                      monto = :monto,
                      notas = :notas
                  WHERE id_reporte = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':id_cliente', $data['id_cliente']);
        $stmt->bindParam(':fecha_transaccion', $data['fecha_transaccion']);
        $stmt->bindParam(':metodo_pago', $data['metodo_pago']);
        $stmt->bindParam(':numero_referencia', $data['numero_referencia']);
        $stmt->bindParam(':fecha_desde', $data['fecha_desde']);
        $stmt->bindParam(':fecha_hasta', $data['fecha_hasta']);
        $stmt->bindParam(':monto', $data['monto']);
        $stmt->bindParam(':notas', $data['notas']);
        
        return $stmt->execute();
    }

    // Método para eliminar un reporte
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_reporte = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    // Método para obtener clientes disponibles para reportes
    public function getAvailableClients($userId, $isAdmin) {
        if ($isAdmin) {
            // Si es admin, puede ver todos los clientes
            $query = "SELECT c.id_cliente, c.nombre_cliente, p.nombre_plan, e.nombre_empresa
                      FROM clientes c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan  
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      ORDER BY c.nombre_cliente ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } else {
            // Si no es admin, solo puede ver clientes relacionados
            $query = "SELECT DISTINCT c.id_cliente, c.nombre_cliente, p.nombre_plan, e.nombre_empresa
                      FROM clientes c
                      LEFT JOIN planes p ON c.id_plan = p.id_plan  
                      LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                      INNER JOIN relaciones r ON c.id_cliente = r.id_cliente
                      WHERE r.id_usuario = :user_id
                      ORDER BY c.nombre_cliente ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
