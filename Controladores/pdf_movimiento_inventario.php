<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../Modelos/Conexion.php';
require_once '../Modelos/AuditoriaModel.php';
date_default_timezone_set('America/El_Salvador');

session_start();

// Obtener filtros de la sesión o POST
$filtros = $_SESSION['ultimos_filtros'] ?? [
    'producto' => '',
    'usuario' => '',
    'tipo' => '',
    'desde' => '',
    'hasta' => ''
];

try {
    // Obtener datos con los mismos filtros
    $model = new AuditoriaModel();
    $movimientos = $model->obtenerMovimientos($filtros);

    // Configurar mPDF con fuentes que soporten símbolos
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
        'useSubstitutions' => true, // Habilitar sustituciones de fuentes
    ]);

    // Logo de la empresa - USAR RUTA ABSOLUTA
    $logoPath = './logo.png';
    $logoBase64 = '';
    
if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        
        // Obtener dimensiones reales de la imagen para ajustar proporción
        list($width, $height) = getimagesize($logoPath);
        $ratio = $height / $width;
        $newHeight = 60; // Altura fija deseada
        $newWidth = $newHeight / $ratio;
    }

    // HTML del PDF con símbolos compatibles
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Auditoría - Sistema de Inventario</title>
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
            .logo {
                flex: 1;
                text-align: right;
            }
            .logo img {
                max-height: 60px;
                max-width: 180px;
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
                background: linear-gradient(135deg, #2c5aa0 0%, #1e3a6b 100%);
                color: white;
                border-radius: 8px;
                font-size: 18px;
                font-weight: bold;
            }
            .filters-info {
                background: #f8f9fa;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
                border-left: 4px solid #2c5aa0;
            }
            .filter-item {
                margin: 4px 0;
                font-size: 11px;
                color: #555;
            }
            .filter-label {
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
                background: linear-gradient(135deg, #2c5aa0 0%, #1e3a6b 100%);
                color: white;
                padding: 10px;
                text-align: left;
                font-weight: 600;
                font-size: 9px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-family: "DejaVu Sans", "Arial", sans-serif;
            }
            td {
                padding: 8px;
                border-bottom: 1px solid #e9ecef;
                vertical-align: top;
                font-family: "DejaVu Sans", "Arial", sans-serif;
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
                font-family: "DejaVu Sans", "Arial", sans-serif;
            }
            .badge-entrada {
                background: #d4edda;
                color: #155724;
            }
            .badge-salida {
                background: #f8d7da;
                color: #721c24;
            }
            .badge-ajuste {
                background: #fff3cd;
                color: #856404;
            }
            .summary {
                margin-top: 20px;
                padding: 15px;
                background: #e8f4f8;
                border-radius: 8px;
                border-left: 4px solid #17a2b8;
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
                font-family: "DejaVu Sans", "Arial", sans-serif;
            }
            .no-data {
                text-align: center;
                padding: 40px;
                color: #6c757d;
            }
            .icon {
                font-family: "DejaVu Sans", "Webdings", "Wingdings", "Arial", sans-serif;
                margin-right: 5px;
            }
            .symbol {
                font-family: "DejaVu Sans", "Arial", sans-serif;
                font-weight: bold;
                margin-right: 5px;
                color: #2c5aa0;
            }

            .logo-container {
            flex: 1;
            text-align: right;
            margin-top: -60px;
        }
        .logo-img {
            max-height: 60px !important; /* Máximo 60px de alto */
            max-width: 160px !important; /* Máximo 180px de ancho */
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
                        <div class="company-details">Reporte de Auditoría y Movimientos</div>
                    </div>';
    
    if ($logoBase64) {
        $html .= '
                    <div class="logo-container">
                        <img src="' . $logoBase64 . '" class="logo-img" alt="Logo de la Empresa">
                    </div>';
    } else {
        $html .= '
                    <div class="logo-container">
                        <div class="logo-text">LOGO</div>
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
                REPORTE DETALLADO DE MOVIMIENTOS DE INVENTARIO
            </div>

            <div class="filters-info">
                <div class="filter-item"><span class="filter-label">Fecha de generación:</span> ' . date('d/m/Y H:i:s') . '</div>';
    
    if (!empty($filtros['desde']) || !empty($filtros['hasta'])) {
        $html .= '<div class="filter-item"><span class="filter-label">Período:</span> ' . $filtros['desde'] . ' - ' . $filtros['hasta'] . '</div>';
    }
    if (!empty($filtros['producto'])) {
        $html .= '<div class="filter-item"><span class="filter-label">Producto:</span> ' . htmlspecialchars($filtros['producto']) . '</div>';
    }
    if (!empty($filtros['usuario'])) {
        $html .= '<div class="filter-item"><span class="filter-label">Usuario:</span> ' . htmlspecialchars($filtros['usuario']) . '</div>';
    }
    if (!empty($filtros['tipo'])) {
        $html .= '<div class="filter-item"><span class="filter-label">Tipo de movimiento:</span> ' . htmlspecialchars($filtros['tipo']) . '</div>';
    }

    $html .= '
            </div>';

    if (count($movimientos) > 0) {
        // Calcular totales
        $totalEntradas = 0;
        $totalSalidas = 0;
        
        foreach ($movimientos as $mov) {
            if ($mov['tipo'] === 'entrada') {
                $totalEntradas += $mov['cantidad'];
            } elseif ($mov['tipo'] === 'salida') {
                $totalSalidas += $mov['cantidad'];
            }
        }

        $html .= '
            <table>
                <thead>
                    <tr>
                        <th width="15%"><span class="symbol">■</span> Fecha/Hora</th>
                        <th width="10%"><span class="symbol">■</span> Tipo</th>
                        <th width="25%"><span class="symbol">■</span> Producto</th>
                        <th width="10%"><span class="symbol">■</span> Cantidad</th>
                        <th width="15%"><span class="symbol">■</span> Usuario</th>
                        <th width="25%"><span class="symbol">■</span> Motivo/Descripción</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($movimientos as $movimiento) {
            $badgeClass = 'badge-' . $movimiento['tipo'];
            
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($movimiento['fecha']) . '</td>
                        <td><span class="badge ' . $badgeClass . '">' . strtoupper(htmlspecialchars($movimiento['tipo'])) . '</span></td>
                        <td>' . htmlspecialchars($movimiento['producto']) . '</td>
                        <td class="text-right">' . number_format($movimiento['cantidad'], 2) . '</td>
                        <td>' . htmlspecialchars($movimiento['usuario']) . '</td>
                        <td>' . htmlspecialchars($movimiento['motivo']) . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">📊 Total de registros:</span>
                    <span>' . count($movimientos) . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">⬆️ Total entradas:</span>
                    <span>' . number_format($totalEntradas, 2) . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">⬇️ Total salidas:</span>
                    <span>' . number_format($totalSalidas, 2) . '</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">⚖️ Balance neto:</span>
                    <span>' . number_format(($totalEntradas - $totalSalidas), 2) . '</span>
                </div>
            </div>';
    } else {
        $html .= '
            <div class="no-data">
                <h3>No se encontraron movimientos</h3>
                <p>No hay registros que coincidan con los criterios de búsqueda aplicados.</p>
            </div>';
    }

    $html .= '
        </div>
    </body>
    </html>';

    // Configurar header y footer
    $mpdf->SetHTMLHeader('
        <div style="text-align: center; font-size: 10px; color: #666; border-bottom: 1px solid #ddd; padding: 5px;">
            Reporte de Auditoría - Página {PAGENO} de {nb}
        </div>
    ');
    
    $mpdf->SetHTMLFooter('
        <div style="text-align: center; font-size: 8px; color: #666; border-top: 1px solid #ddd; padding: 5px;">
            © ' . date('Y') . ' Nombre de la Empresa - Página {PAGENO} de {nb}
        </div>
    ');
    
    $mpdf->WriteHTML($html);
    $mpdf->Output('auditoria_inventario_' . date('Ymd_His') . '.pdf', 'I');

} catch (Exception $e) {
    echo "Error al generar PDF: " . $e->getMessage();
}
?>