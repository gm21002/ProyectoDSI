<?php
require_once 'Conexion.php';

class DashboardModel {
    private $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    // Total de productos en inventario
    public function obtenerTotalProductos() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM inventario");
        return $stmt->fetchColumn();
    }

    // Productos con stock bajo según límite definido
public function obtenerProductosConStockBajo() {
    $stmt = $this->db->query("
        SELECT COUNT(*) FROM inventario i
        JOIN limites_stock l ON i.producto_id = l.producto_id
        WHERE i.cantidad_stock < l.stock_minimo
    ");
    return $stmt->fetchColumn();
}


    // Movimientos recientes (últimos 7 días)
    public function obtenerMovimientosRecientes($dias = 7) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM movimientos 
            WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$dias]);
        return $stmt->fetchColumn();
    }

    // Últimos movimientos (últimos 5 registros)
public function obtenerUltimosMovimientos($limite = 5) {
    $stmt = $this->db->prepare("
        SELECT m.tipo, m.cantidad, m.fecha_hora,
               IFNULL(p.nombre, CONCAT('Producto #', m.producto_id)) AS producto,
               IFNULL(u.correo, m.usuario_correo) AS usuario
        FROM movimientos m
        LEFT JOIN productos p ON p.id = m.producto_id
        LEFT JOIN usuarios u ON u.id = m.usuario_id
        ORDER BY m.fecha_hora DESC
        LIMIT :limite
    ");
    $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}