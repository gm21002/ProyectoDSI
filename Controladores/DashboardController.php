<?php
require_once '../Modelos/DashboardModel.php';
session_start();

// Validación de sesión
if (!isset($_SESSION['usuario_correo'])) {
    header('Location: ../Vistas/Login.php');
    exit();
}

$correo = $_SESSION['usuario_correo'];

$model = new DashboardModel();

// Obtener métricas del panel
$totalProductos = $model->obtenerTotalProductos();
$stockMinimo = $model->obtenerProductosConStockBajo();
$movimientosRecientes = $model->obtenerMovimientosRecientes();
$ultimosMovimientos = $model->obtenerUltimosMovimientos();

// Cargar vista del panel
require_once '../Vistas/Dashboard.php';
