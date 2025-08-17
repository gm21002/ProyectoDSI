<?php
session_start();
require_once '../Modelos/Conexion.php';
require_once '../Modelos/LimiteStockModel.php';
require_once '../Modelos/ProductoModel.php';
require_once '../Modelos/CategoriaModel.php';

$correo = $_SESSION['usuario_correo'] ?? 'usuario@nextgen.com';

$limiteModel = new LimiteStockModel();
$limites = $limiteModel->listarTodo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Límites de Stock - NextGen</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
    *{box-sizing:border-box;}
    body,html{margin:0;height:100%;font-family:'Poppins',sans-serif;background:#0a2540;color:#f0f4f8;}
    .background-image{position:fixed;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:-1;filter:brightness(.7);}
    .dashboard-container{display:flex;height:100vh;overflow:hidden;}
    .sidebar{
      background-color:#1e293b;width:220px;padding:20px;display:flex;flex-direction:column;gap:15px;color:#cbd5e1;
    }
    .sidebar h2{color:#22d3ee;margin-bottom:20px;font-weight:700;font-size:1.5rem;}
    .sidebar a{color:inherit;text-decoration:none;font-weight:600;padding:10px;border-radius:8px;display:flex;align-items:center;gap:8px;transition:background-color .3s;}
    .sidebar a:hover,.sidebar a.active{background-color:#2563eb;color:#fff;}
    .main-content{flex-grow:1;padding:20px 40px;background:#0a2540;overflow-y:auto;display:flex;flex-direction:column;}
    .header{display:flex;justify-content:flex-end;align-items:center;color:#f0f4f8;margin-bottom:30px;}
    .user-menu{position:relative;}
    .user-button{background:none;border:none;color:#22d3ee;font-weight:600;cursor:pointer;font-size:1rem;display:flex;align-items:center;gap:6px;}
    .dropdown{position:absolute;right:0;top:110%;background:#1e293b;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.3);display:none;min-width:180px;}
    .dropdown-item{background:none;border:none;color:#f0f4f8;width:100%;padding:12px 20px;text-align:left;font-weight:600;display:flex;align-items:center;gap:8px;}
    .dropdown-item:hover{background:#2563eb;color:#fff;}
    .content h2{margin-bottom:10px;}
    .btn-primary{margin:10px 0;padding:10px 16px;border:none;border-radius:8px;background:linear-gradient(135deg,#22d3ee,#2563eb);color:#fff;font-weight:600;cursor:pointer;}
    .btn-primary:hover{background:linear-gradient(135deg,#1fb9d9,#1d56be);}
    table{width:100%;border-collapse:collapse;background:rgba(255,255,255,.07);border-radius:12px;overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,.3);color:#e0e7ff;margin-top:16px;}
    th,td{padding:12px 16px;text-align:left;}
    thead{background:linear-gradient(135deg,#22d3ee,#2563eb);color:#fff;}
    tbody tr:nth-child(even){background:rgba(255,255,255,.05);}
    .badge{display:inline-block;padding:.35em .65em;font-size:.75rem;font-weight:700;border-radius:.25rem;}
    .bg-danger{background:#dc3545;color:#fff;}
    .bg-info{background:#0dcaf0;color:#000;}
    .bg-success{background:#198754;color:#fff;}
    .acciones button{margin-right:6px;}
  </style>
</head>
<body>
<img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a607f958-7370-428e-bc1b-2af0eb7d51dd.png" class="background-image" alt="Fondo" />
<div class="dashboard-container">
  <nav class="sidebar">
    <h2>NextGen Distributors</h2>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="ListarInventario.php"><i class="bi bi-archive"></i> Inventario</a>
    <a href="RegistrarLimiteStock.php"><i class="bi bi-plus-lg"></i> Agregar Límite</a>
    <a href="ListarLimites.php" class="active"><i class="bi bi-list-ul"></i> Ver Límites</a>
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
      <h2>Límites de Stock Configurados</h2>
      <a href="RegistrarLimiteStock.php" class="btn-primary"><i class="bi bi-plus-lg"></i> Agregar nuevo límite</a>
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Límite Mínimo</th>
            <th>Límite Máximo</th>
            <th>Categoría</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($limites as $l): ?>
            <tr>
              <td><?= htmlspecialchars($l['producto']) ?></td>
              <td><?= htmlspecialchars($l['stock_minimo']) ?></td>
              <td><?= htmlspecialchars($l['stock_maximo']) ?></td>
              <td>
                <?php
                  if ($l['categoria_id']) {
                    $catModel = new CategoriaModel();
                    $cat = $catModel->obtenerPorId($l['categoria_id']);
                    echo htmlspecialchars($cat['nombre'] ?? 'Sin categoría');
                  } else {
                    echo '—';
                  }
                ?>
              </td>
              <td class="acciones">
                <form action="EditarLimite.php" method="get" style="display:inline;">
                  <input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <button class="btn-primary" title="Editar"><i class="bi bi-pencil"></i></button>
                </form>
                <form action="../Controladores/EliminarLimite.php" method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este límite?');">
                  <input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <button class="btn-primary" title="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</div>
<script>
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
</script>
</body>
</html>
