<?php
require_once "Conexion.php";

class AuditoriaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexion::getInstance()->getConnection();
    }

    public function obtenerMovimientos($filtros) {
        $sql = "SELECT 
                    m.tipo,
                    p.nombre AS producto,
                    m.cantidad,
                    m.fecha_hora AS fecha,
                    m.usuario_correo AS usuario,
                    m.descripcion AS motivo
                FROM movimientos m
                INNER JOIN productos p ON m.producto_id = p.id
                WHERE 1";

        $params = [];

        if (!empty($filtros['producto'])) {
            $sql .= " AND p.nombre LIKE :producto";
            $params[':producto'] = '%' . $filtros['producto'] . '%';
        }

        if (!empty($filtros['usuario'])) {
            $sql .= " AND m.usuario_correo LIKE :usuario";
            $params[':usuario'] = '%' . $filtros['usuario'] . '%';
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo']; // sin strtolower()
        }

        if (!empty($filtros['desde'])) {
            $sql .= " AND m.fecha_hora >= :desde";
            $params[':desde'] = $filtros['desde'] . ' 00:00:00';
        }

        if (!empty($filtros['hasta'])) {
            $sql .= " AND m.fecha_hora <= :hasta";
            $params[':hasta'] = $filtros['hasta'] . ' 23:59:59';
        } 

        $sql .= " ORDER BY m.fecha_hora DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

