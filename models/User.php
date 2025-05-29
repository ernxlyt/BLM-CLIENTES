<?php
class User {
    private $conn;
    private $table_name = "usuarios";

    public $id_usuario;
    public $nombre_usuario;
    public $correo_usuario;
    public $contrasena;
    public $id_rol;
    public $nombre_rol;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear un nuevo usuario (almacena la contraseña en texto plano)
    public function create() {
        if (empty($this->contrasena)) {
            return false; // Validación: Contraseña obligatoria
        }

        // Verificar si el correo ya está registrado en la BD
        $queryCheck = "SELECT id_usuario FROM " . $this->table_name . " WHERE correo_usuario = :correo_usuario";
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(":correo_usuario", $this->correo_usuario);
        $stmtCheck->execute();

        if ($stmtCheck->rowCount() > 0) {
            echo "<script>alert('Error: El correo ya está registrado. Por favor, usa otro.');</script>";
            return false;
        }

        // Si el correo no existe, proceder con la inserción
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre_usuario = :nombre_usuario, 
                      correo_usuario = :correo_usuario, 
                      contrasena = :contrasena, 
                      id_rol = :id_rol";

        $stmt = $this->conn->prepare($query);

        $this->nombre_usuario = htmlspecialchars(strip_tags($this->nombre_usuario));
        $this->correo_usuario = htmlspecialchars(strip_tags($this->correo_usuario));
        $this->contrasena = htmlspecialchars(strip_tags($this->contrasena));
        $this->id_rol = htmlspecialchars(strip_tags($this->id_rol));

        $stmt->bindParam(":nombre_usuario", $this->nombre_usuario);
        $stmt->bindParam(":correo_usuario", $this->correo_usuario);
        $stmt->bindParam(":contrasena", $this->contrasena);
        $stmt->bindParam(":id_rol", $this->id_rol);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function isAdmin() {
        return $this->id_rol == 1; // Ajusta el número según el ID de administrador en tu base de datos
    }

    // Inicio de sesión (comparación directa sin hashing)
    public function login() {
        $query = "SELECT id_usuario, nombre_usuario, correo_usuario, contrasena, id_rol 
                  FROM " . $this->table_name . " 
                  WHERE correo_usuario = :usuario OR nombre_usuario = :usuario
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario", $this->correo_usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Comparación directa sin password_verify
            if ($this->contrasena === $row['contrasena']) {
                $this->id_usuario = $row['id_usuario'];
                $this->nombre_usuario = $row['nombre_usuario'];
                $this->correo_usuario = $row['correo_usuario'];
                $this->id_rol = $row['id_rol'];

                return true;
            }
        }

        return false;
    }

    // Leer todos los usuarios
    public function read() {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.correo_usuario, r.nombre_rol 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.id_rol = r.id_rol
                  ORDER BY u.id_usuario DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Leer un usuario específico
    public function readOne() {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.correo_usuario, u.contrasena, u.id_rol, r.nombre_rol 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.id_rol = r.id_rol
                  WHERE u.id_usuario = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id_usuario = $row['id_usuario'];
            $this->nombre_usuario = $row['nombre_usuario'];
            $this->correo_usuario = $row['correo_usuario'];
            $this->contrasena = $row['contrasena']; // Aquí asignamos correctamente la contraseña
            $this->id_rol = $row['id_rol'];
            $this->nombre_rol = $row['nombre_rol'];

            return true;
        }

        return false;
    }

