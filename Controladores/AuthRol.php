<?php

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Vistas/Login.php");
    exit;
}

// Roles permitidos para la página
$rolesPermitidos = $rolesPermitidos ?? []; // Ejemplo: ['Administrador']

if (!in_array($_SESSION['usuario_rol'], $rolesPermitidos)) {
    // Usuario no tiene permiso, redirigir o mostrar mensaje
    echo "<script>alert('No tienes permisos para acceder a esta página'); window.location='../Vistas/Dashboard.php';</script>";
    exit;
}
