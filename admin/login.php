<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Cabeceras de seguridad para evitar caché 
header("Cache-control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$error = '';
$logout_ok = isset($_GET['logout']);

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento = trim($_POST['documento'] ?? '');
    $pin       = trim($_POST['pin'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($documento) || empty($pin) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            $db  = new Database();
            $pdo = $db->conectar();
            if (!$pdo) throw new Exception('Error de conexión a la base de datos.');

            // Consulta SQL estructurada para obtener el usuario con su tipo, validando que esté activo
            $sql = "SELECT u.documento, u.pin, u.password, u.nombre_completo, t.nombre_tipo_usuario
                    FROM user u
                    INNER JOIN tipo_usuario t ON u.id_tipo_usuario = t.id_tipo_usuario
                    WHERE u.documento = :documento AND u.estado = 'activo'";

            $stmt = $pdo->prepare($sql);
            
            $stmt->bindValue(':documento', (int)$documento, PDO::PARAM_INT);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Validar PIN 
                $pin_valido = ($pin === $user['pin'] || password_verify($pin, $user['pin']));
                
                // Validar Contraseña con password_verify() obligatoria
                if ($pin_valido && password_verify($password, $user['password'])) {
                    
                    // Control dinámico de roles según tu solicitud anterior
                    if (strtolower($user['nombre_tipo_usuario']) === 'administrador') {
                        session_regenerate_id(true);
                        $_SESSION['admin_id']        = $user['documento']; 
                        $_SESSION['nombre_completo'] = $user['nombre_completo'];
                        $_SESSION['login_time']      = time();
                        
                        header('Location: dashboard.php'); // Redirección limpia al menú principal del rol admin 
                        exit;
                    } 
                    else if (strtolower($user['nombre_tipo_usuario']) === 'empleado') {
                        // Si es empleado se manda al index público sin crear sesión administrativa
                        header('Location: ../index.php');
                        exit;
                    }
                }
            }
            
            $error = "Credenciales incorrectas o acceso no autorizado.";
            
        } catch (Exception $e) {
            // mostrar la causa exacta si vuelve a fallar
            $error = 'Error de Sistema: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Sistema de asistencias </title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #1a1576; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .card { width: 100%; max-width: 420px; border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .brand { color: #3730a3; font-size: 22px; font-weight: 600; }
    .btn-primary { background-color: #4f46e5; border: none; }
    .btn-primary:hover { background-color: #1d4ed8; }
    .icon-circle { width: 56px; height: 56px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
    .icon-circle svg { width: 28px; height: 28px; color: #2563eb; }
  </style>
</head>
<body>
  <div class="card p-4 p-md-5">
    <div class="text-center mb-4">
      <div class="icon-circle">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
          <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
          <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
        </svg>
      </div>
      <h1 class="brand">Control de Asistencias</h1>
      <p class="text-muted small">Panel Administrativo</p>
    </div>

    <?php if ($logout_ok): ?>
      <div class="alert alert-success py-2 small">Sesión cerrada correctamente.</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label small">Documento</label>
        <input type="number" name="documento" class="form-control" placeholder="Ej. 1110456" value="<?= htmlspecialchars($_POST['documento'] ?? '') ?>" required>
      </div>
      
      <div class="mb-3">
        <label class="form-label small">PIN de Seguridad (4 dígitos)</label>
        <input type="password" name="pin" maxlength="4" pattern="\d{4}" class="form-control" placeholder="••••" required>
      </div>

      <div class="mb-3">
        <label class="form-label small">Contraseña</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      
      <button type="submit" class="btn btn-primary w-100 mb-3">Ingresar</button>
    </form>

    <div class="text-center">
        <a href="../index.php" class="text-muted small text-decoration-none">← Volver</a>
    </div>
  </div>
</body>
</html>