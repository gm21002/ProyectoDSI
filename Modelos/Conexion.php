<?php
class Conexion {
    private $host = "localhost";
    private $db = "nextgen_distribuitors_bd-2";  // Cambia al nombre de tu BD
    private $user = "root";                    // Usuario por defecto XAMPP
    private $pass = "";                        // Contraseña por defecto vacío en XAMPP
    private $pdo;
    private static $instance = null;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8",
                $this->user,
                $this->pass
            );
            // Para lanzar excepciones en errores
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Conexion();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}
