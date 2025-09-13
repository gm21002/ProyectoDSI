<?php
// Vistas/Auditoria.php
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

    .menu-item {
      display: flex;
      flex-direction: column;
    }

    .menu-toggle {
      background: none;
      border: none;
      color: #cbd5e1;
      text-align: left;
      padding: 12px 16px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: 600;
      cursor: pointer;
    }

    .menu-toggle:hover {
      background: rgba(255 255 255 / 0.1);
    }

    .submenu {
      display: flex;
      flex-direction: column;
      margin-left: 16px;
      margin-top: 4px;
      margin-bottom: 8px;
    }

    .submenu a {
      padding: 6px 12px;
      font-size: 0.9rem;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .header h1 {
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 24px;
    }

    .content {
      width: 100%;
      max-width: 1000px;
      background: rgba(255 255 255 / 0.07);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 32px 28px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.5);
      color: #cbd5e1;
    }

    .form-control, .form-select {
      background: rgba(255,255,255,0.1);
      color: #f0f4f8;
      border: none;
    }

    .form-control:focus, .form-select:focus {
      background: rgba(255,255,255,0.2);
      outline: 2px solid #22d3ee;
      color: white;
    }

    .btn-primary {
      background-color: #2563eb;
      border: none;
    }

    .btn-secondary {
      background-color: #64748b;
      border: none;
    }

    table {
      margin-top: 24px;
      background-color: rgba(255,255,255,0.05);
      color: #f8fafc;
    }

    thead {
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      color: white;
    }

    .alert {
      background: rgba(255,255,255,0.1);
      color: #fbbf24;
      font-weight: 600;
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
      <a href="../Vistas/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>

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
      <a href="../Controladores/AuditoriaController.php"><i class="bi bi-clipboard-data"></i> Auditoría</a>
    </nav>

    <main class="main-content">
      <header class="header">
        <h1>Auditoría de Movimientos</h1>
      </header>

      <section class="content">
        <h4 class="mb-4">Consultar Historial de Movimientos</h4>
        <form method="GET" class="row g-3 mb-4">
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
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel-fill"></i>&nbsp; Filtrar</button>
            <a href="AuditoriaController.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i>&nbsp; Limpiar</a>
          </div>
        </form>

        <?php if (!empty($movimientos)): ?>
          <p><strong>Historial cargado correctamente.</strong></p>
          <table class="table table-bordered table-hover">
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
              <?php foreach ($movimientos as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['tipo']) ?></td>
                  <td><?= htmlspecialchars($row['producto']) ?></td>
                  <td><?= htmlspecialchars($row['cantidad']) ?></td>
                  <td><?= htmlspecialchars($row['fecha']) ?></td>
                  <td><?= htmlspecialchars($row['usuario'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['motivo'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-warning mt-4">No se encontraron resultados para los filtros seleccionados.</div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>

