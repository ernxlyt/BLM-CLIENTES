<?php
class Empresa {
    private $conn;
    private $table_name = "empresas";

    public $id_empresa;
    public $nombre_empresa;
    public $rubro;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre_empresa = :nombre_empresa, 
                      rubro = :rubro";

        $stmt = $this->conn->prepare($query);

        $this->nombre_empresa = htmlspecialchars(strip_tags($this->nombre_empresa));
        $this->rubro = htmlspecialchars(strip_tags($this->rubro));

        $stmt->bindParam(":nombre_empresa", $this->nombre_empresa);
        $stmt->bindParam(":rubro", $this->rubro);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT id_empresa, nombre_empresa, rubro 
                  FROM " . $this->table_name . " 
                  ORDER BY nombre_empresa ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id_empresa, nombre_empresa, rubro 
                  FROM " . $this->table_name . " 
                  WHERE id_empresa = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_empresa);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_empresa = $row['id_empresa'];
            $this->nombre_empresa = $row['nombre_empresa'];
            $this->rubro = $row['rubro'];
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_empresa = :nombre_empresa, 
                      rubro = :rubro
                  WHERE id_empresa = :id_empresa";

        $stmt = $this->conn->prepare($query);

        $this->nombre_empresa = htmlspecialchars(strip_tags($this->nombre_empresa));
        $this->rubro = htmlspecialchars(strip_tags($this->rubro));
        $this->id_empresa = htmlspecialchars(strip_tags($this->id_empresa));

        $stmt->bindParam(":nombre_empresa", $this->nombre_empresa);
        $stmt->bindParam(":rubro", $this->rubro);
        $stmt->bindParam(":id_empresa", $this->id_empresa);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_empresa = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_empresa);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function getClients() {
        $query = "SELECT c.id_cliente, c.nombre_cliente 
                  FROM clientes c
                  WHERE c.id_empresa = ?
                  ORDER BY c.nombre_cliente ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_empresa);
        $stmt->execute();

        return $stmt;
    }
}
?>
