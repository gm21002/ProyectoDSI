<?php
require_once "../Modelos/UsuarioModel.php";

class UsuarioController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'registrar') {
        $this->registrarUsuario();
    } elseif ($accion === 'actualizar') {
        $this->actualizarUsuario();
    } elseif ($accion === 'eliminar') {   // 👈 AGREGADO AQUÍ
        $this->eliminarUsuario((int)($_POST['id'] ?? 0));
    } else {
        echo "<script>alert('Acción no reconocida'); window.history.back();</script>";
    }
} else {
            // GET (opcional: eliminar por GET si así lo usas)
            if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id'])) {
                $this->eliminarUsuario((int)$_GET['id']);
            } else {
                header("Location: ../Vistas/ListarUsuario.php");
            }
        }
    }

    private function registrarUsuario() {
        $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
        $correo         = trim($_POST['correo'] ?? '');
        $contrasena     = trim($_POST['contrasena'] ?? '');
        $rol            = trim($_POST['rol'] ?? '');

        if ($nombre_usuario === '' || $correo === '' || $contrasena === '' || $rol === '') {
            echo "<script>alert('Todos los campos son obligatorios'); window.history.back();</script>"; return;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Formato de correo no válido'); window.history.back();</script>"; return;
        }

        $ok = $this->usuarioModel->crearUsuario($nombre_usuario, $correo, $contrasena, $rol);
        echo "<script>alert('".($ok?'Usuario registrado correctamente':'Error al registrar el usuario')."'); 
              window.location='../Vistas/ListarUsuario.php';</script>";
    }

    private function actualizarUsuario() {
        $id             = (int)($_POST['id'] ?? 0);
        $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
        $correo         = trim($_POST['correo'] ?? '');
        $rol            = trim($_POST['rol'] ?? '');

        if ($id <= 0 || $nombre_usuario === '' || $correo === '' || $rol === '') {
            echo "<script>alert('Datos incompletos'); window.history.back();</script>"; return;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Formato de correo no válido'); window.history.back();</script>"; return;
        }
        if ($this->usuarioModel->existeCorreoEnOtro($correo, $id)) {
            echo "<script>alert('El correo ya está en uso por otro usuario'); window.history.back();</script>"; return;
        }

        $ok = $this->usuarioModel->actualizarUsuario($id, $nombre_usuario, $correo, $rol);
        echo "<script>alert('".($ok?'Usuario actualizado correctamente':'No se pudo actualizar')."'); 
              window.location='../Vistas/ListarUsuario.php';</script>";
    }

   private function eliminarUsuario($id) {
    if ($id <= 0) {
        echo "<script>alert('ID inválido'); window.history.back();</script>"; exit;
    }
    $ok = $this->usuarioModel->eliminarUsuario($id);
    if ($ok) {
        header("Location: ../Vistas/ListarUsuario.php?msg=eliminado"); exit;
    } else {
        echo "<script>alert('No se pudo eliminar (el usuario tiene dependencias)'); 
              window.location='../Vistas/ListarUsuario.php';</script>";
        exit;
    }
}
}

$controller = new UsuarioController();
$controller->handle();