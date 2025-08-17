<?php
session_start();

require_once '../Modelos/Conexion.php';
require_once '../Modelos/CategoriaModel.php';
require_once '../Modelos/LimiteStockModel.php';

$catModel    = new CategoriaModel();
$catList     = $catModel->obtenerCategorias();

$correo      = $_SESSION['usuario_correo'] ?? 'usuario@nextgen.com';
$db          = Conexion::getInstance()->getConnection();

$search      = trim($_GET['q'] ?? '');
$catId       = intval($_GET['cat'] ?? 0);
$action      = $_GET['action'] ?? '';

require_once '../Controladores/SalidaController.php';

$productosInventario = $productosInventario ?? [];
$movimientosSalida   = $movimientosSalida   ?? [];
$fechaInicio         = $fechaInicio         ?? '';
$fechaFin            = $fechaFin            ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Salidas y Reportes - NextGen Distributors</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');
    * { box-sizing: border-box; }
    body, html { margin: 0; height: 100%; font-family: 'Poppins', sans-serif; background: #0a2540; color: #f0f4f8; }
    .background-image { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; filter: brightness(.7); }
    .dashboard-container { display: flex; height: 100vh; overflow: hidden; }
    .sidebar { background: rgba(255,255,255,0.05); width: 240px; padding: 32px 16px; display: flex; flex-direction: column; gap: 16px; border-right: 1px solid rgba(255,255,255,0.1); }
    .sidebar h2 { font-size: 1.5rem; margin-bottom: 32px; text-align: center; background: linear-gradient(135deg,#22d3ee,#2563eb); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; color: #cbd5e1; }
    .sidebar a, .menu-toggle { color: #cbd5e1; text-decoration: none; font-weight: 600; padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: background 0.3s; }
    .sidebar a:hover, .sidebar a.active, .menu-toggle:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .menu-item { display: flex; flex-direction: column; }
    .chevron { margin-left: auto; transition: transform 0.3s; }
    .submenu { display: none; flex-direction: column; margin-left: 24px; margin-top: 8px; }
    .menu-item.open .submenu { display: flex; }
    .menu-item.open .chevron { transform: rotate(180deg); }
    .main-content { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; overflow-y: auto; }
    .header { width: 100%; max-width: 960px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; }
    .user-menu { position: relative; display: inline-block; }
    .user-button { background: rgba(255,255,255,0.1); border: none; color: #f0f4f8; padding: 8px 16px; border-radius: 999px; font-weight: 600; display: flex; align-items: center; cursor: pointer; transition: background 0.3s; }
    .user-button:hover { background: rgba(255,255,255,0.2); }
    .user-button i { margin-right: 8px; }
    .dropdown { position: absolute; top: 48px; right: 0; background: rgba(255,255,255,0.07); backdrop-filter: blur(10px); border-radius: 12px; padding: 12px 0; box-shadow: 0 15px 40px rgba(0,0,0,0.4); min-width: 180px; display: none; z-index: 1000; }
    .dropdown-item { display: block; width: 100%; padding: 10px 20px; color: #f0f4f8; text-decoration: none; font-weight: 600; background: none; border: none; text-align: left; cursor: pointer; transition: background 0.2s; }
    .dropdown-item:hover { background: rgba(255,255,255,0.1); }
    .content-section { background: rgba(255,255,255,0.07); padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); margin-bottom: 30px; width: 100%; max-width: 1200px; }
    .content-section h2 { color: #22d3ee; margin-top: 0; margin-bottom: 20px; font-size: 1.5em; border-bottom: 2px solid #22d3ee; padding-bottom: 10px; }
    .actions { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .search-form { display: flex; gap: 10px; flex-wrap: wrap; }
    .search-form input, .search-form select, .search-form input[type="date"] { padding: 10px 15px; border-radius: 8px; border: 1px solid #4a5c70; background: #2d3a4b; color: #f0f4f8; outline: none; }
    .search-form button { padding: 10px 15px; border: none; border-radius: 8px; background: #22d3ee; color: #0a2540; cursor: pointer; transition: background 0.3s; }
    .search-form button:hover { background: #0ea5e9; color: #fff; }
    .btn-primary, .btn-secondary { display: inline-flex; align-items: center; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: background 0.3s; text-decoration: none; }
    .btn-primary { background: #22d3ee; color: #0a2540; border: none; }
    .btn-primary:hover { background: #0ea5e9; }
    .btn-secondary { background: #4a5c70; color: #f0f4f8; border: 1px solid #4a5c70; }
    .btn-secondary:hover { background: #5a6e82; border-color: #5a6e82; }
    .table-container { overflow-x: auto; margin-top: 20px; }
    table { width: 100%; border-collapse: collapse; }
    table th, table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #2d3a4b; color: #e0e7ff; }
    table th { background: #2563eb; color: #fff; }
    table tbody tr:hover { background: #3a475b; }
    table tbody tr:nth-child(even) { background: #2d3a4b; }
    hr.section-divider { border: none; border-top: 1px solid rgba(34, 211, 238, .5); margin: 20px 0; }
  </style>
</head>
<body>
  <img src="../public/background.png" alt="Background" class="background-image" />
  <div class="dashboard-container">
    <nav class="sidebar">
      <h2>NextGen Distributors</h2>
      <a href="Dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
      <div class="menu-item open">
        <button class="menu-toggle"><i class="bi bi-box-seam-fill"></i> Inventario <i class="bi bi-chevron-down chevron"></i></button>
        <div class="submenu">
          <a href="ListarProducto.php">Productos</a>
          <a href="ListarCategoria.php">Categorías</a>
          <a href="ListarProveedor.php">Proveedores</a>
          <a href="ListarInventario.php">Stock y Precios</a>
        </div>
      </div>
      <a href="RegistrarProducto.php"><i class="bi bi-plus-circle-fill"></i> Registrar Entrada</a>
      <a href="RegistrarInventario.php"><i class="bi bi-box-arrow-in-down"></i> Registrar Inventario</a>
      <a class="active" href="salidas.php"><i class="bi bi-file-earmark-bar-graph-fill"></i> Salidas y Reportes</a>
    </nav>
    <main class="main-content">
      <header class="header">
        <h1>Salidas y Reportes</h1>
        <div class="user-menu" id="userMenu">
          <button class="user-button" onclick="toggleDropdown()">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($correo) ?>
          </button>
          <ul class="dropdown" id="dropdownMenu">
            <li><a href="#">Perfil</a></li>
            <li><a href="../Controladores/CerrarSesion.php">Cerrar Sesión</a></li>
          </ul>
        </div>
      </header>

      <!-- Reporte de Movimientos de Salida -->
      <section class="content-section">
        <h2>Reporte de Movimientos de Salida</h2>
        <div class="actions">
          <form class="search-form" action="salidas.php" method="GET">
            <input type="hidden" name="action" value="listarReporteSalidas">
            <input type="text" name="q" placeholder="Buscar producto o código..." value="<?= htmlspecialchars($search) ?>">
            <select name="cat">
              <option value="0">Todas las categorías</option>
              <?php foreach($catList as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $catId == $cat['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>">
            <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>">
            <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Buscar Salidas</button>
          </form>
          <a href="../Controladores/SalidaController.php?action=exportarSalidasCSV&q=<?= urlencode($search) ?>&cat=<?= $catId ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>" class="btn-secondary">
            <i class="bi bi-download"></i> Exportar CSV
          </a>
        </div>
        <hr class="section-divider" />
        <?php if(!empty($movimientosSalida)): ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID Mov.</th>
                  <th>Código</th>
                  <th>Nombre</th>
                  <th>Categoría</th>
                  <th>Cantidad</th>
                  <th>Fecha</th>
                  <th>Descripción</th>
                  <th>Usuario</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($movimientosSalida as $mov): ?>
                  <tr>
                    <td><?= htmlspecialchars($mov['id']) ?></td>
                    <td><?= htmlspecialchars($mov['codigo']) ?></td>
                    <td><?= htmlspecialchars($mov['nombre_producto']) ?></td>
                    <td><?= htmlspecialchars($mov['nombre_categoria']) ?></td>
                    <td><?= htmlspecialchars($mov['cantidad']) ?></td>
                    <td><?= htmlspecialchars($mov['fecha_hora']) ?></td>
                    <td><?= htmlspecialchars($mov['descripcion']) ?></td>
                    <td><?= htmlspecialchars($mov['usuario_movimiento']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <!-- Reporte General de Inventario -->
      <section class="content-section">
        <h2>Reporte General de Inventario</h2>
        <div class="actions">
          <form class="search-form" action="salidas.php" method="GET">
            <input type="hidden" name="action" value="listarReporteInventario">
            <input type="text" name="q" placeholder="Buscar producto o código..." value="<?= htmlspecialchars($search) ?>">
            <select name="cat">
              <option value="0">Todas las categorías</option>
              <?php foreach($catList as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $catId == $cat['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Buscar</button>
          </form>
          <a href="../Controladores/SalidaController.php?action=exportarInventarioCSV&q=<?= urlencode($search) ?>&cat=<?= $catId ?>" class="btn-secondary">
            <i class="bi bi-download"></i> Exportar CSV
          </a>
        </div>
        <?php if(!empty($productosInventario)): ?>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Nombre</th>
                  <th>Categoría</th>
                  <th>Proveedor</th>
                  <th>Precio</th>
                  <th>Stock</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($productosInventario as $p): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td><?= htmlspecialchars($p['proveedor'] ?? 'N/A') ?></td>
                    <td>$<?= number_format($p['precio'],2) ?></td>
                    <td><?= htmlspecialchars($p['cantidad_stock']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    function toggleDropdown(){
      const d = document.getElementById('dropdownMenu');
      d.style.display = (d.style.display === 'block') ? 'none' : 'block';
    }
    window.addEventListener('click', e => {
      if(!document.getElementById('userMenu').contains(e.target)){
        document.getElementById('dropdownMenu').style.display = 'none';
      }
    });
    document.querySelectorAll('.menu-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.parentElement.classList.toggle('open');
      });
    });
  </script>
</body>
</html>