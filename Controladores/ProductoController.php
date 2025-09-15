<?php
/**
 * ProductoController.php
 *  - Registra un nuevo producto en la tabla `productos`
 *  - Genera automáticamente un código interno único (PROD-XXXX) y lo guarda
 *  - Después crea la entrada inicial de inventario
 *  - Redirige con ?exito=1&codigo=PROD-XXXX o ?error=...
 */

session_start();

require_once '../Modelos/Conexion.php';
require_once '../Modelos/ProductoModel.php';

class ProductoController
{
    private ProductoModel   $productoModel;

    public function __construct()
    {
        $this->productoModel   = new ProductoModel();
    }

    /* ------------------------------------------------------------
       Procesar POST desde RegistrarProducto.php
       ------------------------------------------------------------*/
    public function guardar()
    {
        /* 1. Capturar datos */
        $nombre          = trim($_POST['nombre']            ?? '');
        $categoria_id    = intval($_POST['categoria_id']    ?? 0);

        /* 2. Validaciones */
        if (!$nombre || strlen($nombre) < 3 || $categoria_id === 0) {
            header('Location: ../Vistas/RegistrarProducto.php?error=campos');
            exit;
        }

        /* 3. Crear el producto (genera código único dentro) */
        $producto_id = $this->productoModel->crear($nombre, $categoria_id);

        if (!$producto_id) {
            header('Location: ../Vistas/RegistrarProducto.php?error=guardar_producto');
            exit;
        }

        /* 4. Obtener el código generado para mostrarlo al usuario */
        $codigo = $this->productoModel->obtenerCodigo($producto_id);

        /* 5. Éxito */
        header('Location: ../Vistas/RegistrarProducto.php?exito=1&codigo=' . urlencode($codigo));
        exit;
    }
}

/* ------------------------------------------------------------
   Despachar si se accede directamente
   ------------------------------------------------------------*/
$controller = new ProductoController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->guardar();
} else {
    // acceso directo no permitido
    header('Location: ../Vistas/RegistrarProducto.php');
    exit;
}