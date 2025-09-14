<?php
session_start();

if (!isset($_SESSION['usuario_correo'])) {
    header('Location: login.php');
    exit();
}

$correo = $_SESSION['usuario_correo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - NextGen Distributors</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
  /* (Aquí va todo tu CSS igual, omitido para no repetir) */
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
    .dashboard-container {
      flex-direction: column;
    }
    .sidebar {
      width: 100%;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: center;
    }
    .main-content {
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
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    
    <!-- Submenú Entradas -->
    <div class="menu-item">
      <button type="button" class="menu-toggle">
        <i class="bi bi-box-arrow-in-down"></i> Entradas
        <i class="bi bi-chevron-down chevron"></i>
      </button>
      <div class="submenu">
        <a href="ListarInventario.php">Inventario</a>
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
        <!-- Puedes agregar más si necesitas -->
      </div>
    </div>

  <a href="../Vistas/ListarInventario.php"><i class="bi bi-archive"></i> Inventario</a>
            <div class="menu-item">
        <button class="menu-toggle">
          <i class="bi bi-clipboard-data"></i> Auditoria
          <i class="bi bi-chevron-down"></i>
        </button>
        <div class="submenu">
          <a href="../Controladores/AuditoriaController.php">Movimientos</a>
          <a href="../Vistas/Historico.php">Historico</a>
        </div>
      </div>
  </nav>

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
        <h2>Bienvenido, <?= htmlspecialchars($correo) ?></h2>

        <div class="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> Atención: Hay 3 productos con stock bajo.
        </div>

        <div class="card-group">
          <div class="card card-primary">
            <div>
              <div class="card-title">Total de Productos</div>
              <h2>128</h2>
            </div>
            <i class="bi bi-box-seam fs-1"></i>
          </div>
          <div class="card card-warning">
            <div>
              <div class="card-title">Stock Mínimo</div>
              <h2>3</h2>
            </div>
            <i class="bi bi-exclamation-triangle fs-1"></i>
          </div>
          <div class="card card-success">
            <div>
              <div class="card-title">Movimientos Recientes</div>
              <h2>12</h2>
            </div>
            <i class="bi bi-arrow-clockwise fs-1"></i>
          </div>
        </div>

        <h4>Últimos Movimientos</h4>
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
              <tr>
                <td><span class="badge bg-success">Entrada</span></td>
                <td>Mouse Gamer</td>
                <td>20</td>
                <td>12/06/2025 14:20</td>
                <td>admin@nextgen.com</td>
              </tr>
              <tr>
                <td><span class="badge bg-danger">Salida</span></td>
                <td>Teclado Mecánico</td>
                <td>5</td>
                <td>11/06/2025 10:15</td>
                <td>tecnico1@nextgen.com</td>
              </tr>
              <tr>
                <td><span class="badge bg-success">Entrada</span></td>
                <td>Webcam HD</td>
                <td>15</td>
                <td>10/06/2025 09:45</td>
                <td>admin@nextgen.com</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <footer class="mt-4 small text-center" style="margin-top: 40px !important;">
        &copy; <?php echo date('Y'); ?> NextGen Distributors. Todos los derechos reservados.
      </footer>
    </main>
  </div>

  <script>
    function toggleDropdown() {
      document.getElementById('userMenu').classList.toggle('active');
    }

    window.addEventListener('click', function(e) {
      const menu = document.getElementById('userMenu');
      if (!menu.contains(e.target)) {
        menu.classList.remove('active');
      }
    });

      // dropdown
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
    
     // toggle sidebar submenu
    document.querySelectorAll('.menu-toggle').forEach(btn=>{
      btn.addEventListener('click',()=>{
        btn.parentElement.classList.toggle('open');
      });
    });
  </script>

    <?php if (isset($_SESSION['bienvenida'])): ?>
  <div id="toastCustom" class="toast-custom">
    <?= htmlspecialchars($_SESSION['bienvenida']) ?>
    <button class="toast-close-btn" onclick="hideToast()">×</button>
  </div>
  <script>
    const toast = document.getElementById('toastCustom');
    // Mostrar toast
    toast.classList.add('show');

    // Ocultar después de 5 segundos
    setTimeout(() => {
      hideToast();
    }, 5000);

    function hideToast() {
      toast.classList.remove('show');
    }
  </script>
  <?php unset($_SESSION['bienvenida']); ?>
<?php endif; ?>


</body>
</html>
