<?php
class SocialNetwork {
    private $conn;
    private $table_name = "redes_sociales";

    public $id_red;
    public $id_cliente;
    public $tipo_red;
    public $nombre_red;
    public $usuario_red;
    public $contrasena_red;
    public $url_red;
    public $notas;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create social network
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_cliente = :id_cliente, 
                      tipo_red = :tipo_red, 
                      nombre_red = :nombre_red, 
                      usuario_red = :usuario_red, 
                      contrasena_red = :contrasena_red,
                      url_red = :url_red,
                      notas = :notas";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind values
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));
        $this->tipo_red = htmlspecialchars(strip_tags($this->tipo_red));
        $this->nombre_red = htmlspecialchars(strip_tags($this->nombre_red));
        $this->usuario_red = htmlspecialchars(strip_tags($this->usuario_red));
        $this->contrasena_red = htmlspecialchars(strip_tags($this->contrasena_red));
        $this->url_red = htmlspecialchars(strip_tags($this->url_red));
        $this->notas = htmlspecialchars(strip_tags($this->notas));

        $stmt->bindParam(":id_cliente", $this->id_cliente);
        $stmt->bindParam(":tipo_red", $this->tipo_red);
        $stmt->bindParam(":nombre_red", $this->nombre_red);
        $stmt->bindParam(":usuario_red", $this->usuario_red);
        $stmt->bindParam(":contrasena_red", $this->contrasena_red);
        $stmt->bindParam(":url_red", $this->url_red);
        $stmt->bindParam(":notas", $this->notas);

        if($stmt->execute()) {
            $this->id_red = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }
    
    // Añade este método a tu clase SocialNetwork en models/SocialNetwork.php
