<?php
session_start();
require_once "../Modelos/UsuarioModel.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Si NO vienen datos de login, asumimos que es un logout (por ejemplo, desde un formulario con solo botón)
    if (empty($_POST['correo']) && empty($_POST['contrasena'])) {
        session_destroy();
        header("Location: ../Vistas/login.php");
        exit;
    }

    $correo = trim($_POST['correo'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    // Validación de campos vacíos
    if (empty($correo) || empty($contrasena)) {
        header("Location: ../Vistas/login.php?error=Todos los campos son obligatorios.");
        exit;
    }

    // Validación formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../Vistas/login.php?error=El formato del correo electrónico no es válido.");
        exit;
    }

    // Validación de longitud y complejidad de contraseña
    if (strlen($contrasena) < 8 || strlen($contrasena) > 12) {
        header("Location: ../Vistas/login.php?error=La contraseña debe tener entre 8 y 12 caracteres.");
        exit;
    }

    if (!preg_match('/[A-Z]/', $contrasena) || 
        !preg_match('/[a-z]/', $contrasena) || 
        !preg_match('/[0-9]/', $contrasena) || 
        !preg_match('/[\W_]/', $contrasena)) {
        header("Location: ../Vistas/login.php?error=La contraseña debe incluir mayúsculas, minúsculas, números y un carácter especial.");
        exit;
    }

    // Consultar usuario en la base de datos (sin revelar si es correo o contraseña)
    $usuarioModel = new UsuarioModel();
    $usuario = $usuarioModel->obtenerUsuarioPorCorreo($correo);

    // Aquí la validación para login
    if ($usuario && $contrasena === $usuario['contrasena']) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        header("Location: ../Vistas/Dashboard.php");
        exit;
    } else {
        header("Location: ../Vistas/login.php?error=1");
        exit;
    }
} else {
    header("Location: ../Vistas/login.php");
    exit;
}

