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
                   cantidad_stock, fecha_ingreso)
                VALUES (:prod, :prov, :desc, :precio,
                        :cantidad_stock, :fecha)";
        return $this->db->prepare($sql)->execute($data);
    }

    /* ------------------------------------------------------------
       Sumar stock o crear nuevo registro si no existe
    ------------------------------------------------------------ */
    public function agregarStock(array $data): bool {
        // ¿ya hay inventario para el producto?
        $st = $this->db->prepare("SELECT id, cantidad_stock
                                  FROM inventario
                                  WHERE producto_id = ? LIMIT 1");
        $st->execute([$data[':prod']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $nuevo = $row['cantidad_stock'] + $data[':cantidad_stock'];

            $up = $this->db->prepare("UPDATE inventario SET
                        cantidad_stock = :cant,
                        proveedor_id   = :prov,
                        descripcion    = :desc,
                        precio         = :precio,
                        fecha_ingreso  = :fecha
                      WHERE id = :id");
            return $up->execute([
                ':cant'  => $nuevo,
                ':prov'  => $data[':prov'],
                ':desc'  => $data[':desc'],
                ':precio'=> $data[':precio'],
                ':fecha' => $data[':fecha'],
                ':id'    => $row['id']
            ]);
        }

        // si no existe, insertar
        return $this->guardar($data);
    }
}