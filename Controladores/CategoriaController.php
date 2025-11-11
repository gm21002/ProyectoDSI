<?php
require_once '../Modelos/CategoriaModel.php';
require_once '../Modelos/Conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    if (strlen($nombre) < 3) {
        echo json_encode(['exito' => false, 'mensaje' => 'El nombre debe tener al menos 3 caracteres.']);
        exit;
    }

    $model = new CategoriaModel();
    if ($model->crearCategoria($nombre)) {
        $id = Conexion::getInstance()->getConnection()->lastInsertId();
        echo json_encode(['exito' => true, 'id' => $id, 'mensaje' => 'Categoría registrada correctamente.']);
    } else {
        // aquí puede ser porque ya existe
        echo json_encode(['exito' => false, 'mensaje' => 'La categoría ya existe.']);
    }
}