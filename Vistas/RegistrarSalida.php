<?php
session_start();
if (!isset($_SESSION['usuario_correo'])) {
    header('Location: login.php');
    exit();
}
$correo = $_SESSION['usuario_correo'] ?? 'usuario@nextgen.com';

require_once '../Controladores/RegistrarSalidaController.php';
$productosParaSelect = $productosDisponibles ?? [];
$errores            = $_SESSION['errores'] ?? [];
unset($_SESSION['errores']);
$mensaje_exito      = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_exito']);
$ultimasSalidas     = obtenerUltimasSalidas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registrar Salida - NextGen Distributors</title>
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

/* Sidebar */
.sidebar {
  width: 240px;
  background: rgba(255, 255, 255, 0.05);
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
.sidebar a:hover,
.sidebar a.active {
  background: rgba(255, 255, 255, 0.1);
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
  justify-content: space-between;
  align-items: center;
}
.user-menu {
  position: relative;
}
.user-button {
  background: rgba(255, 255, 255, 0.1);
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
  background: rgba(255, 255, 255, 0.2);
}
.user-button i {
  margin-right: 8px;
}
.dropdown {
  position: absolute;
  top: 48px;
  right: 0;
  background: rgba(255, 255, 255, 0.07);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 12px 0;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
  min-width: 180px;
  display: none;
  z-index: 1000;
}
.dropdown-item {
  width: 100%;
  padding: 10px 20px;
  color: #f0f4f8;
  background: none;
  border: none;
  text-align: left;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}
.dropdown-item:hover {
  background: rgba(255, 255, 255, 0.1);
}
.user-menu.active .dropdown {
  display: block;
}

/* Sección contenido */
.content {
  width: 100%;
  max-width: 860px;
  background: rgba(255, 255, 255, 0.07);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
}
.content h2 {
  text-align: center;
  margin-bottom: 24px;
  font-size: 2rem;
  font-weight: 700;
}

/* Formularios */
form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
form label {
  font-weight: 600;
}
form input,
form select,
form textarea {
  padding: 10px 14px;
  border-radius: 8px;
  border: none;
  font-size: 1rem;
  font-family: inherit;
}
form button {
  background: #22d3ee;
  color: #0a2540;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  justify-content: center; /* CENTRA horizontalmente el contenido */
  align-items: center;     /* CENTRA verticalmente el contenido */
  gap: 10px;
  font-size: 1.1rem;       /* AUMENTA un poco el tamaño de la fuente */
  transition: background 0.3s ease;
}
form button:hover {
  background: #0ea5e9;
  color: white;
}

/* Mensajes */
.message.success {
  background-color: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  padding: 12px;
  margin-bottom: 16px;
  border-radius: 8px;
  font-weight: 600;
}
.message.error {
  background-color: rgba(239, 68, 68, 0.15);
  color: #f87171;
  padding: 12px;
  margin-bottom: 16px;
  border-radius: 8px;
}
.message.error ul {
  margin: 0;
  padding-left: 18px;
}

/* Tabla (si agregas últimas salidas) */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
  background: rgba(255, 255, 255, 0.05);
  color: #e0e7ff;
  border-radius: 12px;
  overflow: hidden;
}
thead {
  background: linear-gradient(135deg, #22d3ee, #2563eb);
  color: white;
}
th, td {
  padding: 12px 16px;
  text-align: left;
  white-space: nowrap;
}
tbody tr:hover {
  background: rgba(34, 211, 238, 0.1);
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
  }
  .main-content {
    padding: 20px;
  }
  .content {
    padding: 20px;
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
  }
  td {
    padding-left: 50%;
    position: relative;
  }
  td::before {
    position: absolute;
    left: 16px;
    top: 12px;
    font-weight: 600;
    color: #22d3ee;
  }
  td:nth-of-type(1)::before { content: "Producto"; }
  td:nth-of-type(2)::before { content: "Cantidad"; }
  td:nth-of-type(3)::before { content: "Motivo"; }
  td:nth-of-type(4)::before { content: "Fecha"; }
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
  <img src="../public/background.png" alt="Background" class="background-image" />

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
    <div class="menu-item open">
      <button type="button" class="menu-toggle">
        <i class="bi bi-box-arrow-up"></i> Salidas
        <i class="bi bi-chevron-down chevron"></i>
      </button>
      <div class="submenu">
        <a href="RegistrarSalida.php" class="active">Registrar Salida</a>
        <a href="Salidas.php">Reporte de Salidas</a>
        <!-- Puedes agregar más si necesitas -->
      </div>
    </div>

    <!-- Otros accesos -->
    <a href="ListarInventario.php"><i class="bi bi-archive"></i> Inventario</a>
    <a href="../Controladores/AuditoriaController.php"><i class="bi bi-clipboard-data"></i> Auditoría</a>
  </nav>

    <main class="main-content">
      <div class="header">
        <h1>Registrar Salida</h1>
        <div class="user-menu" id="userMenu">
          <button class="user-button" onclick="toggleDropdown()">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($correo) ?>
          </button>
          <div class="dropdown" id="dropdownMenu">
            <form action="../Controladores/AuthController.php" method="post" style="margin:0">
              <button type="submit" name="logout" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
            </form>
          </div>
        </div>
      </div>

      <section class="content">
        <?php if (!empty($mensaje_exito)): ?>
          <div class="message success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>
        <?php if (!empty($errores)): ?>
          <div class="message error">
            <ul>
              <?php foreach($errores as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <h2 style="margin-top:24px; color:#cbd5e1;">Nueva Salida</h2>
        <form action="../Controladores/RegistrarSalidaController.php" method="POST">
          <label for="producto_id">Producto:</label>
          <select id="producto_id" name="producto_id" required>
            <option value="">Seleccione un producto</option>
            <?php if ($productosParaSelect): foreach($productosParaSelect as $prod): ?>
              <option value="<?= $prod['id'] ?>">
                <?= htmlspecialchars($prod['nombre']) ?> (Stock: <?= $prod['cantidad_stock'] ?>)
              </option>
            <?php endforeach; else: ?>
              <option disabled>No hay productos disponibles</option>
            <?php endif; ?>
          </select>

          <label for="cantidad">Cantidad:</label>
          <input id="cantidad" type="number" name="cantidad" min="1" required placeholder="Ej: 5">

          <label for="descripcion">Motivo:</label>
          <textarea id="descripcion" name="descripcion" rows="3" required placeholder="Motivo de la salida"></textarea>

          <button type="submit"><i class="bi bi-box-arrow-up"></i> Guardar Salida </button>
        </form>
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