public function read($user_id, $is_admin) {
    if($is_admin) {
        // Los administradores pueden ver todas las redes sociales
        $query = "SELECT r.*, c.nombre_cliente 
                  FROM " . $this->table_name . " r
                  LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                  ORDER BY c.nombre_cliente ASC, r.nombre_red ASC";
        $stmt = $this->conn->prepare($query);
    } else {
        // Los usuarios normales solo ven las redes sociales de los clientes a los que tienen acceso
        $query = "SELECT r.*, c.nombre_cliente 
                  FROM " . $this->table_name . " r
                  LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                  JOIN relaciones rel ON c.id_cliente = rel.id_cliente
                  WHERE rel.id_usuario = ?
                  ORDER BY c.nombre_cliente ASC, r.nombre_red ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
    }
    
    $stmt->execute();
    return $stmt;
}

    // Read all social networks for a client
    public function readByClient($id_cliente) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_cliente = ?
                  ORDER BY nombre_red ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_cliente);
        $stmt->execute();

        return $stmt;
    }

    // Read one social network
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_red = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_red);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_red = $row['id_red'];
            $this->id_cliente = $row['id_cliente'];
            $this->tipo_red = $row['tipo_red'];
            $this->nombre_red = $row['nombre_red'];
            $this->usuario_red = $row['usuario_red'];
            $this->contrasena_red = $row['contrasena_red'];
            $this->url_red = $row['url_red'];
            $this->notas = $row['notas'];
            $this->fecha_creacion = $row['fecha_creacion'];
            return true;
        }

        return false;
    }

    // Update social network
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET tipo_red = :tipo_red, 
                      nombre_red = :nombre_red, 
                      usuario_red = :usuario_red, 
                      contrasena_red = :contrasena_red,
                      url_red = :url_red,
                      notas = :notas
                  WHERE id_red = :id_red";

        $stmt = $this->conn->prepare($query);

        // Sanitize and bind values
        $this->tipo_red = htmlspecialchars(strip_tags($this->tipo_red));
        $this->nombre_red = htmlspecialchars(strip_tags($this->nombre_red));
        $this->usuario_red = htmlspecialchars(strip_tags($this->usuario_red));
        $this->contrasena_red = htmlspecialchars(strip_tags($this->contrasena_red));
        $this->url_red = htmlspecialchars(strip_tags($this->url_red));
        $this->notas = htmlspecialchars(strip_tags($this->notas));
        $this->id_red = htmlspecialchars(strip_tags($this->id_red));

        $stmt->bindParam(":tipo_red", $this->tipo_red);
        $stmt->bindParam(":nombre_red", $this->nombre_red);
        $stmt->bindParam(":usuario_red", $this->usuario_red);
        $stmt->bindParam(":contrasena_red", $this->contrasena_red);
        $stmt->bindParam(":url_red", $this->url_red);
        $stmt->bindParam(":notas", $this->notas);
        $stmt->bindParam(":id_red", $this->id_red);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Delete social network
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_red = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_red);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read all social networks
    public function readAll() {
        $query = "SELECT r.*, c.nombre_cliente 
                  FROM " . $this->table_name . " r
                  LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                  ORDER BY c.nombre_cliente ASC, r.nombre_red ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Modificar el método getNetworkTypes para obtener tipos de la base de datos y permitir agregar nuevos
    public function getNetworkTypes() {
        // Primero intentamos obtener los tipos de la base de datos
        $query = "SELECT DISTINCT tipo_red, 
                  CASE 
                    WHEN tipo_red = 'Facebook' THEN 'fa-facebook' 
                    WHEN tipo_red = 'Instagram' THEN 'fa-instagram' 
                    WHEN tipo_red = 'YouTube' THEN 'fa-youtube' 
                    WHEN tipo_red = 'Twitter' THEN 'fa-twitter' 
                    WHEN tipo_red = 'LinkedIn' THEN 'fa-linkedin' 
                    WHEN tipo_red = 'TikTok' THEN 'fa-tiktok' 
                    WHEN tipo_red = 'Pinterest' THEN 'fa-pinterest' 
                    WHEN tipo_red = 'Snapchat' THEN 'fa-snapchat' 
                    WHEN tipo_red = 'WhatsApp' THEN 'fa-whatsapp' 
                    WHEN tipo_red = 'Telegram' THEN 'fa-telegram' 
                    ELSE 'fa-globe' 
                  END as icono,
                  CASE 
                    WHEN tipo_red = 'Facebook' THEN '#4267B2' 
                    WHEN tipo_red = 'Instagram' THEN '#E1306C' 
                    WHEN tipo_red = 'YouTube' THEN '#FF0000' 
                    WHEN tipo_red = 'Twitter' THEN '#1DA1F2' 
                    WHEN tipo_red = 'LinkedIn' THEN '#0077B5' 
                    WHEN tipo_red = 'TikTok' THEN '#000000' 
                    WHEN tipo_red = 'Pinterest' THEN '#E60023' 
                    WHEN tipo_red = 'Snapchat' THEN '#FFFC00' 
                    WHEN tipo_red = 'WhatsApp' THEN '#25D366' 
                    WHEN tipo_red = 'Telegram' THEN '#0088cc' 
                    ELSE '#10b981' 
                  END as color
                  FROM " . $this->table_name . "
                  WHERE tipo_red IS NOT NULL AND tipo_red != ''
                  GROUP BY tipo_red
                  ORDER BY tipo_red ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $types = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $types[] = [
                'nombre_tipo' => $row['tipo_red'],
                'icono' => $row['icono'],
                'color' => $row['color']
            ];
        }
        
        // Agregar los tipos predeterminados si no existen en la base de datos
        $default_types = [
            ['nombre_tipo' => 'Facebook', 'icono' => 'fa-facebook', 'color' => '#4267B2'],
            ['nombre_tipo' => 'Instagram', 'icono' => 'fa-instagram', 'color' => '#E1306C'],
            ['nombre_tipo' => 'YouTube', 'icono' => 'fa-youtube', 'color' => '#FF0000'],
            ['nombre_tipo' => 'Twitter', 'icono' => 'fa-twitter', 'color' => '#1DA1F2'],
            ['nombre_tipo' => 'LinkedIn', 'icono' => 'fa-linkedin', 'color' => '#0077B5'],
            ['nombre_tipo' => 'TikTok', 'icono' => 'fa-tiktok', 'color' => '#000000']
        ];
        
        // Verificar si cada tipo predeterminado ya existe en los resultados
        foreach ($default_types as $default_type) {
            $exists = false;
            foreach ($types as $type) {
                if ($type['nombre_tipo'] === $default_type['nombre_tipo']) {
                    $exists = true;
                    break;
                }
            }
            
            // Si no existe, agregarlo a la lista
            if (!$exists) {
                $types[] = $default_type;
            }
        }
        
        // Ordenar por nombre
        usort($types, function($a, $b) {
            return strcmp($a['nombre_tipo'], $b['nombre_tipo']);
        });
        
        return $types;
    }

    // Agregar método para obtener información de un tipo de red social
    public function getNetworkTypeInfo($tipo_red) {
        $icons = [
            'Facebook' => ['icono' => 'fa-facebook', 'color' => '#4267B2'],
            'Instagram' => ['icono' => 'fa-instagram', 'color' => '#E1306C'],
            'YouTube' => ['icono' => 'fa-youtube', 'color' => '#FF0000'],
            'Twitter' => ['icono' => 'fa-twitter', 'color' => '#1DA1F2'],
            'LinkedIn' => ['icono' => 'fa-linkedin', 'color' => '#0077B5'],
            'TikTok' => ['icono' => 'fa-tiktok', 'color' => '#000000'],
            'Pinterest' => ['icono' => 'fa-pinterest', 'color' => '#E60023'],
            'Snapchat' => ['icono' => 'fa-snapchat', 'color' => '#FFFC00'],
            'WhatsApp' => ['icono' => 'fa-whatsapp', 'color' => '#25D366'],
            'Telegram' => ['icono' => 'fa-telegram', 'color' => '#0088cc']
        ];
        
        return isset($icons[$tipo_red]) ? $icons[$tipo_red] : ['icono' => 'fa-globe', 'color' => '#10b981'];
    }
}
?>
