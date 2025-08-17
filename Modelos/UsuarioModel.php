<?php
require_once "Conexion.php";

class UsuarioModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstance()->getConnection();
    }

    public function obtenerUsuarioPorCorreo($correo) {
        $sql = "SELECT * FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['correo' => $correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarUltimoAcceso($usuarioId) {
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $usuarioId]);
    }
}

