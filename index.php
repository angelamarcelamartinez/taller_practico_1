<?php
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/funciones.php';

$db = new Database();
$pdo = $db->conectar();

$mensaje = null;
$tipoMensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $documento = trim($_POST['documento'] ?? '');
    $pin       = trim($_POST['pin'] ?? '');

    if (empty($documento) || empty($pin)) {
        $mensaje = "Por favor completa todos los campos.";
        $tipoMensaje = "warning";

    } elseif (!preg_match('/^\d{6,15}$/', $documento)) {
        $mensaje = "Documento inválido.";
        $tipoMensaje = "danger";

    } else {

        // Buscar usuario en nueva tabla
        $stmt = $pdo->prepare("
            SELECT 
                u.documento,
                u.nombre_completo,
                u.pin,
                u.estado,
                a.nombre_area,
                t.nombre_tipo_usuario
            FROM user u
            INNER JOIN area a ON u.id_area = a.id_area
            INNER JOIN tipo_usuario t ON u.id_tipo_usuario = t.id_tipo_usuario
            WHERE u.documento = :documento
            LIMIT 1
        ");

        $stmt->execute([':documento' => $documento]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Usuario no existe o inactivo
        if (!$usuario || $usuario['estado'] != "activo") {
            $mensaje = "Credenciales inválidas o usuario inactivo.";
            $tipoMensaje = "danger";

        // PIN incorrecto
        } elseif (trim($pin) !== trim($usuario['pin'])) {
            $mensaje = "Credenciales inválidas.";
            $tipoMensaje = "danger";

        } else {

            $nombre = htmlspecialchars($usuario['nombre_completo']);

            // Buscar asistencia abierta HOY
            $stmtReg = $pdo->prepare("
                SELECT id_asistencia, fecha_hora_entrada
                FROM asistencias
                WHERE documento = :doc
                AND DATE(fecha_hora_entrada) = CURDATE()
                AND fecha_hora_salida IS NULL
                LIMIT 1
            ");

            $stmtReg->execute([':doc' => $documento]);
            $registro = $stmtReg->fetch(PDO::FETCH_ASSOC);

            // ENTRADA
            if (!$registro) {

                $ins = $pdo->prepare("
                    INSERT INTO asistencias (documento, fecha_hora_entrada)
                    VALUES (:doc, NOW())
                ");

                $ins->execute([':doc' => $documento]);

                $mensaje = "¡Bienvenido $nombre! Entrada registrada.";
                $tipoMensaje = "success";

            // SALIDA
            } else {

                $upd = $pdo->prepare("
                    UPDATE asistencias
                    SET fecha_hora_salida = NOW(),
                        cantidad_horas_trabajadas = 
                        TIMESTAMPDIFF(MINUTE, fecha_hora_entrada, NOW()) / 60
                    WHERE id_asistencia = :id
                ");

                $upd->execute([':id' => $registro['id_asistencia']]);

                // obtener horas
                $stmtHoras = $pdo->prepare("
                    SELECT ROUND(cantidad_horas_trabajadas,2)
                    FROM asistencias
                    WHERE id_asistencia = :id
                ");

                $stmtHoras->execute([':id' => $registro['id_asistencia']]);
                $horas = $stmtHoras->fetchColumn();

                $mensaje = "¡Hasta luego $nombre! Horas trabajadas: <strong>{$horas}</strong> h";
                $tipoMensaje = "success";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BancoSena S.A.S | Registro de Asistencia</title>

    <!-- Bootstrap 5 + FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles_index.css">
</head>
<body>

    <!-- ─── NAVBAR (misma estructura) ─── -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-building-columns"></i> BancoSena S.A.S
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" href="index.php">Inicio</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <a href="admin/login.php" class="btn btn-login btn-sm px-3">
                        <i class="fas fa-user-lock me-1"></i> Iniciar Sesion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ─── HERO ─── -->
    <header class="hero-emp">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Portal de Empleados</h1>
            <p class="lead mb-0 opacity-90">Registra tu entrada o salida con tu documento y PIN</p>
        </div>
    </header>

    <!-- ─── FORMULARIO FLOTANTE ─── -->
    <section>
        <div class="container">
            <div class="form-card">

                <!-- Icono decorativo -->
                <div class="icon-stamp">
                    <i class="fas fa-fingerprint"></i>
                </div>

                <h4 class="text-center fw-bold mb-1">Registro de Asistencia</h4>
                <p class="text-center text-muted small mb-4">Ingresa tus datos para marcar entrada o salida</p>

                <!-- ─── MENSAJE DE RESPUESTA ─── -->
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= htmlspecialchars($tipoMensaje) ?> d-flex align-items-center gap-2 mb-4" role="alert">
                        <?php if ($tipoMensaje === 'success'): ?>
                            <i class="fas fa-check-circle fs-5"></i>
                        <?php elseif ($tipoMensaje === 'danger'): ?>
                            <i class="fas fa-times-circle fs-5"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle fs-5"></i>
                        <?php endif; ?>
                        <div><?= $mensaje /* Ya saneado con htmlspecialchars() arriba */ ?></div>
                    </div>
                <?php endif; ?>

                <!-- ─── FORM: documento + PIN ─── -->
                <form method="POST" action="index.php" novalidate>

                    <div class="mb-3">
                        <label for="documento" class="form-label">
                            <i class="fas fa-id-card me-1 text-primary"></i> Número de Documento
                        </label>
                        <input
                            type="text"
                            id="documento"
                            name="documento"
                            class="form-control"
                            placeholder="Ej. 1234567890"
                            maxlength="15"
                            inputmode="numeric"
                            pattern="\d{6,15}"
                            value="<?= htmlspecialchars($_POST['documento'] ?? '') ?>"
                            required
                            autocomplete="off">
                        <div class="form-text text-muted">Solo números, sin puntos ni guiones.</div>
                    </div>

                    <div class="mb-4">
                        <label for="pin" class="form-label">
                            <i class="fas fa-lock me-1 text-primary"></i> PIN de Acceso
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="pin"
                                name="pin"
                                class="form-control"
                                placeholder="••••••"
                                maxlength="8"
                                inputmode="numeric"
                                required
                                autocomplete="current-password">
                            <button
                                class="btn btn-outline-secondary"
                                type="button"
                                id="togglePin"
                                tabindex="-1"
                                title="Mostrar/ocultar PIN">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-registrar">
                        <i class="fas fa-clock me-2"></i> Registrar Asistencia
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- ─── SECCIÓN INFORMATIVA ─── -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-2 fw-bold">¿Cómo funciona?</h2>
            <span class="section-line d-block mb-5"></span>

            <div class="row g-4 justify-content-center">
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="info-card">
                        <div class="icon-wrap"><i class="fas fa-sign-in-alt"></i></div>
                        <h6 class="fw-bold mb-1">Primera marcación</h6>
                        <p class="text-muted small mb-0">
                            Si no tienes registro de entrada hoy, se crea automáticamente con la hora actual.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="info-card">
                        <div class="icon-wrap"><i class="fas fa-sign-out-alt"></i></div>
                        <h6 class="fw-bold mb-1">Segunda marcación</h6>
                        <p class="text-muted small mb-0">
                            Si ya tienes entrada registrada, se actualiza tu salida y se calculan tus horas trabajadas.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="info-card">
                        <div class="icon-wrap"><i class="fas fa-shield-alt"></i></div>
                        <h6 class="fw-bold mb-1">Datos seguros</h6>
                        <p class="text-muted small mb-0">
                            Tu PIN se almacena cifrado. Nadie, ni el administrador, puede verlo en texto plano.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── MISIÓN, VISIÓN E IMAGEN EMPRESA ─── -->
    <section class="py-5 empresa-info">
        <div class="container">

            <h2 class="text-center fw-bold mb-2">BancoSena S.A.S</h2>
            <span class="section-line d-block mb-5"></span>

            <div class="row align-items-center g-4">

                <!-- Misión y Visión -->
                <div class="col-12 col-lg-6">

                    <div class="mb-4">
                        <h4 class="fw-bold"><i class="fas fa-bullseye me-2 text-primary"></i>Misión</h4>
                        <p class="text-muted mb-0">
                            Brindar soluciones financieras innovadoras y eficientes que impulsen el crecimiento
                            de nuestros clientes y colaboradores, garantizando transparencia, seguridad y calidad en el servicio.
                        </p>
                    </div>

                    <div>
                        <h4 class="fw-bold"><i class="fas fa-eye me-2 text-primary"></i>Visión</h4>
                        <p class="text-muted mb-0">
                            Ser una entidad líder en el sector financiero, reconocida por su compromiso con la
                            innovación tecnológica, la excelencia operativa y el desarrollo sostenible.
                        </p>
                    </div>

                </div>

                <!-- Imagen empresa -->
                <div class="col-12 col-lg-6 text-center">
                    <div class="empresa-img-wrapper">
                        <img src="img/empresa.png" alt="BancoSena S.A.S" class="img-fluid empresa-img">
                    </div>
                </div>

            </div>
        </div>
    </section>

    <footer>
        <?php 
        // Cargar el footer reutilizable
        require_once __DIR__ . '/includes/footer.php'; 
        ?>
    </footer>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle visibilidad del PIN
        document.getElementById('togglePin').addEventListener('click', function () {
            const pinInput = document.getElementById('pin');
            const eyeIcon  = document.getElementById('eyeIcon');
            if (pinInput.type === 'password') {
                pinInput.type = 'text';
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pinInput.type = 'password';
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Permitir solo números en documento y PIN
        ['documento', 'pin'].forEach(id => {
            document.getElementById(id).addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    </script>

    
    <script>
        //Para el navegador trasparente
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function () {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    </script>
</body>
</html>