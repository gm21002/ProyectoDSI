<?php
// Activar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 120);

// Crear directorio temporal si no existe
$tempDir = __DIR__ . '/tmp';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0755, true);
}
if (!is_writable($tempDir)) {
    chmod($tempDir, 0755);
}

// Incluir dependencias
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'SalidaController.php';

date_default_timezone_set('America/El_Salvador');

// Obtener filtros desde GET
$search = $_GET['q'] ?? '';
$catId = intval($_GET['cat'] ?? 0);
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Obtener datos
$movimientos = obtenerReporteSalidas($search, $catId, $fechaInicio, $fechaFin);

// Inicializar mPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'default_font_size' => 10,
    'default_font' => 'dejavusans',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 40,
    'margin_bottom' => 25,
    'margin_header' => 10,
    'margin_footer' => 10,
    'tempDir' => $tempDir,
    'allow_output_buffering' => true
]);

// Logo
$logoPath = './logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}

// HTML
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Reporte de Salidas</title>
<style>
body { font-family: "DejaVu Sans", Arial, sans-serif; color: #333; line-height: 1.4; }
.header-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 3px solid #2563eb; }
.company-name { font-size: 22px; font-weight: bold; color: #2563eb; }
.logo-img { max-height: 60px; max-width: 160px; }
.report-title { text-align: center; margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #22d3ee, #2563eb); color: white; border-radius: 8px; font-size: 18px; font-weight: bold; }
.filters-info { background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #2563eb; }
.filter-item { margin: 4px 0; font-size: 11px; color: #555; }
.filter-label { font-weight: bold; color: #2563eb; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 9px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
th { background: linear-gradient(135deg, #22d3ee, #2563eb); color: white; padding: 10px; text-align: left; font-weight: 600; font-size: 9px; }
td { padding: 8px; border-bottom: 1px solid #e9ecef; vertical-align: top; }
tr:nth-child(even) { background-color: #f8f9fa; }
.summary { margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 8px; border-left: 4px solid #17a2b8; }
.summary-item { display: flex; justify-content: space-between; margin: 5px 0; font-size: 11px; }
.summary-label { font-weight: bold; color: #2563eb; }
.footer { text-align: center; font-size: 9px; color: #666; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; }
</style>
</head>
<body>
<div class="header-content">
  <div class="company-name">NextGen Distributors</div>
  <div><img src="' . $logoBase64 . '" class="logo-img" alt="Logo"></div>
</div>

<div class="report-title">REPORTE DE MOVIMIENTOS DE SALIDA</div>

<div class="filters-info">
  <div class="filter-item"><span class="filter-label">Fecha de generación:</span> ' . date('d/m/Y H:i:s') . '</div>
  <div class="filter-item"><span class="filter-label">Período:</span> ' . $fechaInicio . ' - ' . $fechaFin . '</div>';
if (!empty($search)) {
    $html .= '<div class="filter-item"><span class="filter-label">Producto o código:</span> ' . htmlspecialchars($search) . '</div>';
}
$html .= '</div>';

if (count($movimientos) > 0) {
    $totalSalidas = 0;
    foreach ($movimientos as $m) {
        $totalSalidas += $m['cantidad'];
    }

    $html .= '<table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Código</th>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Cantidad</th>
        <th>Fecha</th>
        <th>Motivo</th>
        <th>Usuario</th>
      </tr>
    </thead>
    <tbody>';
    foreach ($movimientos as $m) {
        $html .= '<tr>
          <td>' . htmlspecialchars($m['id']) . '</td>
          <td>' . htmlspecialchars($m['codigo']) . '</td>
          <td>' . htmlspecialchars($m['nombre_producto']) . '</td>
          <td>' . htmlspecialchars($m['nombre_categoria']) . '</td>
          <td>' . htmlspecialchars($m['cantidad']) . '</td>
          <td>' . htmlspecialchars($m['fecha_hora']) . '</td>
          <td>' . htmlspecialchars($m['descripcion']) . '</td>
          <td>' . htmlspecialchars($m['usuario_movimiento']) . '</td>
        </tr>';
    }
    $html .= '</tbody></table>';

    $html .= '<div class="summary">
      <div class="summary-item">
        <span class="summary-label">📊 Total de registros:</span>
        <span>' . count($movimientos) . '</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">⬇️ Total salidas:</span>
        <span>' . number_format($totalSalidas, 2) . '</span>
      </div>
    </div>';
} else {
    $html .= '<div class="summary"><p>No se encontraron movimientos de salida.</p></div>';
}

$html .= '<div class="footer">
  Página {PAGENO} de {nb} | Generado el ' . date('d/m/Y H:i:s') . ' | © ' . date('Y') . ' NextGen Distributors
</div>
</body>
</html>';

// Renderizar PDF
$mpdf->WriteHTML($html);
$mpdf->Output('reporte_salidas_' . date('Ymd_His') . '.pdf', 'I');
?>
