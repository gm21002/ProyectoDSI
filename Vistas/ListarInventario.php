<?php
session_start();

require_once '../Modelos/Conexion.php';
require_once '../Modelos/CategoriaModel.php';
$catModel  = new CategoriaModel();
$catList   = $catModel->obtenerCategorias();  // para el filtro

$correo = $_SESSION['usuario_correo'] ?? 'usuario@nextgen.com';

$db = Conexion::getInstance()->getConnection();

/* ---- Búsqueda ---- */
$search = trim($_GET['q'] ?? '');
$catId = intval($_GET['cat'] ?? 0);
$sql = "
  SELECT i.id,
         i.producto_id,
         p.nombre,
         p.codigo,
         c.nombre  AS categoria,
         pr.nombre AS proveedor,
         i.precio,
         i.cantidad_stock
  FROM inventario i
  JOIN productos   p  ON p.id  = i.producto_id
  JOIN categorias  c  ON c.id  = p.categoria_id
  JOIN proveedores pr ON pr.id = i.proveedor_id
  WHERE (:search = '' OR p.nombre LIKE CONCAT('%', :search ,'%') OR p.codigo LIKE CONCAT('%', :search ,'%'))
    AND (:cat = 0 OR p.categoria_id = :cat)
  ORDER BY p.nombre
  LIMIT 200
