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

    public function crearProveedor($nombre) {
        $stmt = $this->db->prepare("INSERT INTO proveedores (nombre) VALUES (:nombre)");
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }
}