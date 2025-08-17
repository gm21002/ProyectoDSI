<?php
require_once 'Conexion.php';

class LimiteStockModel {
  private PDO $db;

  public function __construct() {
    $this->db = Conexion::getInstance()->getConnection();
  }

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


  public function obtenerPorProducto(int $productoId): ?array {
    $sql = "SELECT * FROM limites_stock WHERE producto_id = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$productoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function actualizar(int $id, int $min, int $max): bool {
    $sql = "UPDATE limites_stock SET stock_minimo = :min, stock_maximo = :max WHERE id = :id";
    return $this->db->prepare($sql)->execute([
      ':min' => $min,
      ':max' => $max,
      ':id'  => $id
    ]);
  }

  public function eliminar(int $id): bool {
    return $this->db->prepare("DELETE FROM limites_stock WHERE id = ?")->execute([$id]);
  }

  public function listarTodo(): array {
    $sql = "SELECT l.*, p.nombre AS producto FROM limites_stock l
            JOIN productos p ON p.id = l.producto_id
            ORDER BY p.nombre";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  }
}
