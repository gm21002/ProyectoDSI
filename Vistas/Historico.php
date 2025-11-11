<?php
session_start();
require_once '../Modelos/Conexion.php';
$correo = $_SESSION['usuario_correo'];

// Manejar el estado de las pestañas
if (isset($_GET['tab_activa'])) {
    $_SESSION['tab_activa'] = $_GET['tab_activa'];
}
$tab_activa = $_SESSION['tab_activa'] ?? 'historico';

// Obtener la instancia de la conexión
$conexion = Conexion::getInstance();
$conn = $conexion->getConnection();

// Obtener datos históricos
$historico_data = [];
$movimientos_atipicos = [];
$mensaje = '';
$tipo_auditoria = $_GET['tipo_auditoria'] ?? '';
$fecha_corte = $_GET['fecha_corte'] ?? date('Y-m-d'); // Fecha actual por defecto

// Procesar formulario de reporte
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generar_reporte'])) {
    $tipo_auditoria = $_GET['tipo_auditoria'] ?? '';
    $fecha_corte = $_GET['fecha_corte'] ?? date('Y-m-d');
    
    // Validaciones
    if (empty($tipo_auditoria) || empty($fecha_corte)) {
        $mensaje = "Debe seleccionar un período válido.";
    } else {
        // Calcular rango de fechas
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
        
        // Validar que las fechas sean válidas
        if (strtotime($fecha_inicio) === false || strtotime($fecha_corte) === false) {
            $mensaje = "Las fechas seleccionadas no son válidas.";
        } else {
            // Generar reporte histórico con filtro de fechas
            $historico_data = generarReporteHistorico($conn, $fecha_inicio, $fecha_corte);
            
            // Detectar movimientos atípicos con el rango de fechas
            $movimientos_atipicos = detectarMovimientosAtipicos($conn, $fecha_inicio, $fecha_corte);
            
            if (empty($historico_data)) {
                $mensaje = "No hay datos históricos suficientes para generar el reporte.";
            } else {
                $mensaje = "Reporte generado correctamente para el período " . 
                          ($tipo_auditoria == 'mensual' ? 'mensual' : 'trimestral') . 
                          " (Del $fecha_inicio al $fecha_corte)";
                
                if (!empty($movimientos_atipicos)) {
                    $mensaje .= " - Se detectaron " . count($movimientos_atipicos) . " movimientos atípicos";
                }
            }
        }
    }
} else {
    // Cargar datos por defecto (sin filtro de fechas)
    $historico_data = generarReporteHistorico($conn);
    $movimientos_atipicos = detectarMovimientosAtipicos($conn);
}

