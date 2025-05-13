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
}
?>
