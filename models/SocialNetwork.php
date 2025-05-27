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

    // Create social network with validation
    public function create() {
        // Verificar que el cliente existe antes de crear la red social
        if (!$this->clientExists()) {
            throw new Exception("Error: El cliente con ID {$this->id_cliente} no existe en la base de datos.");
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (id_cliente, tipo_red, nombre_red, usuario_red, contrasena_red, url_red, notas) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);

        // Sanitize and validate values
        $this->id_cliente = (int)$this->id_cliente;
        $this->tipo_red = htmlspecialchars(strip_tags($this->tipo_red));
        $this->nombre_red = htmlspecialchars(strip_tags($this->nombre_red));
        $this->usuario_red = htmlspecialchars(strip_tags($this->usuario_red));
        $this->contrasena_red = htmlspecialchars(strip_tags($this->contrasena_red));
        $this->url_red = htmlspecialchars(strip_tags($this->url_red));
        $this->notas = htmlspecialchars(strip_tags($this->notas));

        // Bind parameters using positional placeholders
        $stmt->bindParam(1, $this->id_cliente, PDO::PARAM_INT);
        $stmt->bindParam(2, $this->tipo_red);
        $stmt->bindParam(3, $this->nombre_red);
        $stmt->bindParam(4, $this->usuario_red);
        $stmt->bindParam(5, $this->contrasena_red);
        $stmt->bindParam(6, $this->url_red);
        $stmt->bindParam(7, $this->notas);

        try {
            if($stmt->execute()) {
                $this->id_red = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            // Log del error para debugging
            error_log("Error en SocialNetwork->create(): " . $e->getMessage());
            
            // Si es error de clave externa, dar mensaje más claro
            if ($e->getCode() == 23000) {
                throw new Exception("Error: No se puede crear la red social. Verifique que el cliente existe.");
            }
            
            throw new Exception("Error al crear la red social: " . $e->getMessage());
        }

        return false;
    }

    // Método para verificar si el cliente existe
    private function clientExists() {
        if (empty($this->id_cliente) || !is_numeric($this->id_cliente)) {
            return false;
        }

        $query = "SELECT id_cliente FROM clientes WHERE id_cliente = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_cliente, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Método para crear red social con validación completa
    public function createWithValidation($client_id) {
        // Asignar el ID del cliente
        $this->id_cliente = $client_id;
        
        // Validar datos requeridos
        if (empty($this->tipo_red)) {
            throw new Exception("El tipo de red social es requerido");
        }
        
        if (empty($this->nombre_red)) {
            throw new Exception("El nombre de la red social es requerido");
        }
        
        // Crear la red social
        return $this->create();
    }
    
    // Read method with user permissions
    public function read($user_id, $is_admin) {
        if($is_admin) {
            $query = "SELECT r.*, c.nombre_cliente 
                      FROM " . $this->table_name . " r
                      LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                      ORDER BY c.nombre_cliente ASC, r.nombre_red ASC";
            $stmt = $this->conn->prepare($query);
        } else {
            $query = "SELECT r.*, c.nombre_cliente 
                      FROM " . $this->table_name . " r
                      LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
                      JOIN relaciones rel ON c.id_cliente = rel.id_cliente
                      WHERE rel.id_usuario = ?
                      ORDER BY c.nombre_cliente ASC, r.nombre_red ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
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
        $stmt->bindParam(1, $id_cliente, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Read one social network
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_red = ?
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_red, PDO::PARAM_INT);
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
                  SET tipo_red = ?, 
                      nombre_red = ?, 
                      usuario_red = ?, 
                      contrasena_red = ?,
                      url_red = ?,
                      notas = ?
                  WHERE id_red = ?";

        $stmt = $this->conn->prepare($query);

        // Sanitize values
        $this->tipo_red = htmlspecialchars(strip_tags($this->tipo_red));
        $this->nombre_red = htmlspecialchars(strip_tags($this->nombre_red));
        $this->usuario_red = htmlspecialchars(strip_tags($this->usuario_red));
        $this->contrasena_red = htmlspecialchars(strip_tags($this->contrasena_red));
        $this->url_red = htmlspecialchars(strip_tags($this->url_red));
        $this->notas = htmlspecialchars(strip_tags($this->notas));

        $stmt->bindParam(1, $this->tipo_red);
        $stmt->bindParam(2, $this->nombre_red);
        $stmt->bindParam(3, $this->usuario_red);
        $stmt->bindParam(4, $this->contrasena_red);
        $stmt->bindParam(5, $this->url_red);
        $stmt->bindParam(6, $this->notas);
        $stmt->bindParam(7, $this->id_red, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Delete social network
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_red = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_red, PDO::PARAM_INT);
        return $stmt->execute();
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

    // Get network types
    public function getNetworkTypes() {
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
        
        // Default types if none exist in database
        $default_types = [
            ['nombre_tipo' => 'Facebook', 'icono' => 'fa-facebook', 'color' => '#4267B2'],
            ['nombre_tipo' => 'Instagram', 'icono' => 'fa-instagram', 'color' => '#E1306C'],
            ['nombre_tipo' => 'YouTube', 'icono' => 'fa-youtube', 'color' => '#FF0000'],
            ['nombre_tipo' => 'Twitter', 'icono' => 'fa-twitter', 'color' => '#1DA1F2'],
            ['nombre_tipo' => 'LinkedIn', 'icono' => 'fa-linkedin', 'color' => '#0077B5'],
            ['nombre_tipo' => 'TikTok', 'icono' => 'fa-tiktok', 'color' => '#000000']
        ];
        
        foreach ($default_types as $default_type) {
            $exists = false;
            foreach ($types as $type) {
                if ($type['nombre_tipo'] === $default_type['nombre_tipo']) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $types[] = $default_type;
            }
        }
        
        usort($types, function($a, $b) {
            return strcmp($a['nombre_tipo'], $b['nombre_tipo']);
        });
        
        return $types;
    }

    // Get network type info
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