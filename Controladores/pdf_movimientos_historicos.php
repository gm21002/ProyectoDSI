<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../Modelos/Conexion.php';
date_default_timezone_set('America/El_Salvador');

session_start();

// Obtener parámetros por POST (TODOS los datos)
$tipo_auditoria = $_POST['tipo_auditoria'] ?? 'mensual';
$fecha_corte = $_POST['fecha_corte'] ?? date('Y-m-d');
$datos_historico_json = $_POST['datos_historico'] ?? '[]';
$total_registros = $_POST['total_registros'] ?? 0;

// Decodificar TODOS los datos
$todos_historicos = json_decode($datos_historico_json, true);

// Calcular rango de fechas para mostrar en el PDF
$fecha_inicio = '';
switch ($tipo_auditoria) {
    case 'mensual':
        $fecha_inicio = date('Y-m-d', strtotime($fecha_corte . ' -1 month'));
        break;
    case 'trimestral':
        $fecha_inicio = date('Y-m-d', strtotime($fecha_corte . ' -3 months'));
        break;
    default:
        $fecha_inicio = date('Y-m-d', strtotime($fecha_corte . ' -1 month'));
        break;
}

try {
    // Configurar mPDF
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
        'useSubstitutions' => true,
    ]);

    // Logo de la empresa
    $logoPath = './logo.png';
    $logoBase64 = '';
    
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }

    // HTML del PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Completo de Stock Histórico - Sistema de Inventario</title>
        <style>
            @page {
                header: html_Header;
                footer: html_Footer;
            }
            body { 
                font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif;
                color: #333;
                line-height: 1.4;
            }
            .container {
                width: 100%;
                margin: 0 auto;
            }
            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 3px solid #2c5aa0;
            }
            .company-info {
                flex: 2;
            }
