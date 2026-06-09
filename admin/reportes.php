<?php
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die('Error de conexión a la base de datos');
}

$vista = $_GET['vista'] ?? 'detalle';
$modo = $_GET['modo'] ?? 'rango';
$fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : (int) date('Y');
$documentoFiltro = isset($_GET['documento']) && $_GET['documento'] !== ''
    ? (int) $_GET['documento']
    : null;

$errorFiltro = '';
$asistencias = [];
$resumen = [];
$porMes = [];

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

$empleados = obtenerEmpleadosActivos($pdo);

if (!in_array($vista, ['detalle', 'resumen', 'mensual'], true)) {
    $vista = 'detalle';
}

if ($vista === 'mensual' || $modo === 'mensual') {
    $vista = 'mensual';
    if ($anio < 2000 || $anio > 2100) {
        $errorFiltro = 'El año no es válido.';
        $anio = (int) date('Y');
    } else {
        $porMes = obtenerHorasPorMesEmpleados($pdo, $anio, $documentoFiltro);
        $fechaDesde = $anio . '-01-01';
        $fechaHasta = $anio . '-12-31';
        $resumen = obtenerReporteHorasEmpleados($pdo, $fechaDesde, $fechaHasta, $documentoFiltro);
    }
} else {
    if (!validarFechaYmd($fechaDesde) || !validarFechaYmd($fechaHasta)) {
        $errorFiltro = 'Las fechas del filtro no son válidas.';
        $fechaDesde = date('Y-m-01');
        $fechaHasta = date('Y-m-d');
    } elseif ($fechaDesde > $fechaHasta) {
        $errorFiltro = 'La fecha inicial no puede ser posterior a la final.';
        $fechaDesde = date('Y-m-01');
        $fechaHasta = date('Y-m-d');
    }

    $asistencias = obtenerAsistenciasFiltradas($pdo, $fechaDesde, $fechaHasta, $documentoFiltro);
    $resumen = obtenerReporteHorasEmpleados($pdo, $fechaDesde, $fechaHasta, $documentoFiltro);
}

$totalHorasGeneral = array_sum(array_column($resumen, 'total_horas'));
$totalRegistros = count($asistencias);

function formatearFechaHora(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($fecha));
}

