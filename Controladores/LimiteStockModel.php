<?php
require_once 'Conexion.php';

class LimiteStockModel {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    /**
     * Crear o reemplazar límite de stock para un producto,
     * opcionalmente ligado a una categoría.
     */
    public function crear(int $productoId, int $min, int $max, ?int $categoriaId = null): bool {
        $sql = "REPLACE INTO limites_stock (producto_id, categoria_id, stock_minimo, stock_maximo)
                VALUES (:prod, :cat, :min, :max)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':prod' => $productoId,
            ':cat'  => $categoriaId,
            ':min'  => $min,
            ':max'  => $max
        ]);
    }

    /**
     * Obtener el registro completo de límites para un producto, o null si no existe.
     */
    public function obtenerPorProducto(int $productoId): ?array {
        $sql = "SELECT * FROM limites_stock WHERE producto_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Obtener límites de stock por producto (alias de obtenerPorProducto),
     * devolviendo siempre un array con claves mín y máx.
     */
    public function ver(int $productoId): array {
        $row = $this->obtenerPorProducto($productoId);
        if ($row === null) {
            return ['stock_minimo' => '', 'stock_maximo' => ''];
        }
        return [
            'stock_minimo' => $row['stock_minimo'],
            'stock_maximo' => $row['stock_maximo']
        ];
    }

    /**
     * Actualizar límites de stock por ID de registro.
     */
    public function actualizar(int $id, int $min, int $max): bool {
        $sql = "UPDATE limites_stock
                SET stock_minimo = :min,
                    stock_maximo = :max
                WHERE id = :id";
        return $this->db->prepare($sql)->execute([
            ':min' => $min,
            ':max' => $max,
            ':id'  => $id
        ]);
    }

    /**
     * Eliminar un registro de límite de stock por ID.
     */
    public function eliminar(int $id): bool {
        return $this->db
                    ->prepare("DELETE FROM limites_stock WHERE id = ?")
                    ->execute([$id]);
    }

    /**
     * Listar todos los registros de límites y su producto asociado.
     */
    public function listarTodo(): array {
        $sql = "SELECT l.*, p.nombre AS producto
                FROM limites_stock l
                JOIN productos p ON p.id = l.producto_id
                ORDER BY p.nombre";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}