function generarReporteHistorico($conn, $fecha_inicio = null, $fecha_fin = null) {
    // Si se proporcionan fechas, filtrar por movimientos en ese rango
    $filtro_fechas = "";
    $params = [];
    
    if ($fecha_inicio && $fecha_fin) {
        $filtro_fechas = " AND EXISTS (
            SELECT 1 FROM movimientos m 
            WHERE m.producto_id = i.producto_id 
            AND DATE(m.fecha) BETWEEN :fecha_inicio AND :fecha_fin
        )";
        $params[':fecha_inicio'] = $fecha_inicio;
        $params[':fecha_fin'] = $fecha_fin;
    }
    
    $query = "
        SELECT 
            p.nombre as producto,
            i.cantidad_stock as stock_actual,
            i.stock_historico,
            ABS(i.cantidad_stock - i.stock_historico) as diferencia_absoluta,
            CASE 
                WHEN i.cantidad_stock = 0 THEN 0
                ELSE ABS((i.cantidad_stock - i.stock_historico) / i.cantidad_stock * 100)
            END as diferencia_porcentaje,
            CASE 
                WHEN i.stock_historico IS NULL OR i.stock_historico = 0 THEN 'SIN_HISTORICO'
                WHEN ABS(i.cantidad_stock - i.stock_historico) > (i.cantidad_stock * 0.3) THEN 'INCONSISTENTE'
                ELSE 'CONSISTENTE'
            END as estado,
            CONCAT('Variación: ', 
                   CASE 
                       WHEN i.cantidad_stock > i.stock_historico THEN 'Aumento'
                       WHEN i.cantidad_stock < i.stock_historico THEN 'Disminución'
                       ELSE 'Sin cambio'
                   END
            ) as observaciones
        FROM inventario i
        INNER JOIN productos p ON i.producto_id = p.id
        WHERE i.stock_historico IS NOT NULL
        $filtro_fechas
        ORDER BY diferencia_porcentaje DESC
    ";
    
    $stmt = $conn->prepare($query);
    
    // Si hay parámetros de fecha, bind them
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function detectarMovimientosAtipicos($conn, $fecha_inicio = null, $fecha_fin = null) {
    if (!$fecha_inicio || !$fecha_fin) {
        $fecha_fin = date('Y-m-d');
        $fecha_inicio = date('Y-m-d', strtotime('-90 days'));
    }
    
    $query = "
        SELECT 
            p.nombre as producto,
            m.cantidad,
            m.tipo,
            m.fecha,
            m.descripcion,
            CASE 
                -- REGLA 1: Cantidad muy superior al promedio (2.5 veces)
                WHEN m.cantidad > (
                    SELECT COALESCE(AVG(m2.cantidad) * 2.5, m.cantidad + 1) 
                    FROM movimientos m2 
                    WHERE m2.producto_id = m.producto_id 
                    AND m2.tipo = m.tipo
                    AND DATE(m2.fecha) BETWEEN :fecha_inicio AND :fecha_fin
                    AND m2.id != m.id
                ) THEN 'CANTIDAD_EXCESIVA'
                
                -- REGLA 2: Frecuencia muy alta (más de 4 movimientos en 3 días)
                WHEN (
                    SELECT COUNT(*) 
                    FROM movimientos m3 
                    WHERE m3.producto_id = m.producto_id 
                    AND DATE(m3.fecha) BETWEEN DATE_SUB(DATE(m.fecha), INTERVAL 3 DAY) AND DATE(m.fecha)
                    AND DATE(m3.fecha) BETWEEN :fecha_inicio AND :fecha_fin
                    AND m3.id != m.id
                ) >= 4 THEN 'FRECUENCIA_ALTA'
                
                -- REGLA 3: Movimientos fuera de horario laboral (6 AM - 10 PM)
                WHEN HOUR(m.fecha) < 6 OR HOUR(m.fecha) > 22 THEN 'HORARIO_NO_LABORAL'
                
                ELSE 'NORMAL'
            END as tipo_atipico,
            
            CASE 
                WHEN m.cantidad > (
                    SELECT COALESCE(AVG(m2.cantidad) * 2.5, m.cantidad + 1) 
                    FROM movimientos m2 
                    WHERE m2.producto_id = m.producto_id 
                    AND m2.tipo = m.tipo
                    AND DATE(m2.fecha) BETWEEN :fecha_inicio AND :fecha_fin
                    AND m2.id != m.id
                ) THEN CONCAT('Cantidad (', m.cantidad, ') excede 2.5 veces el promedio histórico')
                
                WHEN (
                    SELECT COUNT(*) 
                    FROM movimientos m3 
                    WHERE m3.producto_id = m.producto_id 
                    AND DATE(m3.fecha) BETWEEN DATE_SUB(DATE(m.fecha), INTERVAL 3 DAY) AND DATE(m.fecha)
                    AND DATE(m3.fecha) BETWEEN :fecha_inicio AND :fecha_fin
                    AND m3.id != m.id
                ) >= 4 THEN 'Más de 4 movimientos en 3 días consecutivos'
                
                WHEN HOUR(m.fecha) < 6 OR HOUR(m.fecha) > 22 THEN CONCAT('Movimiento fuera de horario laboral (', HOUR(m.fecha), ':00 hrs)')
                
                ELSE 'Movimiento normal'
            END as regla
        FROM movimientos m
        INNER JOIN productos p ON m.producto_id = p.id
        WHERE DATE(m.fecha) BETWEEN :fecha_inicio AND :fecha_fin
        HAVING tipo_atipico != 'NORMAL'
        ORDER BY m.fecha DESC
    ";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en detectarMovimientosAtipicos: " . $e->getMessage());
        return [];
    }
}

