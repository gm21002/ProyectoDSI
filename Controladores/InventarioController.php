<?php
/**
 * InventarioController.php
 *  - Registra una entrada de inventario ligada a un producto ya existente.
 *  - La tabla de destino es `inventario` con los campos:
 *      producto_id, proveedor_id, descripcion, precio, cantidad_stock, fecha_ingreso
 */
session_start();

require_once '../Modelos/InventarioModel.php';
require_once '../Modelos/ProductoModel.php';     // solo para listar en formularios o validar existencia
require_once '../Modelos/ProveedorModel.php';    // idem
require_once '../Modelos/MovimientosModel.php';
$movModel = new MovimientosModel();

$inventarioModel = new InventarioModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar y limpiar datos del formulario
    $producto_id     = intval($_POST['producto_id']    ?? 0);   // viene de un <select>
    $proveedor_id    = intval($_POST['proveedor_id']   ?? 0);
    $descripcion     = trim($_POST['descripcion']      ?? '');
    $precio          = floatval($_POST['precio']       ?? 0);
    $cantidad_stock  = intval($_POST['cantidad_stock'] ?? 0);
    $fecha_ingreso   = $_POST['fecha_ingreso']         ?? date('Y-m-d');

  // Validar fecha de ingreso: no vacía, formato YYYY-MM-DD, entre hace 30 días y hoy
    if (empty($fecha_ingreso)) {
        header('Location: ../Vistas/RegistrarInventario.php?error=fecha');
        exit;
    }
    $dtFecha = DateTime::createFromFormat('Y-m-d', $fecha_ingreso);
    $today   = new DateTime('today');
    $monthAgo= (clone $today)->modify('-30 days');
    if (!$dtFecha || $dtFecha->format('Y-m-d') !== $fecha_ingreso
        || $dtFecha > $today
        || $dtFecha < $monthAgo) {
        header('Location: ../Vistas/RegistrarInventario.php?error=fecha');
        exit;
    }

    
    // Validaciones básicas
    if ($producto_id && $proveedor_id && $precio > 0 && $cantidad_stock > 0) {

        // Preparar datos para el modelo
        $data = [
            ':prod'           => $producto_id,
            ':prov'           => $proveedor_id,
            ':desc'           => $descripcion,
            ':precio'         => $precio,
            ':cantidad_stock' => $cantidad_stock,
            ':fecha'          => $fecha_ingreso
        ];

        // Agregar stock (sumar si ya existe, insertar si no)
        $ok = $inventarioModel->agregarStock($data);

        if ($ok) {
            // Registrar movimiento de entrada
            $movData = [
                ':prod'     => $producto_id,
                ':prov'     => $proveedor_id,
                ':cantidad' => $cantidad_stock,
                ':tipo'     => 'entrada',
                ':fecha'    => date('Y-m-d H:i:s'),
                ':usuario'  => $_SESSION['usuario_correo'] ?? ''
            ];
            $movModel->crear($movData);

            header('Location: ../Vistas/ListarInventario.php?exito=1');
            exit;
        } else {
            header('Location: ../Vistas/RegistrarInventario.php?error=1');
            exit;
        }
    } else {
        // Error por campos vacíos o inválidos
        header('Location: ../Vistas/RegistrarInventario.php?error=campos');
        exit;
    }
}