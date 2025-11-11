<?php
require_once 'Conexion.php';

class CategoriaModel {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    public function obtenerCategorias() {
        $stmt = $this->db->query("SELECT * FROM categorias ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si ya existe una categoría con el mismo nombre
     * ignorando espacios al inicio/fin y mayúsculas/minúsculas.
     */
    public function existeCategoria(string $nombre): bool {
        $sql = "SELECT 1 
                FROM categorias 
                WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:n))
                LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([':n' => $nombre]);
        return (bool) $st->fetchColumn();
    }

    /**
     * Crea la categoría solo si no existe.
     * Devuelve true si se insertó y false si ya existía o hubo error.
     */
    public function crearCategoria($nombre) {
        // primero validamos duplicado
        if ($this->existeCategoria($nombre)) {
            return false;
        }

        $stmt = $this->db->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }
}