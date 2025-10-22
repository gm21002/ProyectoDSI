<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../Modelos/Conexion.php';
date_default_timezone_set('America/El_Salvador');

session_start();

// Obtener parámetros por POST (TODOS los datos)
$tipo_auditoria = $_POST['tipo_auditoria'] ?? 'mensual';
$fecha_corte = $_POST['fecha_corte'] ?? date('Y-m-d');
$datos_atipicos_json = $_POST['datos_atipicos'] ?? '[]';
$total_registros = $_POST['total_registros'] ?? 0;

// Decodificar TODOS los datos
$todos_movimientos_atipicos = json_decode($datos_atipicos_json, true);

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
        <title>Reporte Completo de Movimientos Atípicos - Sistema de Inventario</title>
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
                max-height: 60px;
                max-width: 180px;
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
            .badge-entrada {
                background: #d4edda;
                color: #155724;
            }
            .badge-salida {
                background: #f8d7da;
                color: #721c24;
            }
            .badge-horario {
                background: #fff3cd;
                color: #856404;
            }
            .badge-cantidad {
                background: #f8d7da;
                color: #721c24;
            }
            .badge-frecuencia {
                background: #d1ecf1;
                color: #0c5460;
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
                        <div class="company-details">Reporte Completo de Movimientos Atípicos</div>
                    </div>';
    
    if ($logoBase64) {
        $html .= '
                    <div class="logo-container">
                        <img src="' . $logoBase64 . '" class="logo-img" alt="Logo de la Empresa">
                    </div>';
    } else {
        $html .= '
                    <div class="logo-container">
                        <div class="logo-text">⚠ ALERTAS COMPLETAS</div>
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
                REPORTE COMPLETO DE MOVIMIENTOS ATÍPICOS DETECTADOS
            </div>

            <div class="periodo-info">
                <div class="periodo-item"><span class="periodo-label">Fecha de generación:</span> ' . date('d/m/Y H:i:s') . '</div>
                <div class="periodo-item"><span class="periodo-label">Período de auditoría:</span> ' . ucfirst($tipo_auditoria) . '</div>
                <div class="periodo-item"><span class="periodo-label">Fecha de inicio:</span> ' . $fecha_inicio . '</div>
                <div class="periodo-item"><span class="periodo-label">Fecha de corte:</span> ' . $fecha_corte . '</div>
                <div class="periodo-item"><span class="periodo-label">Total de registros:</span> ' . count($todos_movimientos_atipicos) . '</div>
            </div>';

    if (count($todos_movimientos_atipicos) > 0) {
        // Calcular estadísticas de TODOS los datos
        $total_atipicos = count($todos_movimientos_atipicos);
        $horario_no_laboral = 0;
        $cantidad_excesiva = 0;
        $frecuencia_alta = 0;
        
        foreach ($todos_movimientos_atipicos as $mov) {
            if ($mov['tipo_atipico'] == 'HORARIO_NO_LABORAL') $horario_no_laboral++;
            if ($mov['tipo_atipico'] == 'CANTIDAD_EXCESIVA') $cantidad_excesiva++;
            if ($mov['tipo_atipico'] == 'FRECUENCIA_ALTA') $frecuencia_alta++;
        }

        $html .= '
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">📊 Total de movimientos atípicos:</span>
                    <span>' . $total_atipicos . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">⏰ Horario no laboral:</span>
                    <span>' . $horario_no_laboral . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">📦 Cantidad excesiva:</span>
                    <span>' . $cantidad_excesiva . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">🔄 Frecuencia alta:</span>
                    <span>' . $frecuencia_alta . '</span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="20%">Producto</th>
                        <th width="8%">Cantidad</th>
                        <th width="8%">Tipo</th>
                        <th width="15%">Fecha/Hora</th>
                        <th width="25%">Descripción</th>
                        <th width="12%">Tipo Atípico</th>
                        <th width="12%">Regla Aplicada</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($todos_movimientos_atipicos as $movimiento) {
            // Determinar clase del badge según el tipo atípico
            $badgeClass = '';
            switch ($movimiento['tipo_atipico']) {
                case 'HORARIO_NO_LABORAL':
                    $badgeClass = 'badge-horario';
                    break;
                case 'CANTIDAD_EXCESIVA':
                    $badgeClass = 'badge-cantidad';
                    break;
                case 'FRECUENCIA_ALTA':
                    $badgeClass = 'badge-frecuencia';
                    break;
                default:
                    $badgeClass = 'badge-horario';
            }
            
            $tipoBadgeClass = $movimiento['tipo'] == 'entrada' ? 'badge-entrada' : 'badge-salida';
            
            $html .= '
                    <tr>
                        <td><strong>' . htmlspecialchars($movimiento['producto']) . '</strong></td>
                        <td class="text-right">' . htmlspecialchars($movimiento['cantidad']) . '</td>
                        <td><span class="badge ' . $tipoBadgeClass . '">' . strtoupper($movimiento['tipo']) . '</span></td>
                        <td>' . htmlspecialchars($movimiento['fecha']) . '</td>
                        <td>' . htmlspecialchars($movimiento['descripcion']) . '</td>
                        <td><span class="badge ' . $badgeClass . '">' . htmlspecialchars($movimiento['tipo_atipico']) . '</span></td>
                        <td><small>' . htmlspecialchars($movimiento['regla']) . '</small></td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>';
    } else {
        $html .= '
            <div class="no-data">
                <h3>No se detectaron movimientos atípicos</h3>
                <p>No se encontraron movimientos fuera de los parámetros normales en el período seleccionado.</p>
                <p><strong>Período analizado:</strong> ' . $fecha_inicio . ' al ' . $fecha_corte . '</p>
            </div>';
    }

    $html .= '
        </div>
    </body>
    </html>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('movimientos_atipicos_completo_' . date('Ymd_His') . '.pdf', 'I');

} catch (Exception $e) {
    echo "Error al generar PDF: " . $e->getMessage();
}
?>