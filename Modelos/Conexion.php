<?php
class Conexion {
    private $host = "localhost";
    private $db = "nextgen_distribuitors_bd-2";  // Nombre de tu BD
    private $user = "root";                       // Usuario XAMPP
    private $pass = "";                           // Contraseña XAMPP
    private $pdo;
    private static $instance = null;

    // Constructor privado
    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->pass
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Obtener instancia singleton
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Conexion();
        }
        return self::$instance;
    }

    // Obtener conexión PDO
    public function getConnection() {
        return $this->pdo;
    }
}
?>
