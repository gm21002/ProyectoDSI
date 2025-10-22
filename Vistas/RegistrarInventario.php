<?php
session_start();

require_once '../Modelos/CategoriaModel.php';
require_once '../Modelos/ProveedorModel.php';
require_once '../Modelos/ProductoModel.php';

$categoriaModel = new CategoriaModel();
$proveedorModel = new ProveedorModel();
$productoModel = new ProductoModel();

$categorias  = $categoriaModel->obtenerCategorias();   // arreglo asociativo
$proveedores = $proveedorModel->obtenerProveedores();  // arreglo asociativo
$productos     = $productoModel->listar();          // catálogo para dropdown
$correo = $_SESSION['usuario_correo'] ?? 'usuario@nextgen.com';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registrar Inventario - NextGen</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
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
    .container-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }
    .form-container {
      background: rgba(255, 255, 255, 0.07);
      backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 40px 30px;
      max-width: 700px;
      width: 100%;
      box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    }
    h1 {
      text-align: center;
      font-size: 2rem;
      margin-bottom: 24px;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    label {
      font-weight: 600;
      display: block;
      margin-top: 15px;
      margin-bottom: 5px;
      color: #cbd5e1;
    }
    input[type="text"], input[type="number"], textarea, select {
      width: 100%;
      padding: 10px 15px;
      border-radius: 10px;
      border: none;
      font-size: 1rem;
      background: rgba(255,255,255,0.1);
      color: #f0f4f8;
      outline: none;
    }
    input:focus, textarea:focus, select:focus {
      background: rgba(255,255,255,0.2);
      outline: 2px solid #22d3ee;
    }

    /* Estilo para date picker personalizado */
    input[type="date"] {
      width: 100%;
      padding: 10px 15px;
      border-radius: 10px;
      border: none;
      font-size: 1rem;
      background: rgba(255, 255, 255, 0.1);
      color: #f0f4f8;
      outline: none;
      appearance: none;
      position: relative;
    }

    /* Cambiar color del placeholder (para navegadores que lo soporten) */
    input[type="date"]::-webkit-datetime-edit-text,
    input[type="date"]::-webkit-datetime-edit-month-field,
    input[type="date"]::-webkit-datetime-edit-day-field,
    input[type="date"]::-webkit-datetime-edit-year-field {
      color: #cbd5e1;
    }

    /* Icono calendario invertido */
    input[type="date"]::-webkit-calendar-picker-indicator {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      width: 1.2em;
      height: 1.2em;
      color: #f0f4f8;
      filter: invert(1);
      cursor: pointer;
    }

    /* Fondo y borde al hacer focus */
    input[type="date"]:focus {
      background: rgba(255, 255, 255, 0.2);
      outline: 2px solid #22d3ee;
    }
    textarea { resize: vertical; }
    button {
      margin-top: 20px;
      padding: 12px;
      width: 100%;
      border: none;
      border-radius: 12px;
      font-size: 1.1rem;
      font-weight: 700;
      color: white;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      cursor: pointer;
      transition: background 0.3s ease;
    }
    button:hover { background: linear-gradient(135deg, #1fb9d9, #1d56be); }
    .error, .exito {
      text-align: center;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .error { background-color: #ff4c4c33; color: #ff6b6b; }
    .exito { background-color: #22c55e33; color: #4ade80; }

    #modalCategoria, #modalProveedor, #overlay { display:none; position:fixed; z-index:100; }
    #modalCategoria, #modalProveedor {
      top:50%; left:50%; transform:translate(-50%,-50%);
      background:#0f172a; padding:20px; border-radius:12px; box-shadow:0 0 12px rgba(0,0,0,0.5);
      color: #f0f4f8;
      width: 400px;
    }
    #modalProveedor input {
      width: 100%;
      margin-bottom: 15px;
    }
    #modalProveedor button {
      margin: 5px;
      width: calc(50% - 10px);
    }
    #overlay { top:0; left:0; width:100%; height:100%; background:#000000aa; z-index:50; }

    /* --- Toast notification --- */
    .toast-success {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #22c55e;
      color: #fff;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      font-weight: 600;
      display: none;          /* se muestra por JS */
      z-index: 200;
    }

    /* Dashboard layout styles */
    .dashboard-container {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }
    .sidebar {
      background-color: #1e293b;
      width: 220px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 15px;
      color: #cbd5e1;
    }
    .sidebar h2 {
      color: #22d3ee;
      margin-bottom: 20px;
      font-weight: 700;
      font-size: 1.5rem;
    }
    .sidebar a {
      color: #cbd5e1;
      text-decoration: none;
      font-weight: 600;
      padding: 10px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background-color 0.3s ease;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #2563eb;
      color: #fff;
    }
    .main-content {
      flex-grow: 1;
      background-color: #0a2540;
      padding: 20px 40px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }
    .header {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      color: #f0f4f8;
      margin-bottom: 30px;
    }
    .header h1 {
      margin: 0;
      font-weight: 700;
    }
    .user-menu {
      position: relative;
    }
    .user-button {
      background: none;
      border: none;
      color: #22d3ee;
      font-weight: 600;
      cursor: pointer;
      font-size: 1rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .dropdown {
      position: absolute;
      right: 0;
      top: 110%;
      background-color: #1e293b;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      display: none;
      min-width: 180px;
      z-index: 300;
    }
    .dropdown-item {
      background: none;
      border: none;
      color: #f0f4f8;
      width: 100%;
      padding: 12px 20px;
      text-align: left;
      cursor: pointer;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .dropdown-item:hover {
      background-color: #2563eb;
      color: #fff;
    }
    .content {
      flex-grow: 1;
      overflow-y: auto;
      /* Centrar formulario */
      display: flex;
      justify-content: center;   /* centra horizontalmente */
      align-items: flex-start;   /* deja un margen arriba en lugar de centro vertical completo */
      padding-top: 40px;         /* espacio superior */
    }
    .menu-item { display: flex; flex-direction: column; }
    .menu-toggle {
      background: none;
      border: none;
      color: inherit;
      font: inherit;
      padding: 10px;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      border-radius: 8px;
    }
    
    .menu-toggle:hover {
      background: rgba(255 255 255 / 0.1);
      color: #cbd5e1; 
    }
    .chevron { margin-left:auto; transition: transform .3s; }
    .submenu { display:none; flex-direction: column; margin-left: 24px; }
    .submenu a { padding: 8px; }
    .menu-item.open .submenu { display:flex; }
    .menu-item.open .chevron { transform: rotate(180deg); }
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

  <?php if (isset($_GET['exito'])): ?>
    <div id="toast" class="toast-success">
      ¡Inventario registrado correctamente!
    </div>
  <?php endif; ?>

    <section class="content">
      <div class="form-container">
        <h1>Registrar Inventario</h1>

        <?php if (!empty($_SESSION['errores'])): ?>
          <div class="error">
            <ul><?php foreach ($_SESSION['errores'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
          </div>
          <?php unset($_SESSION['errores']); ?>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'fecha'): ?>
          <div class="error">La fecha debe ser válida y estar en los últimos 30 días.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'maxstock'): ?>
          <div class="error">La cantidad excede el stock máximo permitido para este producto.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'campos'): ?>
          <div class="error">Por favor complete todos los campos obligatorios correctamente.</div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['exito'])): ?>
          <div class="exito"><?= htmlspecialchars($_SESSION['exito']); ?></div>
          <?php unset($_SESSION['exito']); ?>
        <?php endif; ?>

        <form method="POST" action="../Controladores/InventarioController.php">
          <label>Producto*:</label>
          <select name="producto_id" required>
            <option value="">Seleccione producto</option>
            <?php foreach ($productos as $p): ?>
              <option 
                value="<?= $p['id'] ?>" 
                data-max="<?= htmlspecialchars($p['stock_maximo'] ?? '') ?>"
              >
                <?= htmlspecialchars($p['codigo'].' - '.$p['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Proveedor*:</label>
          <select id="proveedor" name="proveedor_id" required>
            <option value="">Seleccione proveedor</option>
            <?php foreach ($proveedores as $prov): ?>
              <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" id="btnAgregarProveedor">+ Nuevo Proveedor</button>

          <!-- CAMPO 1: Descripción para la tabla inventario -->
          <label>Descripción del Producto:</label>
          <textarea name="descripcion_inventario" rows="2" placeholder="Descripción general del producto..." required></textarea>

          <label>Precio*:</label>
          <input type="number" name="precio" step="0.01" min="0.01" required>

          <label>Cantidad*:</label>
          <input type="number" name="cantidad_stock" min="1" required>

          <!-- CAMPO 2: Motivo para la tabla movimientos -->
          <label>Motivo del Movimiento*:</label>
          <textarea name="descripcion" rows="3" placeholder="Describa el motivo del movimiento (compra, ajuste, devolución, etc.)..." required></textarea>

          <label>Fecha de Ingreso*:</label>
          <input 
            type="date" 
            name="fecha_ingreso" 
            required 
            max="<?= date('Y-m-d') ?>" 
            min="<?= date('Y-m-d', strtotime('-30 days')) ?>" 
          >

          <button type="submit"><i class="bi bi-save"></i> Guardar Inventario</button>
        </form>
      </div>
    </section><!-- end content -->

  <!-- Modales -->
  <div id="modalProveedor">
    <h3>Nuevo Proveedor</h3>
    <input type="text" id="nombreProveedor" placeholder="Nombre proveedor">
    <button id="guardarProveedor">Guardar Proveedor</button>
    <button id="cerrarModalProveedor">Cancelar</button>
  </div>

  <div id="overlay"></div>

  <script>
    const overlay = document.getElementById('overlay');

    // Centra el modal en relación con el contenedor del formulario
    function centerModal(modalEl){
      const formRect = document.querySelector('.form-container').getBoundingClientRect();
      modalEl.style.top  = (formRect.top  + formRect.height / 2) + 'px';
      modalEl.style.left = (formRect.left + formRect.width  / 2) + 'px';
      modalEl.style.transform = 'translate(-50%, -50%)';
    }


    // Modal Proveedor
    document.getElementById('btnAgregarProveedor').onclick = () => {
      const modalProv = document.getElementById('modalProveedor');
      centerModal(modalProv);
      modalProv.style.display = 'block';
      overlay.style.display = 'block';
    };
    document.getElementById('cerrarModalProveedor').onclick = () => {
      document.getElementById('modalProveedor').style.display = 'none';
      overlay.style.display = 'none';
      document.getElementById('nombreProveedor').value = '';
    };


    // Guardar nuevo proveedor vía AJAX
    document.getElementById('guardarProveedor').onclick = () => {
      const nombre = document.getElementById('nombreProveedor').value.trim();
      if (nombre.length < 3) { alert('El nombre debe tener al menos 3 caracteres.'); return; }

      fetch('../Controladores/ProveedorController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nombre=' + encodeURIComponent(nombre)
      })
      .then(async res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        if (data.exito) {
          const select = document.getElementById('proveedor');
          const opt    = document.createElement('option');
          opt.value    = data.id;
          opt.text     = nombre;
          opt.selected = true;
          select.appendChild(opt);
          document.getElementById('modalProveedor').style.display = 'none';
          overlay.style.display = 'none';
        } else {
          alert(data.mensaje || 'Error al guardar proveedor.');
        }
      })
      .catch(err => {
        console.error(err);
        alert('No se pudo guardar el proveedor (' + err.message + ')');
      });
    };

    // Validar stock máximo por producto
    const prodSelect = document.querySelector('select[name="producto_id"]');
    const qtyInput   = document.querySelector('input[name="cantidad_stock"]');

    function checkMax() {
      const opt = prodSelect.selectedOptions[0];
      const max = parseInt(opt.dataset.max || '0', 10);
      const qty = parseInt(qtyInput.value || '0', 10);
      if (max > 0 && qty > max) {
        alert(Atención: este producto permite un stock máximo de ${max} unidades.);
      }
    }

    prodSelect.addEventListener('change', checkMax);
    qtyInput.addEventListener('input', checkMax);

    // Mostrar y ocultar toast de éxito
    const toast = document.getElementById('toast');
    if (toast) {
      toast.style.display = 'block';
      setTimeout(() => toast.style.display = 'none', 3000);
    }

    // Toggle dropdown menu
    function toggleDropdown() {
      const dropdown = document.getElementById('dropdownMenu');
      if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
      } else {
        dropdown.style.display = 'block';
      }
    }

    // toggling sidebar submenu
    document.querySelectorAll('.menu-toggle').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        btn.parentElement.classList.toggle('open');
      });
    });

    // Close dropdown if clicked outside
    window.addEventListener('click', function(event) {
      const userMenu = document.getElementById('userMenu');
      if (!userMenu.contains(event.target)) {
        document.getElementById('dropdownMenu').style.display = 'none';
      }
    });

    // Recentrar modal visible al redimensionar ventana
    window.addEventListener('resize', ()=>{
      ['modalProveedor'].forEach(id=>{
        const m=document.getElementById(id);
        if(m.style.display==='block'){
          centerModal(m);
        }
      });
    });
  </script>
</body>
</html>