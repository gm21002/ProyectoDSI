<?php
/**
 * InventarioModel
 *  - Tabla inventario
 *  - guardar(): crea un registro nuevo
 *  - agregarStock(): si el producto existe suma la cantidad; si no, inserta
 */

require_once 'Conexion.php';

class InventarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::getInstance()->getConnection();
    }

    /* ------------------------------------------------------------
       Actualizar stock_historico si el nuevo stock lo supera
    ------------------------------------------------------------ */
    private function actualizarStockHistorico(int $inventario_id, int $nuevo_stock_total): bool {
        $sql = "UPDATE inventario 
                SET stock_historico = GREATEST(COALESCE(stock_historico, 0), ?) 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nuevo_stock_total, $inventario_id]);
    }

    /* ------------------------------------------------------------
       Crear registro de inventario (entrada nueva)
       $data = [
         ':prod'           => int producto_id,
         ':prov'           => int proveedor_id,
         ':desc'           => string descripcion,
         ':precio'         => float precio,
         ':cantidad_stock' => int cantidad_stock,
         ':fecha'          => string YYYY-MM-DD
       ]
    ------------------------------------------------------------ */
    public function guardar(array $data): bool {
        $sql = "INSERT INTO inventario
                  (producto_id, proveedor_id, descripcion, precio,
                   cantidad_stock, fecha_ingreso, stock_historico)
                VALUES (:prod, :prov, :desc, :precio,
                        :cantidad_stock, :fecha, :stock_historico)";
        
        // Agregar stock_historico al array de datos (igual a cantidad_stock inicial)
        $data[':stock_historico'] = $data[':cantidad_stock'];
        
        return $this->db->prepare($sql)->execute($data);
    }

    /* ------------------------------------------------------------
       Sumar stock o crear nuevo registro si no existe
    ------------------------------------------------------------ */
    public function agregarStock(array $data): bool {
        // ¿ya hay inventario para el producto?
        $st = $this->db->prepare("SELECT id, cantidad_stock, stock_historico
                                  FROM inventario
                                  WHERE producto_id = ? LIMIT 1");
        $st->execute([$data[':prod']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $nuevo_stock = $row['cantidad_stock'] + $data[':cantidad_stock'];
            
            // Actualizar el stock normal
            $up = $this->db->prepare("UPDATE inventario SET
                        cantidad_stock = :cant,
                        proveedor_id   = :prov,
                        descripcion    = :desc,
                        precio         = :precio,
                        fecha_ingreso  = :fecha
                      WHERE id = :id");
            $result = $up->execute([
                ':cant'  => $nuevo_stock,
                ':prov'  => $data[':prov'],
                ':desc'  => $data[':desc'],
                ':precio'=> $data[':precio'],
                ':fecha' => $data[':fecha'],
                ':id'    => $row['id']
            ]);
            
            // Si la actualización fue exitosa, actualizar stock_historico si es necesario
            if ($result) {
                $this->actualizarStockHistorico($row['id'], $nuevo_stock);
            }
            
            return $result;
        }

        // si no existe, insertar nuevo producto
        return $this->guardar($data);
    }
}