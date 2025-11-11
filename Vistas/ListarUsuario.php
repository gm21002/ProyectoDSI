<?php
session_start();
if (!isset($_SESSION['usuario_correo'])) {
        header('Location: login.php');
        exit();
}

$rolesPermitidos = ['Administrador']; // Solo administradores
require_once "../Controladores/AuthRol.php"; 

$correo = $_SESSION['usuario_correo'];
require_once "../Modelos/UsuarioModel.php";
$usuarioModel = new UsuarioModel();
$usuarios = $usuarioModel->obtenerTodosUsuarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Listar Usuarios - NextGen Distributors</title>
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
            max-width: 1000px;
            background: rgba(255 255 255 / 0.07);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            text-align: left;
            color: #cbd5e1;
            margin-top: 40px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #22d3ee, #2563eb);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 18px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #22d3ee);
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
        .acciones a {
            color: #38bdf8;
            text-decoration: none;
            margin-right: 10px;
            font-weight: 600;
            transition: color 0.2s;
        }
        .acciones a:hover {
            color: #fbbf24;
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
            .content {
                margin-top: 20px;
                padding: 20px 10px;
            }
        }
        .menu-item{display:flex;flex-direction:column;}
        .menu-toggle{
            background:none;border:none;color:inherit;font:inherit;padding:10px;
            display:flex;align-items:center;gap:8px;cursor:pointer;border-radius:8px;text-align:left;
        }
        .menu-toggle:hover {
            background: rgba(255 255 255 / 0.1);
            color: #cbd5e1;
        }
        .chevron{margin-left:auto;transition:transform .3s;}
        .submenu{display:none;flex-direction:column;margin-left:24px;}
        .submenu a{padding:8px;}
        .menu-item.open .submenu{display:flex;}
        .menu-item.open .chevron{transform:rotate(180deg);}
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
            <!-- Submenú Usuarios -->
            <div class="menu-item">
                <button type="button" class="menu-toggle">
                    <i class="bi bi-people"></i> Usuarios
                    <i class="bi bi-chevron-down chevron"></i>
                </button>
                <div class="submenu">
                    <a href="RegistrarUsuario.php">Registrar Usuario</a>
                    <a href="ListarUsuario.php">Listar Usuario</a>
                </div>
            </div>
        </nav>
        <main class="main-content">
            <header class="header">
                <h1>Gestión de Usuarios</h1>
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
               <a href="RegistrarUsuario.php" class="btn-primary">
                  <i class="bi bi-person-plus" style="margin-right: 8px;"></i> Crear Nuevo Usuario
               </a>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td><?= $u['nombre_usuario'] ?></td>
                                <td><?= $u['correo'] ?></td>
                                <td><?= $u['rol'] ?></td>
                                <td class="acciones">
                                   <a href="EditarUsuario.php?id=<?= urlencode((string)$u['id']) ?>"><i class="bi bi-pencil-square"></i> Editar</a>
                                        <form action="../Controladores/UsuarioController.php" method="post" style="display:inline"
                                            onsubmit="return confirm('¿Eliminar usuario? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$u['id']) ?>">
                                        <button type="submit" style="background:none;border:none;color:#f87171;cursor:pointer;font-weight:600;padding:0">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                        </form>                                
                                    </td>
                            </tr>
                            <?php endforeach; ?><?php else: ?>
                                <tr><td colspan="5">No hay usuarios registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
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
</body>
</html>
