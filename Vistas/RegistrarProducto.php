<?php
session_start();
require_once '../Modelos/CategoriaModel.php';

$categoriaModel = new CategoriaModel();
$categorias     = $categoriaModel->obtenerCategorias();
$correo = $_SESSION['usuario_correo'] ?? 'usuario@nextgen.com';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registrar Producto - NextGen</title>
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
      filter: brightness(0.6);
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
      border-right: 1px solid rgba(255,255,255,0.1);
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

    .menu-item { display: flex; flex-direction: column; }
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

    .menu-item.open .submenu { display: flex; }
    .menu-item.open .chevron { transform: rotate(180deg); }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .form-wrapper {
      flex: 1;
      display: flex;
      justify-content: center;   /* horizontal center */
      align-items: flex-start;   /* start vertical (top) */
      padding-top: 80px;         /* separacion para bajar el formulario */
      padding-left: 40px;
      padding-right: 40px;
      padding-bottom: 40px;
    }

    .header {
      width: 100%;
      max-width: 1400px;
      display: flex;
      justify-content: flex-end;
      padding: 20px 40px 0 40px;
    }

    .user-menu { position: relative; }
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
    }

    .user-button:hover {
      background: rgba(255 255 255 / 0.2);
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
    }

    .dropdown-item {
      padding: 10px 20px;
      color: #f0f4f8;
      font-weight: 600;
      text-align: left;
      background: none;
      border: none;
      cursor: pointer;
    }

    .dropdown-item:hover {
      background: rgba(255 255 255 / 0.1);
    }

    .user-menu.active .dropdown { display: block; }

    .form-container {
      background: rgba(255 255 255 / 0.07);
      backdrop-filter: blur(10px);
      padding: 40px;
      border-radius: 16px;
      width: 100%;
      max-width: 700px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    .form-container h1 {
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 24px;
    }

    .form-container label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
    }

    .form-container input,
    .select-custom {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
    }

    .form-container input:hover,
    .select-custom:hover {
      background-color: #f1f5f9;
    }

    .form-container button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      color: white;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: box-shadow 0.3s;
    }

    .form-container button:hover {
      background: #1d4ed8;
      box-shadow: 0 0 10px rgba(37, 99, 235, 0.5);
    }

    .toast-success {
      background: #22c55e;
      padding: 12px 20px;
      border-radius: 12px;
      color: white;
      position: fixed;
      top: 16px;
      right: 16px;
      z-index: 9999;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
      display: none;
    }

    .error {
      background: #ef4444;
      color: white;
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
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
        padding: 16px;
      }

      .form-wrapper {
        padding: 20px;
      }

      .form-container {
        padding: 20px;
      }
    }

    .select-custom {
      background-color: #ffffff;
      color: #111827;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23111827' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 1rem;
      transition: background-color 0.3s, box-shadow 0.3s;
    }

    .select-custom:focus {
      outline: none;
      box-shadow: 0 0 0 2px #2563eb;
      background-color: #f9fafb;
    }

    .select-custom option {
      color: #111827;
    }
  </style>
</head>
<body>
  <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a607f958-7370-428e-bc1b-2af0eb7d51dd.png" alt="Background" class="background-image">

  <div class="dashboard-container">
  <?php include 'sidebar.php'; ?>
  
    <main class="main-content">
      <header class="header">
        <div class="user-menu" id="userMenu">
          <button class="user-button" onclick="toggleDropdown()">
            <i class="bi bi-person-circle"></i>&nbsp; <?= htmlspecialchars($correo) ?>
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

      <?php if (isset($_GET['exito'])): ?>
        <div id="toast" class="toast-success">
          ¡Producto ingresado correctamente!<br>
          <?php if (!empty($_GET['codigo'])): ?>
            Código: <?= htmlspecialchars($_GET['codigo']) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <section class="form-wrapper">
        <div class="form-container">
          <h1>Registrar Producto</h1>

          <?php if (!empty($_GET['error'])): ?>
            <div class="error">Error: complete todos los campos obligatorios.</div>
          <?php endif; ?>

          <form action="../Controladores/ProductoController.php" method="POST">
            <label for="nombre">Nombre del Producto*:</label>
            <input type="text" id="nombre" name="nombre" required maxlength="255" placeholder="Ej: Mouse óptico gamer">

            <label for="categoria">Categoría*:</label>
            <select name="categoria_id" id="categoria" required class="select-custom">
              <option value="">Seleccione...</option>
              <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>

            <button type="submit"><i class="bi bi-save"></i>&nbsp; Guardar Producto</button>
          </form>
        </div>
      </section>
    </main>
  </div>

  <script>
    const toast = document.getElementById('toast');
    if (toast) { toast.style.display = 'block'; setTimeout(()=>toast.style.display='none', 3000); }

    function toggleDropdown() {
      const d=document.getElementById('dropdownMenu');
      d.style.display=d.style.display==='block'?'none':'block';
    }
    window.addEventListener('click',e=>{
      if(!document.getElementById('userMenu').contains(e.target)){
        document.getElementById('dropdownMenu').style.display='none';
      }
    });

    document.querySelectorAll('.menu-toggle').forEach(btn=>{
      btn.addEventListener('click', () => {
        btn.parentElement.classList.toggle('open');
      });
    });
  </script>
</body>
</html>