";
$stmt = $db->prepare($sql);
$stmt->execute([':search' => $search, ':cat' => $catId]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once '../Modelos/LimiteStockModel.php';
$limiteModel = new LimiteStockModel();
$limites = [];

foreach ($limiteModel->listarTodo() as $lim) {
  $limites[$lim['producto_id']] = $lim;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Inventario - NextGen</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
   <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');

    * {
      box-sizing: border-box;
    }

    body, html {
      margin: 0;
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: #0a2540;
      color: #f0f4f8;
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
      flex-direction: row;
    }

    /* Sidebar moderno */
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

    .sidebar a:hover, .sidebar a.active {
      background: rgba(255 255 255 / 0.1);
    }

    .sidebar a i {
      margin-right: 12px;
      font-size: 1.2rem;
    }

    /* Menú con submenu */
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
      text-align: left;
      font-weight: 600;
      transition: background 0.3s ease;
    }

    .menu-toggle:hover {
      background: rgba(255 255 255 / 0.1);
      color: #cbd5e1; 
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

    .submenu a:hover, .submenu a.active {
      background: rgba(255 255 255 / 0.1);
    }

    .menu-item.open .submenu {
      display: flex;
    }

    .menu-item.open .chevron {
      transform: rotate(180deg);
    }

    /* Contenido principal */
    .main-content {
      flex: 1;
      padding: 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .header {
      width: 100%;
      max-width: 960px;
      margin-bottom: 40px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
    }

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

    .actions {
      margin-bottom: 20px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }
    .actions a.btn-primary {
      margin-left: auto;
    }

    .actions form {
      display: flex;
      gap: 8px;
      flex: 1;
      max-width: 400px;
      width: 100%; 
    }

    .actions input[type="text"] {
      width: 300px;
      flex: none;
      padding: 8px 12px;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
    }

    .actions select {
      padding: 8px 12px;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
      background: white;
      color: black;
      cursor: pointer;
    }

    .btn-primary {
      background: #22d3ee;
      color: #0a2540;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background 0.3s ease;
      text-decoration: none;
    }

    .btn-primary:hover {
      background: #0ea5e9;
      color: white;
    }

    .table-container {
      width: 100%;
      overflow-x: auto;
      margin-bottom: 16px;
    }
    .pagination {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 20px;
      flex-wrap: wrap;
    }
    .pagination a {
      padding: 8px 12px;
      border-radius: 8px;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      color: #fff;
      text-decoration: none;
      font-weight: 600;
    }
    .pagination a.active {
      background: #2563eb;
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

    /* Responsive */
    @media (max-width: 768px) {
      .dashboard-container {
        flex-direction: column;
      }
      .sidebar {
        width: 100%;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        padding: 16px 8px;
      }
      .main-content {
        padding: 20px;
      }
      .content {
        padding: 20px;
      }
      .actions {
        flex-direction: column;
        align-items: stretch;
      }
      .actions form {
        max-width: 100%;
      }
      table thead {
        display: none;
      }
      table, tbody, tr, td {
        display: block;
        width: 100%;
      }
      tr {
        margin-bottom: 15px;
        border-bottom: 2px solid #2563eb;
        padding-bottom: 10px;
      }
      td {
        padding-left: 50%;
        position: relative;
        text-align: left;
        white-space: normal;
      }
      td::before {
        position: absolute;
        left: 16px;
        top: 12px;
        white-space: nowrap;
        font-weight: 600;
        color: #22d3ee;
      }
      td:nth-of-type(1)::before { content: "Código"; }
      td:nth-of-type(2)::before { content: "Nombre"; }
      td:nth-of-type(3)::before { content: "Descripción"; }
      td:nth-of-type(4)::before { content: "Categoría"; }
      td:nth-of-type(5)::before { content: "Proveedor"; }
      td:nth-of-type(6)::before { content: "Precio"; }
      td:nth-of-type(7)::before { content: "Stock"; }
    }

    .content h2 {
      font-size: 2.5rem;
      font-weight: 800;
      text-align: center;
      margin-bottom: 24px;
    }
  </style>
</head>
<body>
  <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a607f958-7370-428e-bc1b-2af0eb7d51dd.png" alt="Background" class="background-image" />

  <div class="dashboard-container">
  <nav class="sidebar">
    <h2>NextGen Distributors</h2>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    
    <!-- Submenú Entradas -->
    <div class="menu-item open">
      <button type="button" class="menu-toggle">
        <i class="bi bi-box-arrow-in-down"></i> Entradas
        <i class="bi bi-chevron-down chevron"></i>
      </button>
      <div class="submenu">
        <a href="ListarInventario.php"class="active">Inventario</a>
        <a href="ListarProducto.php">Productos</a>
        <a href="ListarCategoria.php">Categorías</a>
        <a href="ListarProveedor.php">Proveedores</a>
      </div>
    </div>

    <!-- Submenú Salidas -->
    <div class="menu-item">
      <button type="button" class="menu-toggle">
        <i class="bi bi-box-arrow-up"></i> Salidas
        <i class="bi bi-chevron-down chevron"></i>
      </button>
      <div class="submenu">
        <a href="RegistrarSalida.php">Registrar Salida</a>
        <a href="Salidas.php">Reporte de Salidas</a>
      </div>
    </div>

  <a href="../Vistas/ListarInventario.php"><i class="bi bi-archive"></i> Inventario</a>
            <div class="menu-item">
        <button class="menu-toggle">
          <i class="bi bi-clipboard-data"></i> Auditoria
          <i class="bi bi-chevron-down chevron"></i>
        </button>
        <div class="submenu">
          <a href="../Controladores/AuditoriaController.php">Movimientos</a>
          <a href="../Vistas/Historico.php">Historico</a>
        </div>
      </div>
  </nav>

    <main class="main-content">
      <header class="header">
        <div class="user-menu" id="userMenu">
          <button class="user-button" onclick="toggleDropdown()">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($correo) ?>
          </button>
          <div class="dropdown" id="dropdownMenu">
            <form action="../Controladores/AuthController.php" method="post" style="margin:0">
              <button type="submit" name="logout" value="1" class="dropdown-item">
                <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
              </button>
            </form>
          </div>
        </div>
      </header>

      <section class="content">
        <h2>Inventario</h2>

        <div class="actions">
          <form method="GET">
            <input type="text" name="q" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($search) ?>">
            <select name="cat">
              <option value="0">Todas las categorías</option>
              <?php foreach ($catList as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
             <select name="estado">
      <option value="">Todos los estados</option>
      <option value="bajo" <?= ($_GET['estado'] ?? '') === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
      <option value="excedido" <?= ($_GET['estado'] ?? '') === 'excedido' ? 'selected' : '' ?>>Stock excedido</option>
      <option value="agotado" <?= ($_GET['estado'] ?? '') === 'agotado' ? 'selected' : '' ?>>Agotado</option>
      <option value="ok" <?= ($_GET['estado'] ?? '') === 'ok' ? 'selected' : '' ?>>Stock OK</option>
    </select>

            <button type="submit" class="btn-primary"><i class="bi bi-search"></i></button>
          </form>
          <a href="RegistrarInventario.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Nuevo Inventario</a>
        </div>

        <?php if (!$productos): ?>
          <p>No se encontraron productos.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Proveedor</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($productos as $p): ?>
<?php
  $estado = 'Sin límite';
  $badge  = 'light';
  $stock  = $p['cantidad_stock'];
  $lim    = $limites[$p['producto_id']] ?? null;

  if ($stock == 0) {
    $estado = 'Agotado';
    $badge  = 'danger';
  } elseif ($lim) {
    if ($stock < $lim['stock_minimo']) {
      $estado = 'Bajo';
      $badge  = 'danger';
    } elseif ($stock > $lim['stock_maximo']) {
      $estado = 'Excedido';
      $badge  = 'info';
    } else {
      $estado = 'OK';
      $badge  = 'success';
    }
  }

  // ⬇️ Filtro por estado (se aplica después de calcular el estado real)
  $estadoSeleccionado = $_GET['estado'] ?? '';
  if ($estadoSeleccionado && strtolower($estadoSeleccionado) !== strtolower($estado)) {
    continue;
  }
?>
<tr>
  <td><?= htmlspecialchars($p['codigo']) ?></td>
  <td><?= htmlspecialchars($p['nombre']) ?></td>
  <td><?= htmlspecialchars($p['categoria']) ?></td>
  <td><?= htmlspecialchars($p['proveedor']) ?></td>
  <td>$<?= number_format($p['precio'], 2) ?></td>
  <td><?= htmlspecialchars($p['cantidad_stock']) ?></td>
  <td><span class="badge bg-<?= $badge ?>"><?= $estado ?></span></td>
</tr>

<?php endforeach; ?>
            </tbody>
          </table>
          <div style="margin-top: 24px;">
<a href="../Controladores/LimiteStockController.php?accion=listar" class="btn-primary" style="display:inline-block;">
  <i class="bi bi-sliders"></i> Editar Estados
</a>
</div>

        <?php endif; ?>
      </section><!-- content -->
    </main>
  </div>

  <script>
    // dropdown usuario
    function toggleDropdown(){
      const d=document.getElementById('dropdownMenu');
      d.style.display=d.style.display==='block'?'none':'block';
    }
    window.addEventListener('click',e=>{
      const m=document.getElementById('userMenu');
      if(!m.contains(e.target)){
        document.getElementById('dropdownMenu').style.display='none';
      }
    });

    // abrir/cerrar submenu entradas
    document.querySelectorAll('.menu-toggle').forEach(btn=>{
      btn.addEventListener('click',()=>btn.parentElement.classList.toggle('open'));
    });
  </script>
</body>
</html>