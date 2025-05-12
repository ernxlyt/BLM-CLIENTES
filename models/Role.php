<?php
class Role {
    private $conn;
    private $table_name = "roles";

    public $id_rol;
    public $nombre_rol;
    public $descripcion_rol;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre_rol = :nombre_rol, 
                      descripcion_rol = :descripcion_rol";

        $stmt = $this->conn->prepare($query);

        $this->nombre_rol = htmlspecialchars(strip_tags($this->nombre_rol));
        $this->descripcion_rol = htmlspecialchars(strip_tags($this->descripcion_rol));

        $stmt->bindParam(":nombre_rol", $this->nombre_rol);
        $stmt->bindParam(":descripcion_rol", $this->descripcion_rol);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT id_rol, nombre_rol, descripcion_rol 
                  FROM " . $this->table_name . " 
                  ORDER BY id_rol ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id_rol, nombre_rol, descripcion_rol 
                  FROM " . $this->table_name . " 
                  WHERE id_rol = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_rol);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_rol = $row['id_rol'];
            $this->nombre_rol = $row['nombre_rol'];
            $this->descripcion_rol = $row['descripcion_rol'];
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_rol = :nombre_rol, 
                      descripcion_rol = :descripcion_rol
                  WHERE id_rol = :id_rol";

        $stmt = $this->conn->prepare($query);

        $this->nombre_rol = htmlspecialchars(strip_tags($this->nombre_rol));
        $this->descripcion_rol = htmlspecialchars(strip_tags($this->descripcion_rol));
        $this->id_rol = htmlspecialchars(strip_tags($this->id_rol));

        $stmt->bindParam(":nombre_rol", $this->nombre_rol);
        $stmt->bindParam(":descripcion_rol", $this->descripcion_rol);
        $stmt->bindParam(":id_rol", $this->id_rol);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_rol = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_rol);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>
