<?php
session_start();

// Después de obtener los filtros del formulario
$_SESSION['ultimos_filtros'] = $filtros;

// Guardar también los resultados en sesión (opcional)
$_SESSION['ultimos_resultados'] = $movimientos;
$correo = $_SESSION['usuario_correo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Auditoría de Movimientos</title>
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

.header {
  display: flex;
  justify-content: center; /* Centra ambos elementos */
  align-items: center;
  width: 100%;
  padding: 0 20px;
  margin-bottom: 20px;
  gap: 400px; /* Controla la separación entre ellos */
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
      padding: 8px 16px;
      border-radius: 999px;
      font-weight: 600;
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

    .dropdown-item:hover {
      background: rgba(255 255 255 / 0.1);
    }

    .user-menu.active .dropdown {
      display: block;
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
      max-width: 1100px;
      margin: 0 auto;
      background: rgba(255 255 255 / 0.07);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.5);
      color: #cbd5e1;
    }

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

    .btn-filter {
      background-color: white;
      color: #0a2540;
      border: none;
      font-weight: 600;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
      height: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-filter:hover {
      background-color: #f0f0f0;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
    }
    
    .btn-clear {
      background-color: rgba(255, 255, 255, 0.15);
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.3);
      font-weight: 600;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
      height: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-clear:hover {
      background-color: rgba(255, 255, 255, 0.25);
      transform: translateY(-2px);
    }

    /* ESTILOS MEJORADOS PARA LA TABLA */
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
    
    .bg-danger {
      background-color: rgba(220, 53, 69, 0.9) !important;
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

    /* Paginación */
    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    
    .pagination {
      display: flex;
      gap: 8px;
    }
    
    .page-item {
      margin: 0;
    }
    
    .page-link {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #cbd5e1;
      padding: 8px 14px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    
    .page-link:hover {
      background: rgba(255, 255, 255, 0.2);
      color: white;
    }
    
    .page-item.active .page-link {
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      border-color: #22d3ee;
      color: white;
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

    /* Estilos para alinear los botones a la izquierda */
    .button-container {
      display: flex;
      justify-content: flex-start;
      gap: 12px;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a607f958-7370-428e-bc1b-2af0eb7d51dd.png"
       alt="Background"
       class="background-image"
       onerror="this.style.display='none'" />

  <div class="dashboard-container">
    <nav class="sidebar">
      <h2>NextGen Distributors</h2>
      <a href="../Vistas/Dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>

      <div class="menu-item">
        <button class="menu-toggle">
          <i class="bi bi-box-arrow-in-down"></i> Entradas
          <i class="bi bi-chevron-down"></i>
        </button>
        <div class="submenu">
          <a href="../Vistas/ListarInventario.php">Inventario</a>
          <a href="../Vistas/ListarProducto.php">Productos</a>
          <a href="../Vistas/ListarCategoria.php">Categorías</a>
          <a href="../Vistas/ListarProveedor.php">Proveedores</a>
        </div>
      </div>

      <div class="menu-item">
        <button class="menu-toggle">
          <i class="bi bi-box-arrow-up"></i> Salidas
          <i class="bi bi-chevron-down"></i>
        </button>
        <div class="submenu">
          <a href="../Vistas/RegistrarSalida.php">Registrar Salida</a>
          <a href="../Vistas/Salidas.php">Reporte de Salidas</a>
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
  <h1>Auditoría de Movimientos</h1>
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
        <div class="filter-section">
          <div class="filter-title">Consultar Historial de Movimientos</div>
          <form method="GET" class="row g-3">
            <div class="col-md-3">
              <input type="text" name="producto" class="form-control" placeholder="Producto" value="<?= htmlspecialchars($_GET['producto'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <input type="text" name="usuario" class="form-control" placeholder="Usuario" value="<?= htmlspecialchars($_GET['usuario'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <select name="tipo" class="form-select">
                <option value="">Tipo</option>
                <option <?= ($_GET['tipo'] ?? '') == 'Entrada' ? 'selected' : '' ?>>Entrada</option>
                <option <?= ($_GET['tipo'] ?? '') == 'Salida' ? 'selected' : '' ?>>Salida</option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
            </div>
            
            <!-- Contenedor para los botones alineados a la izquierda -->
<div class="col-12">
  <div class="button-container d-flex justify-content-end">
    <button type="submit" class="btn btn-filter me-2"><i class="bi bi-funnel-fill"></i>&nbsp; Filtrar</button>
    <a href="AuditoriaController.php" class="btn btn-clear"><i class="bi bi-x-circle"></i>&nbsp; Limpiar</a>
    <a href="pdf_movimiento_inventario.php" target="_blank" class="btn btn-primary">📄 Generar PDF</a>
  </div>
</div>
          </form>
        </div>

        <?php if (!empty($movimientos)): ?>
          <div class="success-message">
            <i class="bi bi-check-circle-fill"></i> Historial cargado correctamente.
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>Producto</th>
                  <th>Cantidad</th>
                  <th>Fecha/Hora</th>
                  <th>Usuario</th>
                  <th>Motivo</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                // Mostrar solo 6 registros por página
                $page = $_GET['page'] ?? 1;
                $perPage = 4;
                $start = ($page - 1) * $perPage;
                $end = min($start + $perPage, count($movimientos));
                
                for ($i = $start; $i < $end; $i++): 
                  $row = $movimientos[$i];
                ?>
                  <tr>
                    <td>
                      <span class="badge <?= $row['tipo'] == 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                        <?= htmlspecialchars($row['tipo']) ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($row['producto']) ?></td>
                    <td><?= htmlspecialchars($row['cantidad']) ?></td>
                    <td><?= htmlspecialchars($row['fecha']) ?></td>
                    <td><?= htmlspecialchars($row['usuario'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['motivo'] ?? '') ?></td>
                  </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <?php if (count($movimientos) > $perPage): ?>
            <div class="pagination-container">
              <ul class="pagination">
                <?php for ($p = 1; $p <= ceil(count($movimientos) / $perPage); $p++): ?>
                  <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                  </li>
                <?php endfor; ?>
              </ul>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <div class="alert alert-warning mt-4">No se encontraron resultados para los filtros seleccionados.</div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
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
    document.querySelectorAll('.menu-toggle').forEach(btn => {
      btn.addEventListener('click', () => btn.parentElement.classList.toggle('open'));
    });
  </script>
</body>
</html>