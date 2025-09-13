<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NextGen Distributors Login</title>

  <!-- Bootstrap CSS (para toasts) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
    * { box-sizing: border-box; }
    body, html {
      margin: 0;
      height: 100%;
      font-family: 'Poppins', sans-serif;
      overflow: hidden;
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
    .login-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 20px;
      text-align: center;
    }
    .logo-container {
      display: flex;
      align-items: center;
      gap: 16px;
      background: rgba(255 255 255 / 0.05);
      border-radius: 16px;
      padding: 16px 24px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
      margin-bottom: 40px;
      max-width: 320px;
      user-select: none;
    }
    .logo-container svg {
      width: 56px;
      height: 56px;
      flex-shrink: 0;
      stroke-width: 2;
    }
    .icon-box {
      stroke-linejoin: round;
      stroke-linecap: round;
      stroke: url(#blue-green-gradient);
      fill: url(#blue-green-gradient);
      transition: fill 0.3s ease;
    }
    .icon-box:hover {
      stroke: #06b6d4;
      fill: #06b6d4;
      cursor: pointer;
    }
    .text-nextgen {
      font-weight: 800;
      font-size: 2rem;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      white-space: nowrap;
      margin: 0;
    }
    .text-distributors {
      font-weight: 600;
      font-size: 0.95rem;
      color: #a5b8cc;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      margin-top: 4px;
    }
    .text-container {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      line-height: 1.1;
      height: 100%;
    }
    form.login-form {
      background: rgba(255 255 255 / 0.07);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 32px 28px;
      max-width: 360px;
      width: 100%;
      box-shadow: 0 15px 40px rgba(0,0,0,0.5);
    }
    form.login-form h2 {
      font-weight: 700;
      margin-bottom: 24px;
      font-size: 1.8rem;
      color: #e0e7ff;
    }
    form.login-form label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 0.95rem;
      color: #cbd5e1;
    }
    .input-icon {
      position: relative;
      margin-bottom: 20px;
    }
    .input-icon i {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
    }
    .input-icon i.left {
      left: 16px;
    }
    .input-icon i.toggle-password {
      right: 16px;
      cursor: pointer;
    }
    .input-icon input {
      width: 100%;
      padding: 12px 42px 12px 42px;
      border-radius: 12px;
      border: none;
      font-size: 1rem;
      background: rgba(255 255 255 / 0.1);
      color: #f0f4f8;
      transition: background-color 0.3s ease;
      outline-offset: 2px;
    }
    .input-icon input::placeholder {
      color: #94a3b8;
    }
    .input-icon input:focus {
      background: rgba(255 255 255 / 0.2);
      outline: 2px solid #22d3ee;
      color: #fff;
    }
    form.login-form button {
      width: 100%;
      background: linear-gradient(135deg, #22d3ee, #2563eb);
      border: none;
      border-radius: 12px;
      padding: 14px 0;
      font-weight: 700;
      font-size: 1.1rem;
      color: white;
      cursor: pointer;
      transition: background 0.3s ease;
      box-shadow: 0 8px 20px rgba(34, 211, 238, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    form.login-form button:hover {
      background: linear-gradient(135deg, #1fb9d9, #1d56be);
      box-shadow: 0 12px 28px rgba(29, 86, 190, 0.9);
    }
    footer {
      margin-top: 48px;
      color: #ffffff;
    }
    @media (max-width: 480px) {
      .logo-container {
        padding: 12px 16px;
        max-width: 280px;
        gap: 12px;
      }
      form.login-form {
        max-width: 280px;
        padding: 28px 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Imagen de fondo -->
  <img
    src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/a607f958-7370-428e-bc1b-2af0eb7d51dd.png"
    alt="Background"
    class="background-image"
    onerror="this.style.display='none'"
  />

<?php if (isset($_GET['error'])): ?>
  <div id="toast-error" class="toast align-items-center text-bg-danger border-0 position-fixed top-0 end-0 m-4" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 9999;">
    <div class="d-flex">
      <div class="toast-body">
        <?php echo htmlspecialchars($_GET['error']); ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
<?php endif; ?>


  <!-- Contenido principal -->
  <div class="login-wrapper">
    <div class="logo-container">
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="blue-green-gradient" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#22d3ee" />
            <stop offset="100%" stop-color="#2563eb" />
          </linearGradient>
        </defs>
        <rect x="12" y="12" width="20" height="20" class="icon-box" rx="3" ry="3" />
        <rect x="32" y="20" width="20" height="20" class="icon-box" rx="3" ry="3" />
        <line class="icon-box" x1="32" y1="20" x2="20" y2="32" />
        <line class="icon-box" x1="32" y1="40" x2="44" y2="28" />
      </svg>
      <div class="text-container">
        <p class="text-nextgen">NextGen</p>
        <p class="text-distributors">Distributors</p>
      </div>
    </div>

    <form class="login-form" action="../Controladores/AuthController.php" method="post">
      <h2>Iniciar Sesión</h2>

      <label for="username">Correo electrónico</label>
      <div class="input-icon">
        <i class="bi bi-envelope-fill left"></i>
        <input type="text" id="username" name="correo" placeholder="Ingrese su correo electrónico" required autocomplete="username" />
      </div>

      <label for="password">Contraseña</label>
      <div class="input-icon">
        <i class="bi bi-lock-fill left"></i>
        <input type="password" id="password" name="contrasena" placeholder="Ingrese su contraseña" required autocomplete="current-password" />
        <i class="bi bi-eye-fill toggle-password" onclick="togglePassword()"></i>
      </div>

      <button type="submit"><i class="bi bi-box-arrow-in-right"></i> Acceder</button>
    </form>

    <footer class="mt-4 small text-center">
      &copy; <?php echo date('Y'); ?> NextGen Distributors. Todos los derechos reservados.
    </footer>
  </div>

  <!-- Bootstrap JS (para toast) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById("password");
      const toggleIcon = document.querySelector(".toggle-password");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.classList.remove("bi-eye-fill");
        toggleIcon.classList.add("bi-eye-slash-fill");
      } else {
        passwordInput.type = "password";
        toggleIcon.classList.remove("bi-eye-slash-fill");
        toggleIcon.classList.add("bi-eye-fill");
      }
    }
  </script>

  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const toastEl = document.getElementById('toast-error');
      if (toastEl) {
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
      }

      if (window.location.search.includes('error=')) {
        const cleanUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
      }
    });
</script>
</body>
</html>

