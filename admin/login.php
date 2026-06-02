<?php
session_start();
require_once __DIR__ . '/connection/archivo_conexion.php';
header("Cache-control:no-store, no-cache, must-revalidate, max-age=0");
header("Cache-control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_SESSION['tip_user'])) {
  $rol = $_SESSION['tip_user'];
  $rutas = [
    'administrador' => 'admin/index_admin.php',
    'cliente'       => 'cli/index_cli.php',
    'vendedor'      => 'ven/index_ven.php',
  ];
  header('Location: ' . ($rutas[$rol] ?? 'login.php'));
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Formato de email invalido.";
  } else {
    try {
      $db  = new Database();
      $pdo = $db->conectar();
      if (!$pdo) throw new Exception('Error de conexion');
      $sql = "
                SELECT u.documento, u.nombre, u.email, u.password,t.id_tip_user, t.tip_user
                FROM user u
                INNER JOIN type_user t ON u.id_tip_user=t.id_tip_user
                WHERE u.email=?";

      $stmt = $pdo->prepare($sql);
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($user && password_verify($password, $user['password'])) {
      session_regenerate_id(true);
        $_SESSION['documento']   = $user['documento'];
        $_SESSION['nombre']      = $user['nombre'];
        $_SESSION['email']       = $user['email'];
        $_SESSION['id_tip_user'] = $user['id_tip_user'];
        $_SESSION['tip_user']    = strtolower(trim($user['tip_user'])); // ✅ tip_user
        $_SESSION['login_time']  = time();

         $rol = $_SESSION['tip_user'];
         $rutas = [
            'administrador' => 'admin/index_admin.php',  
            'cliente'       => 'cli/index_cli.php',       
            'vendedor'      => 'ven/index_ven.php',       
          ];
           header('Location: ' . ($rutas[$rol] ?? 'login.php'));
           exit;
      } else {
        $error = "Correo o contraseña incorrectos.";
      }
    } catch (Exception $e) {
      error_log("Login error: ". $e->getMessage());
      $error='Error interno. Intente más tarde';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TiendaPro | Iniciar Sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }

    .card {
      width: 100%;
      max-width: 420px;
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }

    .brand {
      color: #2563eb;
      font-size: 22px;
      font-weight: 600;
    }

    .btn-primary {
      background-color: #2563eb;
      border: none;
    }

    .btn-primary:hover {
      background-color: #1d4ed8;
    }

    .form-control:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .icon-circle {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: #eff6ff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
    }

    .icon-circle svg {
      width: 28px;
      height: 28px;
      color: #2563eb;
    }
  </style>
</head>

<body>
  <div class="card p-4 p-md-5">
    <div class="text-center mb-4">
      <div class="icon-circle">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 11H4L5 9z" />
        </svg>
      </div>
      <h1 class="brand">TiendaPro</h1>
      <p class="text-muted" style="font-size:14px;">Ingresa tus credenciales para continuar</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 py-2" style="font-size:14px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 3a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 8 4zm0 7a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label" style="font-size:14px;">Correo electrónico</label>
        <input type="email" name="email" class="form-control"
          placeholder="admin@tiendapro.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required>
      </div>
      <div class="mb-2">
        <label class="form-label" style="font-size:14px;">Contraseña</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <div class="text-end mb-3">
        <a href="#" style="font-size:13px; color:#2563eb; text-decoration:none;">¿Olvidaste tu contraseña?</a>
      </div>
      <button type="submit" class="btn btn-primary w-100">Ingresar</button>

    </form>

    <div class="text-center mb-4">
      <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn btn-index btn-sm px-3">
          <i class="fas fa-user-lock me-1"></i><-Volver al incio
            </a>
      </div>
    </div>
</body>

</html>
