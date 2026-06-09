<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

$nombre    = $_SESSION['nombre_completo'] ?? $_SESSION['nombre'] ?? 'Administrador';
$documento = $_SESSION['admin_id'] ?? $_SESSION['documento'] ?? 'Sin documento';
$rol       = 'Administrador';

$iniciales = '';
$partes = explode(' ', trim($nombre));
if (count($partes) >= 2) {
    $iniciales = strtoupper(substr($partes[0], 0, 1)) . strtoupper(substr($partes[1], 0, 1));
} else {
    $iniciales = strtoupper(substr($nombre, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body { background: #f5f7fb; }
        .dashboard-card {
            transition: .3s;
            border: none;
            border-radius: 15px;
        }
        .dashboard-card:hover { transform: translateY(-5px); }
        .avatar-circle {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #4f46e5;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-5 p-3 bg-white rounded shadow-sm border">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-circle shadow-sm">
                    <?= htmlspecialchars($iniciales) ?>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($nombre) ?></h6>
                    <small class="text-muted">
                        Doc: <?= htmlspecialchars($documento) ?> |
                        <span class="badge text-white" style="background-color: #4f46e5;">
                            <?= htmlspecialchars($rol) ?>
                        </span>
                    </small>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">

            <div class="col-md-5">
                <div class="card dashboard-card shadow h-100">
                    <div class="card-body text-center p-4">
                        <i class="fa-solid fa-users fa-4x text-primary mb-3"></i>
                        <h4>Empleados</h4>
                        <p class="text-muted">
                            Crear (PIN inicial, departamento, estado), ver y editar empleados.
                            También puede registrar un nuevo administrador.
                        </p>
                        <a href="empleados_crud.php" class="btn btn-primary px-4 mt-2">
                            Gestionar empleados
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card dashboard-card shadow h-100">
                    <div class="card-body text-center p-4">
                        <i class="fa-solid fa-chart-column fa-4x text-warning mb-3"></i>
                        <h4>Reportes de asistencia</h4>
                        <p class="text-muted">
                            Consulta con JOIN empleados + asistencias, filtro por fechas,
                            cálculo de horas y descarga en Excel.
                        </p>
                        <a href="reportes.php" class="btn btn-warning text-dark fw-medium px-4 mt-2">
                            Ver reportes
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="text-center mt-5">
            <a href="../logout.php" class="btn btn-danger btn-lg px-4 shadow-sm">
                <i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar sesión 
            </a>
        </div>

    </div>

</body>
</html>
