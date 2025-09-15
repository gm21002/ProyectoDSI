<?php
require_once '../Modelos/ProveedorModel.php';
require_once '../Modelos/Conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    if (strlen($nombre) < 3) {
        echo json_encode(['exito' => false, 'mensaje' => 'Nombre muy corto']);
        exit;
    }

    $model = new ProveedorModel();
    if ($model->crearProveedor($nombre)) {
        $id = Conexion::getInstance()->getConnection()->lastInsertId();
        echo json_encode(['exito' => true, 'id' => $id]);
    } else {
        echo json_encode(['exito' => false, 'mensaje' => 'Ya existe o error BD']);
    }
}