    // Actualizar datos del usuario
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_usuario = :nombre_usuario, 
                      correo_usuario = :correo_usuario, 
                      id_rol = :id_rol
                  WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        $this->nombre_usuario = htmlspecialchars(strip_tags($this->nombre_usuario));
        $this->correo_usuario = htmlspecialchars(strip_tags($this->correo_usuario));
        $this->id_rol = htmlspecialchars(strip_tags($this->id_rol));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));

        $stmt->bindParam(":nombre_usuario", $this->nombre_usuario);
        $stmt->bindParam(":correo_usuario", $this->correo_usuario);
        $stmt->bindParam(":id_rol", $this->id_rol);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Actualizar contraseña (sin hashing)
    public function updatePassword() {
        if (empty($this->contrasena)) {
            return false; // Validación: Contraseña obligatoria para actualizar
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET contrasena = :contrasena
                  WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":contrasena", $this->contrasena);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Eliminar un usuario
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_usuario = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // ========== NUEVOS MÉTODOS PARA ASIGNACIONES CON SERVICIOS ==========

    /**
     * Obtiene todos los clientes asignados a este usuario con sus servicios
     */
    public function getAssignedClients() {
        $query = "SELECT 
                    r.id_relacion,
                    c.id_cliente,
                    c.nombre_cliente,
                    c.estado as cliente_estado,
                    r.tipo_servicio,
                    r.fecha_asignacion,
                    r.notas,
                    e.nombre_empresa,
                    p.nombre_plan
                  FROM relaciones r
                  INNER JOIN clientes c ON r.id_cliente = c.id_cliente
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  LEFT JOIN planes p ON c.id_plan = p.id_plan
                  WHERE r.id_usuario = ?
                  ORDER BY c.nombre_cliente, r.tipo_servicio";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Asigna este usuario a un cliente con un servicio específico
     */
    public function assignToClient($id_cliente, $tipo_servicio, $notas = '') {
        // Verificar si ya existe una asignación igual
        $query_check = "SELECT id_relacion FROM relaciones 
                        WHERE id_usuario = ? AND id_cliente = ? AND tipo_servicio = ?";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(1, $this->id_usuario);
        $stmt_check->bindParam(2, $id_cliente);
        $stmt_check->bindParam(3, $tipo_servicio);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            // Ya existe, actualizar notas
            $row = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $id_relacion = $row['id_relacion'];
            
            $query_update = "UPDATE relaciones SET notas = ? WHERE id_relacion = ?";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(1, $notas);
            $stmt_update->bindParam(2, $id_relacion);
            return $stmt_update->execute();
        } else {
            // No existe, crear nueva asignación
            $query_insert = "INSERT INTO relaciones (id_usuario, id_cliente, tipo_servicio, notas) 
                            VALUES (?, ?, ?, ?)";
            $stmt_insert = $this->conn->prepare($query_insert);
            $stmt_insert->bindParam(1, $this->id_usuario);
            $stmt_insert->bindParam(2, $id_cliente);
            $stmt_insert->bindParam(3, $tipo_servicio);
            $stmt_insert->bindParam(4, $notas);
            return $stmt_insert->execute();
        }
    }

    /**
     * Actualiza una asignación existente
     */
    public function updateAssignment($id_relacion, $id_cliente, $tipo_servicio, $notas = '') {
        $query = "UPDATE relaciones 
                  SET id_cliente = ?, tipo_servicio = ?, notas = ? 
                  WHERE id_relacion = ? AND id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_cliente);
        $stmt->bindParam(2, $tipo_servicio);
        $stmt->bindParam(3, $notas);
        $stmt->bindParam(4, $id_relacion);
        $stmt->bindParam(5, $this->id_usuario);
        return $stmt->execute();
    }

    /**
     * Elimina una asignación específica
     */
    public function removeAssignment($id_relacion) {
        $query = "DELETE FROM relaciones WHERE id_relacion = ? AND id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_relacion);
        $stmt->bindParam(2, $this->id_usuario);
        return $stmt->execute();
    }

    /**
     * Obtiene todos los clientes disponibles para asignar
     */
    public function getAvailableClients() {
        $query = "SELECT c.id_cliente, c.nombre_cliente, e.nombre_empresa, c.estado
                  FROM clientes c
                  LEFT JOIN empresas e ON c.id_empresa = e.id_empresa
                  WHERE c.estado = 'Activo'
                  ORDER BY c.nombre_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tipos de servicio existentes para autocompletado
     */
    public function getServiceTypes($term = '') {
        $query = "SELECT DISTINCT tipo_servicio FROM relaciones 
                  WHERE tipo_servicio IS NOT NULL AND tipo_servicio != ''";
        
        if (!empty($term)) {
            $term = "%$term%";
            $query .= " AND tipo_servicio LIKE ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $term);
        } else {
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tipos = [];
        foreach ($result as $row) {
            $tipos[] = $row['tipo_servicio'];
        }
        
        return $tipos;
    }

    /**
     * Obtiene estadísticas de asignaciones del usuario
     */
    public function getAssignmentStats() {
        $query = "SELECT 
                    COUNT(DISTINCT r.id_cliente) as total_clientes,
                    COUNT(r.id_relacion) as total_servicios,
                    COUNT(DISTINCT r.tipo_servicio) as tipos_servicio_unicos
                  FROM relaciones r
                  INNER JOIN clientes c ON r.id_cliente = c.id_cliente
                  WHERE r.id_usuario = ? AND c.estado = 'Activo'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>