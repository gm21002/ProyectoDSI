<?php
session_start();

require_once '../Modelos/ProductoModel.php';
require_once '../Modelos/CategoriaModel.php';

$correo        = $_SESSION['usuario_correo'] ?? 'usuario@nextgen.com';
$productoModel = new ProductoModel();
$categoriaModel= new CategoriaModel();

/* -------- Filtros -------- */
$search = trim($_GET['q']   ?? '');
$catId  = intval($_GET['cat'] ?? 0);

/* Consulta */
$db   = Conexion::getInstance()->getConnection();
$sql  = "
 SELECT p.id, p.nombre, p.codigo,
        c.nombre AS categoria
 FROM productos p
 JOIN categorias c ON c.id = p.categoria_id
 WHERE ( :q = '' OR p.nombre LIKE CONCAT('%',:q,'%') OR p.codigo LIKE CONCAT('%',:q,'%') )
   AND ( :cat = 0 OR p.categoria_id = :cat )
 ORDER BY p.nombre
";
$stmt = $db->prepare($sql);
$stmt->execute([':q'=>$search, ':cat'=>$catId]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = $categoriaModel->obtenerCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lista de Productos - NextGen</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
      flex: 1;
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
  <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a607f958-7370-428e-bc1b-2af0eb7d51dd.png" alt="Background" class="background-image">

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
        <a href="ListarInventario.php">Inventario</a>
        <a href="ListarProducto.php" class="active">Productos</a>
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
        <!-- Puedes agregar más si necesitas -->
      </div>
    </div>

    <!-- Otros accesos -->
    <a href="ListarInventario.php"><i class="bi bi-archive"></i> Inventario</a>
    <a href="auditoria.php"><i class="bi bi-clipboard-data"></i> Auditoría</a>
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
        <h2>Lista de Productos</h2>

        <div class="actions">
          <form method="GET">
            <input type="text" name="q" placeholder="Buscar por nombre o código..." value="<?= htmlspecialchars($search) ?>">
            <select name="cat">
              <option value="0">Todas las categorías</option>
              <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary"><i class="bi bi-search"></i></button>
          </form>
          <a href="RegistrarProducto.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Nuevo Producto</a>
        </div>

        <?php if (!$productos): ?>
          <p>No se encontraron productos.</p>
        <?php else: ?>
          <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($productos as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['codigo']) ?></td>
                  <td><?= htmlspecialchars($p['nombre']) ?></td>
                  <td><?= htmlspecialchars($p['categoria']) ?></td>
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
      const d=document.getElementById('dropdownMenu');
      d.style.display=d.style.display==='block'?'none':'block';
    }
    window.addEventListener('click',e=>{
      if(!document.getElementById('userMenu').contains(e.target)){
        document.getElementById('dropdownMenu').style.display='none';
      }
    });
    document.querySelectorAll('.menu-toggle').forEach(btn=>{
      btn.addEventListener('click',()=>btn.parentElement.classList.toggle('open'));
    });
  </script>
</body>
</html>