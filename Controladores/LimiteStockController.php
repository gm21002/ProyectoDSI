<?php
require_once '../Modelos/LimiteStockModel.php';
require_once '../Modelos/ProductoModel.php';
require_once '../Modelos/CategoriaModel.php';

session_start();
$limiteModel    = new LimiteStockModel();
$productoModel  = new ProductoModel();
$categoriaModel = new CategoriaModel();

// Registrar nuevo límite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $producto_id  = intval($_POST['producto_id'] ?? 0);
  $categoria_id = ($_POST['categoria_id'] ?? '') !== '' ? intval($_POST['categoria_id']) : null;
  $min          = intval($_POST['stock_minimo'] ?? -1);
  $max          = intval($_POST['stock_maximo'] ?? -1);

  $errores = [];

  if ($producto_id <= 0) $errores[] = "Debe seleccionar un producto.";
  if ($min < 0 || $max < 0) $errores[] = "Los límites no pueden ser negativos.";
  if ($min >= $max) $errores[] = "El límite mínimo debe ser menor que el máximo.";

  if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    header("Location: ../Vistas/RegistrarLimiteStock.php");
    exit;
  }

  if ($limiteModel->crear($producto_id, $min, $max, $categoria_id)) {
    $_SESSION['exito'] = "Límite guardado correctamente.";
  } else {
    $_SESSION['errores'] = ["No se pudo guardar el límite (¿ya existe?)."];
  }

  header("Location: ../Vistas/RegistrarLimiteStock.php");
  exit;
}

// Mostrar formulario para crear límite
if ($_GET['accion'] ?? '' === 'nuevo') {
  require_once '../Vistas/RegistrarLimiteStock.php';
  exit;
}

// Mostrar lista de límites
if ($_GET['accion'] ?? '' === 'listar') {
  $limites = $limiteModel->listarTodo();
  require_once '../Vistas/ListarLimites.php';
  exit;
}


