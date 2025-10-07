<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_correo'])) {
    header('Location: ../Vistas/Login.php');
    exit();
}

require_once '../Modelos/Conexion.php';
require_once '../Modelos/MovimientosModel.php';
require_once '../Modelos/ProductoModel.php';

$movimientoModel = new MovimientosModel();
$productoModel = new ProductoModel();
$db = Conexion::getInstance()->getConnection();

// Obtener el ID del usuario basado en el correo de la sesión
$usuarioCorreo = $_SESSION['usuario_correo'];
$usuarioId = null;

// Buscar el ID del usuario por su correo
$stmt = $db->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->execute([$usuarioCorreo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    $usuarioId = $usuario['id'];
} else {
    // Si no encuentra el usuario, redirigir al login
    header('Location: ../Vistas/Login.php');
    exit();
}

// Obtener productos con stock
$productosDisponibles = $productoModel->listarProductosActivosConStock();

function obtenerUltimasSalidas(): array {
    $db = Conexion::getInstance()->getConnection();
    $sql = "
        SELECT m.id, p.nombre AS nombre_producto, m.cantidad, m.descripcion, m.fecha_hora, u.correo AS usuario_movimiento
        FROM movimientos m
        JOIN productos p ON p.id = m.producto_id
        JOIN usuarios u ON u.id = m.usuario_id
        WHERE m.tipo_movimiento = 'salida'
        ORDER BY m.fecha_hora DESC
        LIMIT 10
    ";
    $stmt = $db->query($sql);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errores = [];

    $productoId  = intval($_POST['producto_id'] ?? 0);
    $cantidad    = intval($_POST['cantidad'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($productoId <= 0) $errores[] = "Debe seleccionar un producto.";
    if ($cantidad <= 0)   $errores[] = "La cantidad debe ser mayor que cero.";
    if (empty($descripcion)) $errores[] = "Debe especificar un motivo.";
    
    // Ahora $usuarioId ya está definido arriba
    if (empty($usuarioId)) {
        $errores[] = "Usuario no identificado.";
    }

    // Obtener proveedor_id desde inventario
    $proveedorId = null;
    if (empty($errores)) {
        $stmt = $db->prepare("SELECT proveedor_id FROM inventario WHERE producto_id = :producto_id LIMIT 1");
        $stmt->execute(['producto_id' => $productoId]);
        $proveedorId = $stmt->fetchColumn();

        if (!$proveedorId) {
            $errores[] = "No se encontró proveedor para este producto.";
        }
    }

    // Verificar stock
    if (empty($errores)) {
        $stockActual = $movimientoModel->obtenerStockActual($productoId);
        if ($stockActual === false) {
            $errores[] = "Producto no encontrado.";
        } elseif ($cantidad > $stockActual) {
            $errores[] = "Stock insuficiente. Actual: $stockActual unidades.";
        }
    }

    if (empty($errores)) {
$exito = $movimientoModel->registrarMovimientoYActualizarStock(
    $productoId,
    'salida',
    $cantidad,
    $usuarioCorreo,  // ← USA EL CORREO EN LUGAR DEL ID
    $descripcion,
    $proveedorId
);

        if ($exito) {
            $_SESSION['mensaje_exito'] = "Salida registrada exitosamente.";
            header('Location: ../Vistas/RegistrarSalida.php');
            exit();
        } else {
            error_log("🔴 Error al registrar salida en MovimientosModel");
            $errores[] = "Ocurrió un error al registrar la salida.";
        }
    }

    $_SESSION['errores'] = $errores;
    header('Location: ../Vistas/RegistrarSalida.php');
    exit();
}