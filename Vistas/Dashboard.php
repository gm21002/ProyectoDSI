<?php
session_start();

// Redirigir si no está logueado
if (!isset($_SESSION['usuario_correo'])) {
    header('Location: login.php');
    exit();
}

// Datos de usuario
$nombreUsuario = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario_correo'];
$correo = $_SESSION['usuario_correo'];

// Conexión a la BD
require_once '../modelos/Conexion.php';
$conexion = Conexion::getInstance();
$conn = $conexion->getConnection();

// --- Consultas dinámicas ---

// Total de productos
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM productos");
$stmt->execute();
$totalProductos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Productos con stock mínimo
$stmt = $conn->prepare("
    SELECT COUNT(*) AS minimo
    FROM inventario i
    JOIN limites_stock l ON i.producto_id = l.producto_id
    WHERE i.cantidad_stock <= l.stock_minimo
");
$stmt->execute();
$stockMinimo = $stmt->fetch(PDO::FETCH_ASSOC)['minimo'] ?? 0;

// Movimientos recientes (últimos 5)
$tipoFiltro = $_GET['tipo'] ?? '';
$sql = "
    SELECT m.tipo, p.nombre AS producto, m.cantidad, m.fecha, m.usuario_correo AS usuario, i.cantidad_stock, l.stock_minimo
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    JOIN inventario i ON i.producto_id = p.id
    LEFT JOIN limites_stock l ON l.producto_id = p.id
";
if($tipoFiltro === 'entrada' || $tipoFiltro === 'salida'){
    $sql .= " WHERE m.tipo = :tipo";
}
$sql .= " ORDER BY m.fecha DESC LIMIT 5";

$stmt = $conn->prepare($sql);
if($tipoFiltro === 'entrada' || $tipoFiltro === 'salida'){
    $stmt->bindParam(':tipo', $tipoFiltro);
}
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalMovimientos = count($movimientos);

// --- DATOS PARA GRÁFICA DE MOVIMIENTOS POR MES (ULTIMOS 12 MESES) ---

$meses = [];
$entradasPorMes = [];
$salidasPorMes = [];

for($i=11;$i>=0;$i--){
    $time = strtotime("-$i month");
    $mesAno = date('Y-m', $time);
    $meses[] = date('M Y', $time);
    $entradasPorMes[$mesAno] = 0;
    $salidasPorMes[$mesAno] = 0;
}

// Consulta SQL: Movimientos últimos 12 meses
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(fecha,'%Y-%m') AS mes_ano, tipo, SUM(cantidad) AS total
    FROM movimientos
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY mes_ano, tipo
    ORDER BY mes_ano ASC
");
$stmt->execute();
$movimientosMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Llenar arrays
foreach($movimientosMes as $m){
    $mesAno = $m['mes_ano'];
    if($m['tipo'] === 'entrada') $entradasPorMes[$mesAno] = intval($m['total']);
    if($m['tipo'] === 'salida') $salidasPorMes[$mesAno] = intval($m['total']);
}

// Preparar datos para Chart.js
$entradasData = [];
$salidasData = [];
foreach(array_keys($entradasPorMes) as $mesAno){
    $entradasData[] = $entradasPorMes[$mesAno];
    $salidasData[] = $salidasPorMes[$mesAno];
}

// Mensaje de bienvenida (si existe) — lo guardamos en una variable y lo eliminamos
$mensajeBienvenida = $_SESSION['bienvenida'] ?? null;
if ($mensajeBienvenida) {
  unset($_SESSION['bienvenida']);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - NextGen Distributors</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>

  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');
  * { box-sizing: border-box; }
  html, body {
    margin: 0;
    height: 100%;
    font-family: 'Poppins', sans-serif;
    color: #f0f4f8;
    /* Fondo sólido azul (forzado) */
    background-color: #0a2540 !important;
    background-image: none !important;
  }

  .dashboard-container {
    min-height: 100vh;
  }

  .sidebar {
    width: 240px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    background: rgba(255 255 255 / 0.05);
    backdrop-filter: blur(8px);
    padding: 32px 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    z-index: 100;
  }

  .sidebar h2 {
    font-size: 1.5rem;
    margin-bottom: 32px;
    text-align: center;
    background: linear-gradient(135deg, #22d3ee, #2563eb);
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

  .main-content {
    margin-left: 240px;
    padding: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
  }

  .header {
    width: 100%;
    max-width: 960px;
    margin-bottom: 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .header h1 {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #22d3ee, #2563eb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
  }

  /* Mantener h2 normal y posicionar el rol a la derecha con la misma fuente */
  .content h2 { position: relative; }
  .content h2 .user-role { position: absolute; right: 0; top: 0; color: #a8b3c7; font: inherit; white-space: nowrap; }

  .user-menu {
    position: relative;
    display: inline-block;
  }

  .user-button {
    background: rgba(255 255 255 / 0.1);
    border: none;
    color: #f0f4f8;
    padding: 8px 16px;
    border-radius: 999px;
    font-weight: 600;
    display: flex;
    align-items: center;
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
    border-radius: 12px;
    padding: 12px 0;
    box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    min-width: 180px;
    display: none;
    z-index: 1000;
  }

  .dropdown-item {
    display: block;
    width: 100%;
    padding: 10px 20px;
    color: #f0f4f8;
    text-decoration: none;
    font-weight: 600;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .dropdown-item:hover {
    background: rgba(255 255 255 / 0.1);
  }

  /* Filtros de la tabla de movimientos */
  .filter-group { display:flex; gap:8px; margin:8px 0 16px; justify-content:center; align-items:center; }
  .filter-btn {
    padding: 6px 12px;
    border-radius: 8px;
    text-decoration: none;
    color: #cbd5e1;
    background: rgba(255 255 255 / 0.03);
    border: 1px solid rgba(255,255,255,0.06);
    font-weight: 700;
  }
  .filter-btn.active {
    background: linear-gradient(135deg, #22d3ee, #2563eb);
    color: white;
    border-color: transparent;
  }

  .user-menu.active .dropdown {
    display: block;
  }

  .content {
    width: 100%;
    max-width: 1200px;
    background: rgba(255 255 255 / 0.07);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 32px 28px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.5);
    text-align: left;
    color: #cbd5e1;
  }

  .alert {
    background: rgba(255 255 255 / 0.1);
    color: #fbbf24;
    border-radius: 12px;
    font-weight: 600;
    padding: 16px;
    margin-bottom: 24px;
  }

  .card-group {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 32px;
  }

  /* Contenedor para la gráfica: tamaño controlado */
  .chart-wrapper {
    width: 100%;
    max-width: 760px; /* ancho máximo más pequeño */
    height: 300px; /* altura fija para la gráfica (ligeramente más pequeña) */
    margin: 0 auto 24px; /* centrar horizontalmente */
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #movimientosMesChart {
    width: 100% !important;
    height: 100% !important; /* ocupa la altura del contenedor */
    display: block;
    margin: 0 auto; /* asegurar centrado */
  }

  .card {
    flex: 1 1 calc(33.333% - 16px);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #f0f4f8;
    box-shadow: 0 15px 40px rgba(0,0,0,0.5);
  }

  .card-primary { background: linear-gradient(135deg, #22d3ee, #2563eb); }
  .card-warning { background: linear-gradient(135deg, #fbbf24, #b45309); }
  .card-success { background: linear-gradient(135deg, #4ade80, #166534); }

  .card h2 {
    font-size: 2.5rem;
    margin: 0;
    font-weight: 800;
  }

  /* Iconos en las tarjetas: tamaño ligeramente mayor y responsivo */
  .card i, .card .bi {
    font-size: 3rem; /* aumenta un poco respecto al valor por defecto */
    line-height: 1;
  }

  @media (max-width: 768px) {
    .card i, .card .bi {
      font-size: 2.2rem; /* reducir en pantallas pequeñas para no romper el layout */
    }
  }

  .card-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .table-container {
    width: 100%;
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255 255 255 / 0.07);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    color: #e0e7ff;
    margin-top: 16px;
  }

  table thead {
    background: linear-gradient(135deg, #22d3ee, #2563eb);
    color: white;
  }

  th, td {
    padding: 12px 16px;
    text-align: left;
    white-space: nowrap;
  }

  tbody tr:hover {
    background: rgba(34, 211, 238, 0.15);
  }

  .badge {
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.875rem;
  }

  .bg-success {
    background: #4ade80;
    color: #166534;
  }

  .bg-danger {
    background: #f87171;
    color: #7f1d1d;
  }

  @media (max-width: 768px) {
    .sidebar {
      position: static;
      width: 100%;
      height: auto;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: center;
      padding: 16px 8px;
      border-right: none;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .main-content {
      margin-left: 0;
      padding: 20px;
    }
    .card-group {
      flex-direction: column;
    }
    .card {
      flex: 1 1 100%;
    }
  }
  /* ----- submenu styles ----- */
  .menu-item{display:flex;flex-direction:column;}
  .menu-toggle{
    background:none;border:none;color:inherit;font:inherit;padding:10px;
    display:flex;align-items:center;gap:8px;cursor:pointer;border-radius:8px;text-align:left;
  }
  .menu-toggle:hover {
    background: rgba(255 255 255 / 0.1);
    color: #cbd5e1; /* color del texto igual que los enlaces */
  }

  .chevron{margin-left:auto;transition:transform .3s;}
  .submenu{display:none;flex-direction:column;margin-left:24px;}
  .submenu a{padding:8px;}
  .menu-item.open .submenu{display:flex;}
  .menu-item.open .chevron{transform:rotate(180deg);}

  /* ----- Estilos para Toast personalizado ----- */
  .toast-custom {
  position: fixed;
  top: 20px;       /* distancia desde arriba */
  right: 20px;     /* distancia desde la derecha */
  background-color: #198754;
  color: white;
  padding: 15px 25px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  font-family: Arial, sans-serif;
  font-size: 16px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.5s ease-in-out;
  z-index: 9999;
}

  .toast-custom.show {
    opacity: 1;
    pointer-events: auto;
  }

  .toast-close-btn {
    background: transparent;
    border: none;
    color: white;
    font-weight: bold;
    font-size: 18px;
    margin-left: 15px;
    cursor: pointer;
  }

  /* Separación ligera entre la tabla/contenido y el footer */
  .main-content footer {
    margin-top: 28px; /* un poco más abajo */
    margin-bottom: 18px; /* espacio al final de la página */
  }
</style>

</head>
<body>
  <!-- background handled via CSS (body) -->
  <?php if (!empty($mensajeBienvenida)): ?>
    <div id="toastBienvenida" class="toast-custom" role="status" aria-live="polite" aria-atomic="true">
      <span><?= htmlspecialchars($mensajeBienvenida) ?></span>
      <button class="toast-close-btn" aria-label="Cerrar" onclick="closeToast()">✕</button>
    </div>
    <script>
      function showToast(){
        const t = document.getElementById('toastBienvenida');
        if(!t) return;
        t.classList.add('show');
        // auto hide
        setTimeout(()=>{ t.classList.remove('show'); }, 4500);
      }
      function closeToast(){ const t = document.getElementById('toastBienvenida'); if(t) t.classList.remove('show'); }
      document.addEventListener('DOMContentLoaded', showToast);
    </script>
  <?php endif; ?>

  <div class="dashboard-container">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
      <header class="header">
        <h1>Panel de Control</h1>
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
        <h2>Bienvenido, <?= htmlspecialchars($nombreUsuario) ?> <span class="user-role"><?= htmlspecialchars($rol === 'Administrador' ? 'Administrador' : 'Bodeguero') ?></span></h2>

        <?php if($stockMinimo > 0): ?>
        <div class="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          Atención: Hay <?= $stockMinimo ?> productos con stock bajo.
        </div>
        <?php endif; ?>

        <div class="card-group">
          <div class="card card-primary">
            <div>
              <div class="card-title">Total de Productos</div>
              <h2><?= $totalProductos ?></h2>
            </div>
            <i class="bi bi-box-seam fs-1"></i>
          </div>
          <div class="card card-warning">
            <div>
              <div class="card-title">Stock Mínimo</div>
              <h2><?= $stockMinimo ?></h2>
            </div>
            <i class="bi bi-exclamation-triangle fs-1"></i>
          </div>
          <div class="card card-success">
            <div>
              <div class="card-title">Movimientos Recientes</div>
              <h2><?= $totalMovimientos ?></h2>
            </div>
            <i class="bi bi-arrow-clockwise fs-1"></i>
          </div>
        </div>

        <!-- GRÁFICA DE MOVIMIENTOS POR MES -->
        <h4>Total de Entradas y Salidas de Productos por Mes (Últimos 12 Meses)</h4>
        <div class="chart-wrapper">
          <canvas id="movimientosMesChart"></canvas>
        </div>

        <h4>Últimos Movimientos</h4>
        <div class="filter-group" role="navigation" aria-label="Filtrar movimientos">
          <?php $self = htmlspecialchars($_SERVER['PHP_SELF']); ?>
          <a href="<?= $self ?>" class="filter-btn <?= $tipoFiltro === '' ? 'active' : '' ?>">Todos</a>
          <a href="<?= $self ?>?tipo=entrada" class="filter-btn <?= $tipoFiltro === 'entrada' ? 'active' : '' ?>">Entradas</a>
          <a href="<?= $self ?>?tipo=salida" class="filter-btn <?= $tipoFiltro === 'salida' ? 'active' : '' ?>">Salidas</a>
        </div>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Fecha</th>
                <th>Usuario</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($movimientos as $mov): ?>
              <tr>
                <td>
                  <span class="badge <?= strtolower($mov['tipo']) === 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                    <?= htmlspecialchars(ucfirst($mov['tipo'])) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($mov['producto']) ?></td>
                <td><?= htmlspecialchars($mov['cantidad']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?></td>
                <td><?= htmlspecialchars($mov['usuario'] ?? 'Sistema') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <footer class="mt-4 small text-center">
        &copy; <?= date('Y') ?> NextGen Distributors. Todos los derechos reservados.
      </footer>
    </main>
  </div>

<script>
function toggleDropdown() {
  document.getElementById('userMenu').classList.toggle('active');
}
window.addEventListener('click', function(e) {
  const menu = document.getElementById('userMenu');
  if (!menu.contains(e.target)) menu.classList.remove('active');
});

</script>

<!-- Cargar Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// --- CHART.JS ---
document.addEventListener('DOMContentLoaded', function(){
  const canvas = document.getElementById('movimientosMesChart');
  if (canvas) {
    const ctx = canvas.getContext('2d');
    new Chart(ctx,{
      type:'bar',
      data:{
        labels: <?= json_encode($meses) ?>,
        datasets:[
          {label:'Entradas', data: <?= json_encode($entradasData) ?>, backgroundColor:'#4ade80'},
          {label:'Salidas', data: <?= json_encode($salidasData) ?>, backgroundColor:'#f87171'}
        ]
      },
      options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true}}}
    });
  }
});
</script>

</body>
</html>