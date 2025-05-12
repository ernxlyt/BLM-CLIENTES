<?php
class Plan {
    private $conn;
    private $table_name = "planes";

    public $id_plan;
    public $nombre_plan;
    public $descripcion_plan;
    public $precio;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre_plan = :nombre_plan, 
                      descripcion_plan = :descripcion_plan, 
                      precio = :precio";

        $stmt = $this->conn->prepare($query);

        $this->nombre_plan = htmlspecialchars(strip_tags($this->nombre_plan));
        $this->descripcion_plan = htmlspecialchars(strip_tags($this->descripcion_plan));
        $this->precio = htmlspecialchars(strip_tags($this->precio));

        $stmt->bindParam(":nombre_plan", $this->nombre_plan);
        $stmt->bindParam(":descripcion_plan", $this->descripcion_plan);
        $stmt->bindParam(":precio", $this->precio);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT id_plan, nombre_plan, descripcion_plan, precio 
                  FROM " . $this->table_name . " 
                  ORDER BY id_plan ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id_plan, nombre_plan, descripcion_plan, precio 
                  FROM " . $this->table_name . " 
                  WHERE id_plan = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_plan);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_plan = $row['id_plan'];
            $this->nombre_plan = $row['nombre_plan'];
            $this->descripcion_plan = $row['descripcion_plan'];
            $this->precio = $row['precio'];
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_plan = :nombre_plan, 
                      descripcion_plan = :descripcion_plan, 
                      precio = :precio
                  WHERE id_plan = :id_plan";

        $stmt = $this->conn->prepare($query);

        $this->nombre_plan = htmlspecialchars(strip_tags($this->nombre_plan));
        $this->descripcion_plan = htmlspecialchars(strip_tags($this->descripcion_plan));
        $this->precio = htmlspecialchars(strip_tags($this->precio));
        $this->id_plan = htmlspecialchars(strip_tags($this->id_plan));

        $stmt->bindParam(":nombre_plan", $this->nombre_plan);
        $stmt->bindParam(":descripcion_plan", $this->descripcion_plan);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":id_plan", $this->id_plan);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_plan = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_plan);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>
