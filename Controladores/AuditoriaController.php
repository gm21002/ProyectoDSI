<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "../Modelos/AuditoriaModel.php";

class AuditoriaController {
    public function mostrarVista() {
        $modelo = new AuditoriaModel();

        $filtros = [
            'producto' => trim($_GET['producto'] ?? ''),
            'usuario'  => trim($_GET['usuario'] ?? ''),
            'tipo'     => trim($_GET['tipo'] ?? ''),
            'desde'    => trim($_GET['desde'] ?? ''),
            'hasta'    => trim($_GET['hasta'] ?? '')
        ];

        $movimientos = $modelo->obtenerMovimientos($filtros);

        include "../Vistas/Auditoria.php";
    }
}

$controlador = new AuditoriaController();
$controlador->mostrarVista();
