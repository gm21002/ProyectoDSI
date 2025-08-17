<?php
// session_start(); // Comentado porque ya se llama desde la vista salidas.php

if (!isset($_SESSION['usuario_correo'])) {
    header('Location: ../Vistas/Login.php');
    exit();
}

require_once '../Modelos/Conexion.php';
require_once '../Modelos/CategoriaModel.php';
// require_once '../Modelos/ProductoModel.php'; // Solo si necesitas lógica de productos

$categoriaModel = new CategoriaModel();
$catList = $categoriaModel->obtenerCategorias(); // Filtro de categorías

// Variables para los datos de los reportes
$productosInventario = [];
$movimientosSalida = [];

// Obtener parámetros de búsqueda y filtro
$search = trim($_GET['q'] ?? '');
$catId = intval($_GET['cat'] ?? 0);
$action = $_GET['action'] ?? $_POST['action'] ?? 'listarReporteInventario';

// Parámetros específicos para el reporte de salidas
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

switch ($action) {
    case 'exportarInventarioCSV':
        exportarInventarioCSV($search, $catId);
        break;

    case 'listarReporteInventario':
        $db = Conexion::getInstance()->getConnection();
        $sql = "
            SELECT i.id, p.nombre, p.codigo, c.nombre AS categoria, pr.nombre AS proveedor, i.precio, i.cantidad_stock
            FROM inventario i
            JOIN productos p ON p.id = i.producto_id
            JOIN categorias c ON c.id = p.categoria_id
            JOIN proveedores pr ON pr.id = i.proveedor_id
            WHERE (:search = '' OR p.nombre LIKE CONCAT('%', :search ,'%') OR p.codigo LIKE CONCAT('%', :search ,'%'))
              AND (:cat = 0 OR p.categoria_id = :cat)
            ORDER BY p.nombre
            LIMIT 500
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':search' => $search, ':cat' => $catId]);
        $productosInventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'listarReporteSalidas':
        $movimientosSalida = obtenerReporteSalidas($search, $catId, $fechaInicio, $fechaFin);
        break;

    case 'exportarSalidasCSV':
        exportarSalidasCSV($search, $catId, $fechaInicio, $fechaFin);
        break;

    default:
        $movimientosSalida = obtenerReporteSalidas($search, $catId, $fechaInicio, $fechaFin);

        $db = Conexion::getInstance()->getConnection();
        $sql = "
            SELECT i.id, p.nombre, p.codigo, c.nombre AS categoria, pr.nombre AS proveedor, i.precio, i.cantidad_stock
            FROM inventario i
            JOIN productos p ON p.id = i.producto_id
            JOIN categorias c ON c.id = p.categoria_id
            JOIN proveedores pr ON pr.id = i.proveedor_id
            ORDER BY p.nombre
            LIMIT 200
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $productosInventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}

// === FUNCIONES ===

function exportarInventarioCSV(string $search, int $catId) {
    require_once '../Modelos/Conexion.php';
    $db = Conexion::getInstance()->getConnection();

    $sql = "
        SELECT p.codigo, p.nombre, c.nombre AS categoria, pr.nombre AS proveedor, i.precio, i.cantidad_stock
        FROM inventario i
        JOIN productos p ON p.id = i.producto_id
        JOIN categorias c ON c.id = p.categoria_id
        JOIN proveedores pr ON pr.id = i.proveedor_id
        WHERE (:search = '' OR p.nombre LIKE CONCAT('%', :search ,'%') OR p.codigo LIKE CONCAT('%', :search ,'%'))
          AND (:cat = 0 OR p.categoria_id = :cat)
        ORDER BY p.nombre
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':search' => $search, ':cat' => $catId]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($productos)) {
        echo "No hay datos para exportar.";
        exit();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Código', 'Nombre Producto', 'Categoría', 'Proveedor', 'Precio Unitario', 'Cantidad en Stock']);
    foreach ($productos as $p) {
        fputcsv($output, [
            $p['codigo'],
            $p['nombre'],
            $p['categoria'],
            $p['proveedor'],
            number_format($p['precio'], 2),
            $p['cantidad_stock']
        ]);
    }
    fclose($output);
    exit();
}

function obtenerReporteSalidas(string $search, int $catId, string $fechaInicio, string $fechaFin): array {
    $db = Conexion::getInstance()->getConnection();
    $sql = "
        SELECT
            m.id,
            p.codigo,
            p.nombre AS nombre_producto,
            c.nombre AS nombre_categoria,
            m.cantidad,
            m.fecha_hora,
            m.descripcion,
            u.correo AS usuario_movimiento
        FROM movimientos m
        JOIN productos p ON p.id = m.producto_id
        JOIN categorias c ON c.id = p.categoria_id
        JOIN usuarios u ON u.id = m.usuario_id
        WHERE m.tipo_movimiento = 'salida'
          AND m.fecha_hora BETWEEN :fecha_inicio AND DATE_ADD(:fecha_fin, INTERVAL 1 DAY)
          AND (:search = '' OR p.nombre LIKE CONCAT('%', :search, '%') OR p.codigo LIKE CONCAT('%', :search, '%'))
          AND (:cat = 0 OR p.categoria_id = :cat)
        ORDER BY m.fecha_hora DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':fecha_inicio' => $fechaInicio,
        ':fecha_fin'    => $fechaFin,
        ':search'       => $search,
        ':cat'          => $catId
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportarSalidasCSV(string $search, int $catId, string $fechaInicio, string $fechaFin) {
    $movimientos = obtenerReporteSalidas($search, $catId, $fechaInicio, $fechaFin);

    if (empty($movimientos)) {
        echo "No hay movimientos de salida que coincidan con los filtros.";
        exit();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_salidas_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID Movimiento', 'Código Producto', 'Nombre Producto', 'Categoría', 'Cantidad Salida', 'Fecha y Hora', 'Descripción', 'Usuario']);

    foreach ($movimientos as $mov) {
        fputcsv($output, [
            $mov['id'],
            $mov['codigo'],
            $mov['nombre_producto'],
            $mov['nombre_categoria'],
            $mov['cantidad'],
            $mov['fecha_hora'],
            $mov['descripcion'],
            $mov['usuario_movimiento']
        ]);
    }
    fclose($output);
    exit();
}