.logo-container {
    flex: 1;
    text-align: right;
    margin-top: -60px; /* Ajusta este valor según necesites */
}
            .logo-img {
                max-height: 60px !important;
                max-width: 160px !important;
                width: auto;
                height: auto;
                object-fit: contain;
            }
            .logo-text {
                font-size: 18px;
                font-weight: bold;
                color: #2c5aa0;
                padding: 10px;
                border: 2px solid #2c5aa0;
                border-radius: 5px;
                display: inline-block;
            }
            .company-name {
                font-size: 22px;
                font-weight: bold;
                color: #2c5aa0;
                margin: 0;
            }
            .company-details {
                font-size: 11px;
                color: #666;
                margin: 2px 0;
            }
            .report-title {
                text-align: center;
                margin: 20px 0;
                padding: 15px;
                background: linear-gradient(135deg, #2c5aa0 0%, #2c5aa0 100%);
                color: white;
                border-radius: 8px;
                font-size: 18px;
                font-weight: bold;
            }
            .periodo-info {
                background: #f8f9fa;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
                border-left: 4px solid #2c5aa0;
            }
            .periodo-item {
                margin: 4px 0;
                font-size: 11px;
                color: #555;
            }
            .periodo-label {
                font-weight: bold;
                color: #2c5aa0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                font-size: 9px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-radius: 8px;
                overflow: hidden;
            }
            th {
                background: linear-gradient(135deg, #2c5aa0 0%, #2c5aa0 100%);
                color: white;
                padding: 10px;
                text-align: left;
                font-weight: 600;
                font-size: 9px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            td {
                padding: 8px;
                border-bottom: 1px solid #e9ecef;
                vertical-align: top;
            }
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 8px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .badge-consistente {
                background: #d4edda;
                color: #155724;
            }
            .badge-inconsistente {
                background: #f8d7da;
                color: #721c24;
            }
            .badge-sin-historia {
                background: #fff3cd;
                color: #856404;
            }
            .text-positive { 
                color: #28a745;
                font-weight: bold;
            }
            .text-negative { 
                color: #dc3545;
                font-weight: bold;
            }
            .text-warning { 
                color: #ffc107;
                font-weight: bold;
            }
            .summary {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #2c5aa0;
            }
            .summary-item {
                display: flex;
                justify-content: space-between;
                margin: 5px 0;
                font-size: 11px;
            }
            .summary-label {
                font-weight: bold;
                color: #2c5aa0;
            }
            .footer {
                text-align: center;
                font-size: 9px;
                color: #666;
                margin-top: 30px;
                padding-top: 10px;
                border-top: 1px solid #ddd;
            }
            .no-data {
                text-align: center;
                padding: 40px;
                color: #6c757d;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header Content -->
            <htmlpageheader name="Header">
                <div class="header-content">
                    <div class="company-info">
                        <div class="company-name">NextGen Distributors</div>
                        <div class="company-details">Sistema de Gestión de Inventarios</div>
                        <div class="company-details">Reporte Completo de Stock Histórico</div>
                    </div>';
    
    if ($logoBase64) {
        $html .= '
                    <div class="logo-container">
                        <img src="' . $logoBase64 . '" class="logo-img" alt="Logo de la Empresa">
                    </div>';
    } else {
        $html .= '
                    <div class="logo-container">
                        <div class="logo-text">📊 HISTÓRICO COMPLETO</div>
                    </div>';
    }
    
    $html .= '
                </div>
            </htmlpageheader>

            <!-- Footer Content -->
            <htmlpagefooter name="Footer">
                <div class="footer">
                    Página {PAGENO} de {nb} | Generado el ' . date('d/m/Y H:i:s') . ' | 
                    © ' . date('Y') . ' NextGen Distributors. Todos los derechos reservados.
                </div>
            </htmlpagefooter>

            <div class="report-title">
                REPORTE COMPLETO: STOCK ACTUAL VS HISTÓRICO
            </div>

            <div class="periodo-info">
                <div class="periodo-item"><span class="periodo-label">Fecha de generación:</span> ' . date('d/m/Y H:i:s') . '</div>
                <div class="periodo-item"><span class="periodo-label">Tipo de auditoría:</span> ' . ucfirst($tipo_auditoria) . '</div>
                <div class="periodo-item"><span class="periodo-label">Fecha de corte:</span> ' . $fecha_corte . '</div>
                <div class="periodo-item"><span class="periodo-label">Total de productos analizados:</span> ' . count($todos_historicos) . '</div>
            </div>';

    if (count($todos_historicos) > 0) {
        // Calcular estadísticas de TODOS los datos
        $total_productos = count($todos_historicos);
        $consistentes = 0;
        $inconsistentes = 0;
        $sin_historico = 0;
        $total_variacion = 0;
        
        foreach ($todos_historicos as $row) {
            if ($row['estado'] == 'CONSISTENTE') $consistentes++;
            if ($row['estado'] == 'INCONSISTENTE') $inconsistentes++;
            if ($row['estado'] == 'SIN_HISTORICO') $sin_historico++;
            $total_variacion += $row['diferencia_porcentaje'];
        }

        $promedio_variacion = $total_productos > 0 ? $total_variacion / $total_productos : 0;

        $html .= '
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">📦 Total de productos analizados:</span>
                    <span>' . $total_productos . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">✅ Productos consistentes:</span>
                    <span>' . $consistentes . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">❌ Productos inconsistentes:</span>
                    <span>' . $inconsistentes . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">📊 Variación promedio:</span>
                    <span>' . number_format($promedio_variacion, 1) . '%</span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="25%">Producto</th>
                        <th width="12%">Stock Actual</th>
                        <th width="12%">Stock Histórico</th>
                        <th width="10%">Diferencia</th>
                        <th width="12%">% Variación</th>
                        <th width="19%">Observaciones</th>
                        <th width="10%">Estado</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($todos_historicos as $row) {
            $estadoClass = 'badge-' . strtolower($row['estado']);
            $variacionClass = $row['diferencia_porcentaje'] > 30 ? 'text-negative' : 
                             ($row['diferencia_porcentaje'] > 10 ? 'text-warning' : 'text-positive');
            
            $html .= '
                    <tr>
                        <td><strong>' . htmlspecialchars($row['producto']) . '</strong></td>
                        <td class="text-right">' . htmlspecialchars($row['stock_actual']) . '</td>
                        <td class="text-right">' . htmlspecialchars($row['stock_historico']) . '</td>
                        <td class="text-right">' . number_format($row['diferencia_absoluta'], 1) . '</td>
                        <td class="text-right ' . $variacionClass . '">' . number_format($row['diferencia_porcentaje'], 1) . '%</td>
                        <td>' . htmlspecialchars($row['observaciones']) . '</td>
                        <td><span class="badge ' . $estadoClass . '">' . htmlspecialchars($row['estado']) . '</span></td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>';
    } else {
        $html .= '
            <div class="no-data">
                <h3>No hay datos históricos disponibles</h3>
                <p>No se encontraron registros de inventario con datos históricos para generar el reporte.</p>
            </div>';
    }

    $html .= '
        </div>
    </body>
    </html>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('stock_historico_completo_' . date('Ymd_His') . '.pdf', 'I');

} catch (Exception $e) {
    echo "Error al generar PDF: " . $e->getMessage();
}
?>