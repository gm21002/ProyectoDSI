<?php
require_once 'Conexion.php';

class CategoriaModel {
    private $db;

    public function __construct() {
        // Usamos el singleton existente
        $this->db = Conexion::getInstance()->getConnection();
    }

    public function obtenerCategorias() {
        $stmt = $this->db->query("SELECT * FROM categorias ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearCategoria($nombre) {
        $stmt = $this->db->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }
}