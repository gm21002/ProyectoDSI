<?php
// sidebar.php
if (!isset($_SESSION)) session_start();
$rol = $_SESSION['usuario_rol'] ?? '';
$correo = $_SESSION['usuario_correo'] ?? '';
?>

<nav class="sidebar" aria-label="Navegación principal">
    <h2>NextGen Distributors</h2>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>

    <!-- Entradas -->
    <div class="menu-item">
        <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="submenu-entradas">
            <i class="bi bi-box-arrow-in-down"></i> Entradas
            <i class="bi bi-chevron-down chevron" aria-hidden="true"></i>
        </button>
        <div id="submenu-entradas" class="submenu" role="menu" aria-hidden="true">
            <a href="ListarInventario.php" role="menuitem">Inventario</a>
            <a href="ListarProducto.php" role="menuitem">Productos</a>
            <a href="ListarCategoria.php" role="menuitem">Categorías</a>
            <a href="ListarProveedor.php" role="menuitem">Proveedores</a>
        </div>
    </div>

    <!-- Salidas -->
    <div class="menu-item">
        <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="submenu-salidas">
            <i class="bi bi-box-arrow-up"></i> Salidas
            <i class="bi bi-chevron-down chevron" aria-hidden="true"></i>
        </button>
        <div id="submenu-salidas" class="submenu" role="menu" aria-hidden="true">
            <a href="RegistrarSalida.php" role="menuitem">Registrar Salida</a>
            <a href="Salidas.php" role="menuitem">Reporte de Salidas</a>
        </div>
    </div>

    <a href="../Vistas/ListarInventario.php"><i class="bi bi-archive"></i> Inventario</a>

    <!-- Auditoría -->
    <div class="menu-item">
        <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="submenu-auditoria">
            <i class="bi bi-clipboard-data"></i> Auditoría
            <i class="bi bi-chevron-down chevron" aria-hidden="true"></i>
        </button>
        <div id="submenu-auditoria" class="submenu" role="menu" aria-hidden="true">
            <a href="../Controladores/AuditoriaController.php" role="menuitem">Movimientos</a>
            <a href="../Vistas/Historico.php" role="menuitem">Historico</a>
        </div>
    </div>

    <!-- Usuarios (solo Administrador) -->
    <?php if ($rol === 'Administrador'): ?>
    <div class="menu-item">
        <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="submenu-usuarios">
            <i class="bi bi-people"></i> Usuarios
            <i class="bi bi-chevron-down chevron" aria-hidden="true"></i>
        </button>
        <div id="submenu-usuarios" class="submenu" role="menu" aria-hidden="true">
            <a href="RegistrarUsuario.php" role="menuitem">Registrar Usuario</a>
            <a href="ListarUsuario.php" role="menuitem">Listar Usuario</a>
        </div>
    </div>
    <?php endif; ?>
</nav>

<script>
/*
  Robust sidebar submenu handler:
  - Registra listeners en DOMContentLoaded
  - Event delegation fallback (por si el sidebar se inyecta o scripts bloquean)
  - Actualiza atributos aria-expanded / aria-hidden para accesibilidad
*/
(function () {
  function initSidebar() {
    // function to toggle a single menu item
    function toggleMenuItem(btn) {
      const menuItem = btn.closest('.menu-item');
      if (!menuItem) return;
      const submenu = menuItem.querySelector('.submenu');
      const chevron = menuItem.querySelector('.chevron');
      if (!submenu) return;

      const isOpen = menuItem.classList.toggle('open');
      // Accessibility attributes
      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      submenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      if (chevron) {
        if (isOpen) chevron.style.transform = 'rotate(180deg)';
        else chevron.style.transform = '';
      }
    }

    // Attach click listeners directly to existing buttons
    const buttons = document.querySelectorAll('.sidebar .menu-toggle');
    if (buttons.length) {
      buttons.forEach(btn => {
        // ensure button type is button (prevents accidental form submit)
        if (btn.getAttribute('type') !== 'button') btn.setAttribute('type', 'button');

        // remove any previous duplicate listeners by cloning (safe)
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          toggleMenuItem(newBtn);
        });
      });
    }

    // Fallback: event delegation (handles cases where menu-toggle added later)
    document.addEventListener('click', function (e) {
      // if clicked a menu-toggle or inside one
      const potential = e.target.closest && e.target.closest('.sidebar .menu-toggle');
      if (potential) {
        e.preventDefault();
        toggleMenuItem(potential);
      }
    });

    // Optional: close all submenus when clicking outside sidebar
    document.addEventListener('click', function (e) {
      const sidebar = document.querySelector('.sidebar');
      if (!sidebar) return;
      if (!sidebar.contains(e.target)) {
        sidebar.querySelectorAll('.menu-item.open').forEach(item => {
          item.classList.remove('open');
          const btn = item.querySelector('.menu-toggle');
          const submenu = item.querySelector('.submenu');
          const chevron = item.querySelector('.chevron');
          if (btn) btn.setAttribute('aria-expanded', 'false');
          if (submenu) submenu.setAttribute('aria-hidden', 'true');
          if (chevron) chevron.style.transform = '';
        });
      }
    }, true);
  }

  // Run init after DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
  } else {
    initSidebar();
  }
})();
</script>
