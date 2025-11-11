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

    public function obtenerTodosUsuarios() {
    $sql = "SELECT * FROM usuarios ORDER BY id ASC";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarUltimoAcceso($usuarioId) {
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $usuarioId]);
    }


    public function crearUsuario($nombre_usuario, $correo, $contrasena, $rol) {
        try {
            $sql = "INSERT INTO usuarios (nombre_usuario, correo, contrasena, rol)
                    VALUES (:nombre_usuario, :correo, :contrasena, :rol)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'nombre_usuario' => $nombre_usuario,
                'correo' => $correo,
                'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
                'rol' => $rol
            ]);
            return true;
        } catch (PDOException $e) {
            echo "<script>alert('Error al crear usuario: " . $e->getMessage() . "');</script>";
            return false;
        }
    }

public function obtenerUsuarioPorId($id) {
    $sql = "SELECT id, nombre_usuario, correo, rol FROM usuarios WHERE id = :id LIMIT 1";
    $st  = $this->pdo->prepare($sql);
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();
    return $st->fetch(PDO::FETCH_ASSOC);
}

public function existeCorreoEnOtro($correo, $id) {
    $sql = "SELECT 1 FROM usuarios WHERE correo = :correo AND id <> :id LIMIT 1";
    $st  = $this->pdo->prepare($sql);
    $st->execute([':correo'=>$correo, ':id'=>$id]);
    return (bool)$st->fetchColumn();
}

public function actualizarUsuario($id, $nombre_usuario, $correo, $rol) {
    $sql = "UPDATE usuarios
            SET nombre_usuario = :nombre_usuario,
                correo = :correo,
                rol = :rol
            WHERE id = :id";
    $st = $this->pdo->prepare($sql);
    return $st->execute([
        ':nombre_usuario' => $nombre_usuario,
        ':correo'         => $correo,
        ':rol'            => $rol,
        ':id'             => $id
    ]);
}

public function eliminarUsuario($id) {
    try {
        $st = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        return $st->execute([':id' => $id]);
    } catch (PDOException $e) {
        // Si hay FK/relaciones, MySQL lanza 23000
        if ($e->getCode() === '23000') {
            return false;
        }
        throw $e; // para depurar otros errores
    }
}


}

