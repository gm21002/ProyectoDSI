<?php
require_once 'Conexion.php';

class ProveedorModel {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    public function obtenerProveedores() {
        $stmt = $this->db->query("SELECT * FROM proveedores ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si ya existe un proveedor con el mismo nombre
     * ignorando mayúsculas/minúsculas y espacios.
     */
    public function existeProveedor(string $nombre): bool {
        $sql = "SELECT 1 
                FROM proveedores 
                WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:n))
                LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':n' => $nombre]);
        return (bool) $st->fetchColumn();
    }

    /**
     * Crea el proveedor solo si no existe.
     */
    public function crearProveedor($nombre) {
        // validar duplicado
        if ($this->existeProveedor($nombre)) {
            return false;
        }

        $stmt = $this->db->prepare("INSERT INTO proveedores (nombre) VALUES (:nombre)");
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }
}