function urlReporte(array $params): string
{
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return 'reportes.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asistencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background: #f5f7fb; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fa-solid fa-clock text-success me-2"></i>Reporte de Asistencias</h1>
            <p class="text-muted mb-0">Marcajes, resumen por empleado y horas por mes</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <?php if ($errorFiltro): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($errorFiltro) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $vista === 'detalle' ? 'active' : '' ?>"
               href="<?= htmlspecialchars(urlReporte([
                   'vista' => 'detalle',
                   'fecha_desde' => $fechaDesde,
                   'fecha_hasta' => $fechaHasta,
                   'documento' => $documentoFiltro ?? '',
               ])) ?>">
                <i class="fa-solid fa-list me-1"></i> Detalle de marcajes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $vista === 'resumen' ? 'active' : '' ?>"
               href="<?= htmlspecialchars(urlReporte([
                   'vista' => 'resumen',
                   'fecha_desde' => $fechaDesde,
                   'fecha_hasta' => $fechaHasta,
                   'documento' => $documentoFiltro ?? '',
               ])) ?>">
                <i class="fa-solid fa-users me-1"></i> Resumen por empleado
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $vista === 'mensual' ? 'active' : '' ?>"
               href="<?= htmlspecialchars(urlReporte([
                   'vista' => 'mensual',
                   'anio' => $anio,
                   'documento' => $documentoFiltro ?? '',
               ])) ?>">
                <i class="fa-solid fa-calendar me-1"></i> Horas por mes
            </a>
        </li>
    </ul>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <?php if ($vista === 'mensual'): ?>
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="vista" value="mensual">
                    <div class="col-md-3">
                        <label class="form-label">Año</label>
                        <input type="number" name="anio" class="form-control" min="2000" max="2100"
                               value="<?= $anio ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Empleado (opcional)</label>
                        <select name="documento" class="form-select">
                            <option value="">Todos los empleados</option>
                            <?php foreach ($empleados as $emp): ?>
                                <option value="<?= (int) $emp['documento'] ?>"
                                    <?= $documentoFiltro === (int) $emp['documento'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre_completo']) ?>
                                    (<?= (int) $emp['documento'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">Filtrar</button>
                    </div>
                </form>
            <?php else: ?>
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="vista" value="<?= htmlspecialchars($vista) ?>">
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control"
                               value="<?= htmlspecialchars($fechaDesde) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control"
                               value="<?= htmlspecialchars($fechaHasta) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Empleado (opcional)</label>
                        <select name="documento" class="form-select">
                            <option value="">Todos los empleados</option>
                            <?php foreach ($empleados as $emp): ?>
                                <option value="<?= (int) $emp['documento'] ?>"
                                    <?= $documentoFiltro === (int) $emp['documento'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre_completo']) ?>
                                    (<?= (int) $emp['documento'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($vista !== 'mensual'): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="text-muted small">Total horas (período)</div>
                        <div class="h3 fw-bold text-primary mb-0"><?= number_format($totalHorasGeneral, 2) ?> h</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="text-muted small">Marcajes en detalle</div>
                        <div class="h3 fw-bold mb-0"><?= $totalRegistros ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="text-muted small">Período</div>
                        <div class="fw-semibold mt-1">
                            <?= htmlspecialchars($fechaDesde) ?> — <?= htmlspecialchars($fechaHasta) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($vista === 'detalle'): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                Detalle de entrada y salida (<?= $totalRegistros ?>)
            </div>
            <div class="card-body p-0">
                <?php if ($totalRegistros === 0): ?>
                    <p class="text-muted p-4 mb-0">No hay registros en el rango seleccionado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Documento</th>
                                    <th>Empleado</th>
                                    <th>Área</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Horas</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($asistencias as $a): ?>
                                    <?php $enCurso = empty($a['fecha_hora_salida']); ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($a['fecha_hora_entrada']))) ?></td>
                                        <td><?= (int) $a['documento'] ?></td>
                                        <td><?= htmlspecialchars($a['nombre_completo'] ?? 'Sin nombre') ?></td>
                                        <td><?= htmlspecialchars($a['nombre_area'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars(formatearFechaHora($a['fecha_hora_entrada'])) ?></td>
                                        <td><?= htmlspecialchars(formatearFechaHora($a['fecha_hora_salida'])) ?></td>
                                        <td>
                                            <?php if ($enCurso): ?>
                                                <span class="text-muted">—</span>
                                            <?php else: ?>
                                                <?= htmlspecialchars(number_format((float) $a['cantidad_horas_trabajadas'], 2)) ?> h
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($enCurso): ?>
                                                <span class="badge bg-warning text-dark">En curso</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Completado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($vista === 'resumen'): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Resumen por empleado</div>
            <div class="card-body p-0">
                <?php if (count($resumen) === 0): ?>
                    <p class="text-muted p-4 mb-0">No hay datos para el filtro seleccionado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Documento</th>
                                    <th>Empleado</th>
                                    <th>Área</th>
                                    <th>Registros</th>
                                    <th>Jornadas completas</th>
                                    <th>Total horas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumen as $fila): ?>
                                    <tr>
                                        <td><?= (int) $fila['documento'] ?></td>
                                        <td><?= htmlspecialchars($fila['nombre_completo']) ?></td>
                                        <td><?= htmlspecialchars($fila['nombre_area'] ?? '—') ?></td>
                                        <td><?= (int) $fila['total_registros'] ?></td>
                                        <td><?= (int) $fila['jornadas_completas'] ?></td>
                                        <td class="fw-semibold">
                                            <?= number_format((float) $fila['total_horas'], 2) ?> h
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Total general</th>
                                    <th><?= number_format($totalHorasGeneral, 2) ?> h</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="text-muted small">Total horas del año <?= $anio ?></div>
                        <div class="h3 fw-bold text-primary mb-0"><?= number_format($totalHorasGeneral, 2) ?> h</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="text-muted small">Empleados con registros</div>
                        <div class="h3 fw-bold mb-0"><?= count($resumen) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (count($resumen) > 0): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Resumen anual por empleado</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Documento</th>
                                    <th>Empleado</th>
                                    <th>Área</th>
                                    <th>Registros</th>
                                    <th>Total horas <?= $anio ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumen as $fila): ?>
                                    <?php if ((float) $fila['total_horas'] <= 0 && (int) $fila['total_registros'] <= 0) continue; ?>
                                    <tr>
                                        <td><?= (int) $fila['documento'] ?></td>
                                        <td><?= htmlspecialchars($fila['nombre_completo']) ?></td>
                                        <td><?= htmlspecialchars($fila['nombre_area'] ?? '—') ?></td>
                                        <td><?= (int) $fila['total_registros'] ?></td>
                                        <td class="fw-semibold">
                                            <?= number_format((float) $fila['total_horas'], 2) ?> h
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Desglose mensual — <?= $anio ?></div>
            <div class="card-body p-0">
                <?php if (count($porMes) === 0): ?>
                    <p class="text-muted p-4 mb-0"> No hay asistencias registradas en este año.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Documento</th>
                                    <th>Empleado</th>
                                    <th>Mes</th>
                                    <th>Registros</th>
                                    <th>Horas del mes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($porMes as $fila): ?>
                                    <tr>
                                        <td><?= (int) $fila['documento'] ?></td>
                                        <td><?= htmlspecialchars($fila['nombre_completo']) ?></td>
                                        <td><?= htmlspecialchars($meses[(int) $fila['mes']] ?? $fila['mes']) ?></td>
                                        <td><?= (int) $fila['registros_mes'] ?></td>
                                        <td class="fw-semibold">
                                            <?= number_format((float) $fila['horas_mes'], 2) ?> h
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>