// Obtener parámetros de paginación
$pagina_historico = $_GET['pagina_historico'] ?? 1;
$pagina_atipicos = $_GET['pagina_atipicos'] ?? 1;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reporte de Auditoría - Histórico</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');
    * { box-sizing: border-box; }
    body, html {
      margin: 0;
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: #0a2540;
      color: #f0f4f8;
    }
    
    /* ESTILOS PARA PAGINACIÓN - SOLO BOTONES */
.pagination-container {
    margin-top: 0px;
    padding: -40px 0;
}

.pagination {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
    color: #e0e7ff;
}

.page-item {
    margin: 0;
}

.button-container {
    display: flex;
    gap: 12px;
    margin-bottom: 10px;
    margin-top: -5px; /* Reducido de 10px a 5px */
}

.page-link {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #cbd5e1;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
}

.page-link:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #22d3ee, #2563eb);
    border-color: #22d3ee;
    color: white;
    box-shadow: 0 4px 15px rgba(34, 211, 238, 0.3);
    transform: translateY(-2px);
}

.page-item.disabled .page-link {
    background: rgba(214, 31, 31, 0.05);
    color: #ffffffff;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.page-item.disabled .page-link:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: none;
    box-shadow: none;
}

/* Contador de registros - sin fondo */
.pagination-info {
    text-align: center;
    font-size: 0.85rem;
    color: #ffffffff;
    font-weight: 500;
    margin-top: 10px;
}

/* Iconos en botones */
.page-link i {
    font-size: 0.8rem;
}

/* Efectos especiales para números */
.page-link:not(.active):hover {
    background: linear-gradient(135deg, rgba(34, 211, 238, 0.2), rgba(37, 99, 235, 0.2));
}

/* Responsive */
@media (max-width: 768px) {
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px;
    }
    
    .page-link {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
    
    .pagination-info {
        font-size: 0.8rem;
    }
}

/* Efecto de brillo en hover */
.page-link:hover {
    filter: brightness(1.2);
}

    .background-image {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      object-fit: cover;
      z-index: -1;
      filter: brightness(0.7);
    }

    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 240px;
      background: rgba(255 255 255 / 0.05);
      backdrop-filter: blur(8px);
      padding: 32px 16px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar h2 {
      font-size: 1.5rem;
      margin-bottom: 32px;
      text-align: center;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 800;
    }

    .sidebar a {
      text-decoration: none;
      color: #cbd5e1;
      display: flex;
      align-items: center;
      padding: 12px 16px;
      border-radius: 8px;
      transition: background 0.3s ease;
      font-weight: 600;
    }

    .sidebar a:hover {
      background: rgba(255 255 255 / 0.1);
    }

    .sidebar a i {
      margin-right: 12px;
      font-size: 1.2rem;
    }

    /* Submenú */
    .menu-item {
      display: flex;
      flex-direction: column;
    }
    .menu-toggle {
      background: none;
      border: none;
      color: inherit;
      font: inherit;
      padding: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      border-radius: 8px;
      font-weight: 600;
      transition: background 0.3s ease;
    }
    .menu-toggle:hover {
      background: rgba(255, 255, 255, 0.1);
    }
    .chevron {
      margin-left: auto;
      transition: transform 0.3s;
    }
    .submenu {
      display: none;
      flex-direction: column;
      margin-left: 24px;
      margin-top: 8px;
    }
    .submenu a {
      padding: 8px 16px;
      border-radius: 6px;
      color: #cbd5e1;
      font-weight: 500;
      text-decoration: none;
      transition: background 0.3s ease;
    }
    .submenu a:hover,
    .submenu a.active {
      background: rgba(255, 255, 255, 0.1);
    }
    .menu-item.open .submenu {
      display: flex;
    }
    .menu-item.open .chevron {
      transform: rotate(180deg);
    }

    .main-content {
      flex: 1;
      padding: 30px;
      display: flex;
      flex-direction: column;
    }
        .content {
      width: 100%;
      max-width: 1500px;
      margin: 0 auto;
      background: rgba(255 255 255 / 0.07);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.5);
      color: #cbd5e1;
    }


.header {
  display: flex;
  justify-content: center; /* Centra ambos elementos */
  align-items: center;
  width: 100%;
  padding: 0 20px;
  margin-bottom: 20px;
  gap: 900px; /* Controla la separación entre ellos */
}

