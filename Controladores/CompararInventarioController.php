<?php
session_start();
require_once '../Modelos/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    $archivo = $_FILES['archivo_csv']['tmp_name'];

    if (!file_exists($archivo)) {
        $_SESSION['reporte_comparacion'] = [];
        header('Location: ../Vistas/CompararInventario.php');
        exit();
    }

    $db = Conexion::getInstance()->getConnection();

    $reporte = [];

    if (($handle = fopen($archivo, 'r')) !== false) {
        $encabezado = fgetcsv($handle); // Leer encabezado
        while (($fila = fgetcsv($handle)) !== false) {
            // Asumimos que el CSV tiene: código, nombre, categoría, proveedor, precio, stock_fisico
            [$codigo, $nombre, $categoria, $proveedor, $precio, $stock_fisico] = $fila;

            $sql = "
                SELECT p.codigo, p.nombre, c.nombre AS categoria, pr.nombre AS proveedor,
                       i.precio, i.cantidad_stock
                FROM inventario i
                JOIN productos p ON p.id = i.producto_id
                JOIN categorias c ON c.id = p.categoria_id
                JOIN proveedores pr ON pr.id = i.proveedor_id
                WHERE p.codigo = :codigo
                LIMIT 1
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([':codigo' => $codigo]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($producto) {
                $diferencia = floatval($stock_fisico) - floatval($producto['cantidad_stock']);
                $estado = ($diferencia === 0.0) ? 'Coincide' : 'Diferente';

                $reporte[] = [
                    'codigo'         => $codigo,
                    'nombre'         => $producto['nombre'],
                    'categoria'      => $producto['categoria'],
                    'proveedor'      => $producto['proveedor'],
                    'precio'         => $producto['precio'],
                    'stock_sistema'  => $producto['cantidad_stock'],
                    'stock_fisico'   => $stock_fisico,
                    'diferencia'     => $diferencia,
                    'estado'         => $estado
                ];
            } else {
                $reporte[] = [
                    'codigo'         => $codigo,
                    'nombre'         => $nombre,
                    'categoria'      => $categoria,
                    'proveedor'      => $proveedor,
                    'precio'         => $precio,
                    'stock_sistema'  => 'N/A',
                    'stock_fisico'   => $stock_fisico,
                    'diferencia'     => 'N/A',
                    'estado'         => 'No encontrado'
                ];
            }
        }
        fclose($handle);
    }

    $_SESSION['reporte_comparacion'] = $reporte;
    header('Location: ../Vistas/CompararInventario.php');
    exit();
} else {
    echo "Error: No se recibió archivo válido.";
}
