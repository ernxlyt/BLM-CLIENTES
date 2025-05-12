<?php
class Relation {
    private $conn;
    private $table_name = "relaciones";

    public $id_relacion;
    public $id_usuario;
    public $id_cliente;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_usuario = :id_usuario, 
                      id_cliente = :id_cliente";

        $stmt = $this->conn->prepare($query);

        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":id_cliente", $this->id_cliente);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function readByUser() {
        $query = "SELECT r.id_relacion, r.id_cliente, c.nombre_cliente 
                  FROM " . $this->table_name . " r
                  JOIN clientes c ON r.id_cliente = c.id_cliente
                  WHERE r.id_usuario = ?
                  ORDER BY c.nombre_cliente ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->execute();

        return $stmt;
    }

    public function readByClient() {
        $query = "SELECT r.id_relacion, r.id_usuario, u.nombre_usuario 
                  FROM " . $this->table_name . " r
                  JOIN usuarios u ON r.id_usuario = u.id_usuario
                  WHERE r.id_cliente = ?
                  ORDER BY u.nombre_usuario ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_cliente);
        $stmt->execute();

        return $stmt;
    }

  
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_relacion = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_relacion);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function exists() {
        $query = "SELECT id_relacion 
                  FROM " . $this->table_name . " 
                  WHERE id_usuario = ? AND id_cliente = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->bindParam(2, $this->id_cliente);
        $stmt->execute();

        return ($stmt->rowCount() > 0);
    }
}
?>