.header h1 {
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, #22d3ee, #2563eb);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin: 0;
  text-align: center;
}

    .user-menu {
      position: relative;
      display: flex;
      justify-content: flex-end;
      padding: 10px 0;
    }

    .user-button {
      background: rgba(255 255 255 / 0.1);
      border: none;
      color: #f0f4f8;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 15px;
      font-weight: 200px;
      display: flex;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .user-button:hover {
      background: rgba(255 255 255 / 0.2);
    }

    .user-button i {
      margin-right: 8px;
    }

.dropdown {
  position: absolute;
  top: 48px;
  right: 0;
  background: rgba(255 255 255 / 0.07);
  backdrop-filter: blur(10px);
  border-radius: 8px; /* REDUCE el border-radius */
  padding: 6px 0; /* REDUCE el padding vertical */
  box-shadow: 0 8px 20px rgba(0,0,0,0.4); /* REDUCE la sombra */
  min-width: 140px; /* REDUCE el ancho mínimo */
  display: none;
  z-index: 1000;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 6px 12px; /* REDUCE el padding */
  color: #f0f4f8;
  text-decoration: none;
  font-weight: 500; /* REDUCE el peso de la fuente */
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s ease;
  font-size: 0.85rem; /* REDUCE el tamaño de fuente */
}

    /* Filtros */
    .filter-section {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
    }

    .filter-title {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: #f0f4f8;
    }

    .form-control, .form-select {
      background: rgba(255,255,255,0.1);
      color: #f0f4f8;
      border: none;
      height: 42px;
    }

    .form-control::placeholder {
      color: #cbd5e1;
      opacity: 0.8;
    }

    .form-control:focus, .form-select:focus {
      background: rgba(255,255,255,0.2);
      outline: 2px solid #22d3ee;
      color: white;
      box-shadow: none;
    }

    .btn-primary {
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      border: none;
      font-weight: 600;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(34, 211, 238, 0.3);
    }

    .btn-warning {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      border: none;
      font-weight: 600;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .button-container {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-bottom: 20px;
    }

    /* ESTILOS MEJORADOS PARA LAS TABLAS */
    .table-container {
      width: 100%;
      overflow-x: auto;
      margin-top: 20px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.05);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      color: #e0e7ff;
    }

    table thead {
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      color: white;
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    tbody tr {
      transition: background 0.3s ease;
    }

    tbody tr:nth-child(even) {
      background: rgba(255, 255, 255, 0.03);
    }

    tbody tr:hover {
      background: rgba(34, 211, 238, 0.15);
    }

    .badge {
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
    }
    
    .bg-success {
      background-color: rgba(40, 167, 69, 0.9) !important;
      color: white !important;
    }
    
    .bg-warning {
      background-color: rgba(255, 193, 7, 0.9) !important;
      color: #000 !important;
    }
    
    .bg-danger {
      background-color: rgba(220, 53, 69, 0.9) !important;
      color: white !important;
    }

    .bg-secondary {
      background-color: rgba(108, 117, 125, 0.9) !important;
      color: white !important;
    }

    .success-message {
      background: rgba(101, 218, 138, 0.15);
      color: #65da8a;
      padding: 12px 16px;
      border-radius: 8px;
      margin: 15px 0;
      font-weight: 600;
      border-left: 4px solid #65da8a;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert {
      background: rgba(255,255,255,0.1);
      color: #fbbf24;
      padding: 12px 16px;
      font-weight: 600;
      border: none;
      border-left: 4px solid #fbbf24;
      border-radius: 8px;
      margin: 15px 0;
    }

    .alert-danger {
      background: rgba(220, 53, 69, 0.15);
      color: #ff6b6b;
      border-left: 4px solid #ff6b6b;
    }

    .alert-info {
      background: rgba(13, 202, 240, 0.15);
      color: #0dcaf0;
      border-left: 4px solid #0dcaf0;
    }

    /* Estilos para porcentajes */
    .text-positive { color: #65da8a; }
    .text-negative { color: #ff6b6b; }
    .text-warning { color: #ffd93d; }

    /* Pestañas */
    .nav-tabs {
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .nav-tabs .nav-link {
      color: #cbd5e1;
      border: none;
      padding: 12px 24px;
      font-weight: 600;
    }

    .nav-tabs .nav-link.active {
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      color: white;
      border-radius: 8px 8px 0 0;
    }

    .nav-tabs .nav-link:hover {
      color: #22d3ee;
      border: none;
    }

    .tab-content {
      padding: 20px 0;
    }

    @media (max-width: 992px) {
      .dashboard-container {
        flex-direction: column;
      }
      .sidebar {
        width: 100%;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        padding: 20px 10px;
      }
      .main-content {
        padding: 20px;
      }
      .content {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a607f958-7370-428e-bc1b-2af0eb7d51dd.png"
       alt="Background"
       class="background-image"
       onerror="this.style.display='none'" />

  <div class="dashboard-container">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
      <header class="header">
        <h1>Histórico</h1>
                        <div class="user-menu" id="userMenu">
          <button class="user-button" onclick="toggleDropdown()">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($correo) ?>
          </button>
          <div class="dropdown" id="dropdownMenu">
            <form action="../Controladores/AuthController.php" method="post" style="margin:0">
              <button type="submit" name="logout" value="1" class="dropdown-item">
                <i class="bi bi-box-arrow-right me-2"></i>&nbsp; Cerrar sesión
              </button>
            </form>
          </div>
        </div>
      </header>

<section class="content">
    <!-- Filtros de Reporte de Auditoría -->
    <div class="filter-section">
        <div class="filter-title">Generar Reporte de Auditoría</div>
        <form method="GET" class="row g-3">
            <input type="hidden" name="generar_reporte" value="1">
            <div class="col-md-4">
                <select name="tipo_auditoria" class="form-select" required>
                    <option value="">Seleccionar período</option>
                    <option value="mensual" <?= $tipo_auditoria == 'mensual' ? 'selected' : '' ?>>Mensual</option>
                    <option value="trimestral" <?= $tipo_auditoria == 'trimestral' ? 'selected' : '' ?>>Trimestral</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="date" name="fecha_corte" class="form-control" value="<?= htmlspecialchars($fecha_corte) ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-gear-fill"></i> Generar Reporte
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($mensaje)): ?>
        <?php if (strpos($mensaje, 'correctamente') !== false): ?>
            <div class="success-message">
                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php elseif (strpos($mensaje, 'atípicos') !== false): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill"></i> <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Pestañas -->
    <ul class="nav nav-tabs" id="auditoriaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tab_activa == 'historico' ? 'active' : '' ?>" 
                    id="historico-tab" 
                    data-bs-toggle="tab" 
                    data-bs-target="#historico" 
                    type="button" 
                    role="tab"
                    onclick="guardarTabActiva('historico')">
                <i class="bi bi-graph-up"></i> Stock Histórico
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $tab_activa == 'atipicos' ? 'active' : '' ?>" 
                    id="atipicos-tab" 
                    data-bs-toggle="tab" 
                    data-bs-target="#atipicos" 
                    type="button" 
                    role="tab"
                    onclick="guardarTabActiva('atipicos')">
                <i class="bi bi-exclamation-triangle"></i> Movimientos Atípicos
                <?php if (!empty($movimientos_atipicos)): ?>
                    <span class="badge bg-danger ms-1"><?= count($movimientos_atipicos) ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="auditoriaTabsContent">
        <!-- Pestaña Stock Histórico -->
        <div class="tab-pane fade <?= $tab_activa == 'historico' ? 'show active' : '' ?>" id="historico" role="tabpanel">
            <?php if (!empty($historico_data)): ?>
                <?php
                // Configuración de paginación para histórico
                $registros_por_pagina = 5;
                $total_historico = count($historico_data);
                $total_paginas_historico = ceil($total_historico / $registros_por_pagina);
                $inicio_historico = ($pagina_historico - 1) * $registros_por_pagina;
                $historico_paginado = array_slice($historico_data, $inicio_historico, $registros_por_pagina);
                ?>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock Actual</th>
                                <th>Stock Histórico</th>
                                <th>Diferencia</th>
                                <th>% Variación</th>
                                <th>Observaciones</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico_paginado as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['producto']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['stock_actual']) ?></td>
                                    <td><?= htmlspecialchars($row['stock_historico']) ?></td>
                                    <td><?= number_format($row['diferencia_absoluta'], 1) ?></td>
                                    <td class="<?= $row['diferencia_porcentaje'] > 30 ? 'text-negative' : 'text-positive' ?>">
                                        <?= number_format($row['diferencia_porcentaje'], 1) ?>%
                                    </td>
                                    <td><?= htmlspecialchars($row['observaciones']) ?></td>
                                    <td>
                                        <span class="badge <?= 
                                            $row['estado'] == 'CONSISTENTE' ? 'bg-success' : 
                                            ($row['estado'] == 'INCONSISTENTE' ? 'bg-danger' : 'bg-secondary')
                                        ?>">
                                            <?= htmlspecialchars($row['estado']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación Stock Histórico -->
                <?php if ($total_paginas_historico > 1): ?>
                    <div class="pagination-container mt-3">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <!-- Botón Anterior -->
                                <li class="page-item <?= $pagina_historico == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= 
                                        http_build_query(array_merge($_GET, [
                                            'pagina_historico' => $pagina_historico - 1,
                                            'pagina_atipicos' => 1,
                                            'tab_activa' => 'historico'
                                        ])) 
                                    ?>">
                                        <i class="bi bi-chevron-left"></i> Anterior
                                    </a>
                                </li>
                                
                                <!-- Números de página -->
                                <?php for ($i = 1; $i <= $total_paginas_historico; $i++): ?>
                                    <li class="page-item <?= $i == $pagina_historico ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= 
                                            http_build_query(array_merge($_GET, [
                                                'pagina_historico' => $i,
                                                'pagina_atipicos' => 1,
                                                'tab_activa' => 'historico'
                                            ])) 
                                        ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Botón Siguiente -->
                                <li class="page-item <?= $pagina_historico == $total_paginas_historico ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= 
                                        http_build_query(array_merge($_GET, [
                                            'pagina_historico' => $pagina_historico + 1,
                                            'pagina_atipicos' => 1,
                                            'tab_activa' => 'historico'
                                        ])) 
                                    ?>">
                                        Siguiente <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="text-center text-muted mt-2">
                            Mostrando <?= count($historico_paginado) ?> de <?= $total_historico ?> registros
                        </div>
                    </div>
                <?php endif; ?>

<!-- Botones de acción Stock Histórico -->
<div class="button-container mt-4">
    <form method="POST" action="../Controladores/pdf_movimientos_historicos.php" target="_blank" style="display: inline;">
        <input type="hidden" name="tipo_auditoria" value="<?= $tipo_auditoria ?>">
        <input type="hidden" name="fecha_corte" value="<?= $fecha_corte ?>">
        <input type="hidden" name="datos_historico" value="<?= htmlspecialchars(json_encode($historico_data)) ?>">
        <input type="hidden" name="total_registros" value="<?= $total_historico ?>">
        <button type="submit" class="btn btn-primary" <?= empty($historico_data) ? 'disabled' : '' ?>>
            <i class="bi bi-file-earmark-pdf"></i> Generar PDF Completo
        </button>
    </form>
</div>
            <?php else: ?>
                <div class="alert alert-warning mt-4">No hay datos históricos disponibles para mostrar.</div>
            <?php endif; ?>
        </div>

        <!-- Pestaña Movimientos Atípicos -->
        <div class="tab-pane fade <?= $tab_activa == 'atipicos' ? 'show active' : '' ?>" id="atipicos" role="tabpanel">
            <?php if (!empty($movimientos_atipicos)): ?>
                <?php
                // Configuración de paginación para movimientos atípicos
                $registros_por_pagina = 4;
                $total_atipicos = count($movimientos_atipicos);
                $total_paginas_atipicos = ceil($total_atipicos / $registros_por_pagina);
                $inicio_atipicos = ($pagina_atipicos - 1) * $registros_por_pagina;
                $atipicos_paginados = array_slice($movimientos_atipicos, $inicio_atipicos, $registros_por_pagina);
                ?>
                
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> 
                    Se detectaron <?= $total_atipicos ?> movimientos atípicos en el período seleccionado
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Tipo Atípico</th>
                                <th>Regla</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atipicos_paginados as $movimiento): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($movimiento['producto']) ?></strong></td>
                                    <td><?= htmlspecialchars($movimiento['cantidad']) ?></td>
                                    <td>
                                        <span class="badge <?= $movimiento['tipo'] == 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= htmlspecialchars($movimiento['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($movimiento['fecha']) ?></td>
                                    <td><?= htmlspecialchars($movimiento['descripcion']) ?></td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <?= htmlspecialchars($movimiento['tipo_atipico']) ?>
                                        </span>
                                    </td>
                                    <td><small><?= htmlspecialchars($movimiento['regla']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación Movimientos Atípicos -->
                <?php if ($total_paginas_atipicos > 1): ?>
                    <div class="pagination-container mt-3">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <!-- Botón Anterior -->
                                <li class="page-item <?= $pagina_atipicos == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= 
                                        http_build_query(array_merge($_GET, [
                                            'pagina_atipicos' => $pagina_atipicos - 1,
                                            'pagina_historico' => 1,
                                            'tab_activa' => 'atipicos'
                                        ])) 
                                    ?>">
                                        <i class="bi bi-chevron-left"></i> Anterior
                                    </a>
                                </li>
                                
                                <!-- Números de página -->
                                <?php for ($i = 1; $i <= $total_paginas_atipicos; $i++): ?>
                                    <li class="page-item <?= $i == $pagina_atipicos ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= 
                                            http_build_query(array_merge($_GET, [
                                                'pagina_atipicos' => $i,
                                                'pagina_historico' => 1,
                                                'tab_activa' => 'atipicos'
                                            ])) 
                                        ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Botón Siguiente -->
                                <li class="page-item <?= $pagina_atipicos == $total_paginas_atipicos ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= 
                                        http_build_query(array_merge($_GET, [
                                            'pagina_atipicos' => $pagina_atipicos + 1,
                                            'pagina_historico' => 1,
                                            'tab_activa' => 'atipicos'
                                        ])) 
                                    ?>">
                                        Siguiente <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="text-center text-muted mt-2">
                            Mostrando <?= count($atipicos_paginados) ?> de <?= $total_atipicos ?> movimientos atípicos
                        </div>
                    </div>
                <?php endif; ?>

<!-- Botones de acción Movimientos Atípicos -->
<div class="button-container mt-4">
    <form method="POST" action="../Controladores/pdf_movimientos_atipicos.php" target="_blank" style="display: inline;">
        <input type="hidden" name="tipo_auditoria" value="<?= $tipo_auditoria ?>">
        <input type="hidden" name="fecha_corte" value="<?= $fecha_corte ?>">
        <input type="hidden" name="datos_atipicos" value="<?= htmlspecialchars(json_encode($movimientos_atipicos)) ?>">
        <input type="hidden" name="total_registros" value="<?= $total_atipicos ?>">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-file-earmark-pdf"></i> Generar PDF Completo
        </button>
    </form>
</div>
            <?php else: ?>
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle-fill"></i> 
                    <?= isset($_GET['generar_reporte']) ? 
                        'No se encontraron movimientos atípicos en el período seleccionado.' : 
                        'Seleccione un período y genere el reporte para ver movimientos atípicos.' 
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.menu-toggle').forEach(btn => {
      btn.addEventListener('click', () => btn.parentElement.classList.toggle('open'));
    });

    function toggleDropdown(){
      const d = document.getElementById('dropdownMenu');
      d.style.display = d.style.display === 'block' ? 'none' : 'block';
    }
    window.addEventListener('click', e => {
      const m = document.getElementById('userMenu');
      if (!m.contains(e.target)) {
        document.getElementById('dropdownMenu').style.display = 'none';
      }
    });

    function guardarTabActiva(tab) {
        // Actualizar parámetro en la URL sin recargar
        const url = new URL(window.location);
        url.searchParams.set('tab_activa', tab);
        window.history.replaceState({}, '', url);
        
        // También guardar en sessionStorage para persistencia
        sessionStorage.setItem('tab_activa', tab);
    }

    // Recuperar pestaña activa al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const tabActiva = sessionStorage.getItem('tab_activa') || 'historico';
        const tabElement = document.getElementById(tabActiva + '-tab');
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    });

    // Activar pestañas de Bootstrap - versión corregida
    document.addEventListener('DOMContentLoaded', function() {
        const tabTriggers = [].slice.call(document.querySelectorAll('#auditoriaTabs button[data-bs-toggle="tab"]'));
        tabTriggers.forEach(function(tabTrigger) {
            tabTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                const tab = new bootstrap.Tab(this);
                tab.show();
            });
        });
    });
  </script>

</body>
</html>