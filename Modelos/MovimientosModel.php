<?php
require_once 'Conexion.php';

class MovimientosModel {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    /**
     * Registra un movimiento de inventario simple (usado en versiones anteriores).
     * $data = [
     *   ':prod'    => int producto_id,
     *   ':prov'    => int proveedor_id,
     *   ':cantidad'=> int cantidad,
     *   ':tipo'    => 'entrada' | 'salida',
     *   ':fecha'   => string 'YYYY-MM-DD HH:MM:SS',
     *   ':usuario' => string correo del usuario
     * ]
     */
    public function crear(array $data): bool {
        $sql = "INSERT INTO movimientos
                  (producto_id, proveedor_id, cantidad, tipo, fecha, usuario_correo)
                VALUES
                  (:prod, :prov, :cantidad, :tipo, :fecha, :usuario)";
        return $this->db->prepare($sql)->execute($data);
    }

    /**
     * Registra un movimiento de entrada o salida y actualiza el stock del producto de forma transaccional.
     *
     * @param int    $productoId    ID del producto.
     * @param string $tipoMovimiento 'entrada' o 'salida'.
     * @param int    $cantidad      Cantidad a mover.
     * @param int    $usuarioId     ID del usuario que registra el movimiento.
     * @param string $descripcion   Motivo del movimiento.
     * @param int    $proveedorId   ID del proveedor.
     * @return bool                 True si todo fue exitoso, false en caso contrario.
     */
    public function registrarMovimientoYActualizarStock(
        int $productoId,
        string $tipoMovimiento,
        int $cantidad,
        int $usuarioId,
        string $descripcion,
        int $proveedorId
    ): bool {
        $this->db->beginTransaction();

        try {
            // 1. Obtener y bloquear stock actual
            $stmt = $this->db->prepare("SELECT cantidad_stock FROM inventario WHERE producto_id = ? FOR UPDATE");
            $stmt->execute([$productoId]);
            $currentStock = $stmt->fetchColumn();

            if ($currentStock === false) {
                throw new Exception("Producto no encontrado.");
            }

            if ($tipoMovimiento === 'salida' && $cantidad > $currentStock) {
                throw new Exception("Stock insuficiente. Actual: $currentStock");
            }

            $newStock = $tipoMovimiento === 'entrada'
                ? $currentStock + $cantidad
                : $currentStock - $cantidad;

            // 2. Actualizar inventario
            $stmt = $this->db->prepare("UPDATE inventario SET cantidad_stock = ? WHERE producto_id = ?");
            $stmt->execute([$newStock, $productoId]);

            // 3. Registrar el movimiento
            $stmt = $this->db->prepare("
                INSERT INTO movimientos 
                (producto_id, proveedor_id, cantidad, descripcion, tipo, fecha, usuario_correo, fecha_hora, tipo_movimiento, usuario_id)
                VALUES 
                (?, ?, ?, ?, ?, NOW(), NULL, NOW(), ?, ?)
            ");
            $stmt->execute([
                $productoId,
                $proveedorId,
                $cantidad,
                $descripcion,
                $tipoMovimiento,   // campo 'tipo'
                $tipoMovimiento,   // campo 'tipo_movimiento'
                $usuarioId
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al registrar movimiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el stock actual de un producto.
     *
     * @param int $productoId ID del producto.
     * @return int|false      Cantidad en stock o false si no se encuentra el producto.
     */
    public function obtenerStockActual(int $productoId) {
        $stmt = $this->db->prepare("SELECT cantidad_stock FROM inventario WHERE producto_id = ?");
        $stmt->execute([$productoId]);
        return $stmt->fetchColumn();
    }
}
