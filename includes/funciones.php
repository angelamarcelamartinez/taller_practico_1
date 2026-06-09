<?php

/**
 * ============================================================================
 * SECCIÓN: USUARIOS (Antes Productos)
 * ============================================================================
 */

function obtenerTodosLosUsuarios(PDO $pdo): array {
    // Trae los datos del usuario junto con el nombre de su área y su tipo de usuario
    $sql = "SELECT u.*, tu.nombre_tipo_usuario, a.nombre_area
            FROM user u
            LEFT JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
            LEFT JOIN area a ON u.id_area = a.id_area
            ORDER BY u.documento ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerTodosLosEmpleados(PDO $pdo): array
{
    $sql = "SELECT u.*, tu.nombre_tipo_usuario, a.nombre_area
            FROM user u
            INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
            LEFT JOIN area a ON u.id_area = a.id_area
            WHERE LOWER(tu.nombre_tipo_usuario) = 'empleado'
            ORDER BY u.documento ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function esEmpleado(PDO $pdo, int $documento): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM user u
         INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
         WHERE u.documento = ? AND LOWER(tu.nombre_tipo_usuario) = 'empleado'
         LIMIT 1"
    );
    $stmt->execute([$documento]);
    return (bool) $stmt->fetchColumn();
}

function obtenerUsuarioPorDocumento(PDO $pdo, int $documento) {
    $sql = "SELECT * FROM user WHERE documento = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$documento]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertarUsuario(
    PDO $pdo, 
    int $documento, 
    string $pin, 
    string $password, 
    string $nombre_completo, 
    ?int $id_area, 
    ?int $id_tipo_usuario, 
    string $estado = 'activo'
): bool {
    $sql = "INSERT INTO user (documento, pin, password, nombre_completo, id_area, id_tipo_usuario, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$documento, $pin, $password, $nombre_completo, $id_area, $id_tipo_usuario, $estado]);
        return true;
    } catch (PDOException $e) {
        error_log("Error al insertar usuario: " . $e->getMessage());
        return false;
    }
}

function actualizarUsuario(
    PDO $pdo,
    int $documento,
    ?string $pin,
    string $nombre_completo,
    ?int $id_area,
    ?int $id_tipo_usuario,
    string $estado,
    ?string $password = null
): bool {
    $sets   = ['nombre_completo = ?', 'id_area = ?', 'id_tipo_usuario = ?', 'estado = ?'];
    $params = [$nombre_completo, $id_area, $id_tipo_usuario, $estado];

    if ($pin !== null) {
        array_unshift($sets, 'pin = ?');
        array_unshift($params, $pin);
    }
    if ($password !== null) {
        $sets[]   = 'password = ?';
        $params[] = $password;
    }

    $params[] = $documento;
    $sql      = 'UPDATE user SET ' . implode(', ', $sets) . ' WHERE documento = ?';
    $stmt     = $pdo->prepare($sql);

    try {
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error al actualizar usuario: " . $e->getMessage());
        return false;
    }
}

function eliminarUsuario(PDO $pdo, int $documento): bool
{
    $sql = "DELETE FROM user WHERE documento = ?";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$documento]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error al eliminar usuario: " . $e->getMessage());
        return false; 
    }
}


function obtenerTodosLosTiposUsuario(PDO $pdo): array
{
    $sql = "SELECT * FROM tipo_usuario ORDER BY nombre_tipo_usuario ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearTipoUsuario(PDO $pdo, string $nombre): int|false {
    // Adaptado con ON DUPLICATE KEY UPDATE respetando la lógica que tenías
    $sql = "INSERT INTO tipo_usuario (nombre_tipo_usuario) VALUES (?) 
            ON DUPLICATE KEY UPDATE nombre_tipo_usuario = VALUES(nombre_tipo_usuario)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$nombre]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error al crear tipo de usuario: " . $e->getMessage());
        return false;
    }
}

function actualizarTipoUsuario(PDO $pdo, int $id, string $nombre): bool {
    $sql = "UPDATE tipo_usuario SET nombre_tipo_usuario = ? WHERE id_tipo_usuario = ?";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$nombre, $id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error al actualizar tipo de usuario: " . $e->getMessage());
        return false;
    }
}

function eliminarTipoUsuario(PDO $pdo, int $id_tipo): bool {
    $sql = "DELETE FROM tipo_usuario WHERE id_tipo_usuario = ?";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$id_tipo]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error al eliminar tipo de usuario: " . $e->getMessage());
        return false;
    }
}

function validarPinUsuario(string $pin_ingresado, string $pin_almacenado): bool
{
    return $pin_ingresado === $pin_almacenado
        || password_verify($pin_ingresado, $pin_almacenado);
}

function hashPin(string $pin): string
{
    return password_hash($pin, PASSWORD_DEFAULT);
}

function obtenerTodasLasAreas(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT * FROM area ORDER BY nombre_area ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearArea(PDO $pdo, string $nombre): int|false
{
    $stmt = $pdo->prepare("INSERT INTO area (nombre_area) VALUES (?)");
    try {
        $stmt->execute([$nombre]);
        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error al crear área: ' . $e->getMessage());
        return false;
    }
}

function actualizarArea(PDO $pdo, int $id, string $nombre): bool
{
    $stmt = $pdo->prepare("UPDATE area SET nombre_area = ? WHERE id_area = ?");
    try {
        $stmt->execute([$nombre, $id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error al actualizar área: ' . $e->getMessage());
        return false;
    }
}

function eliminarArea(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM area WHERE id_area = ?");
    try {
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error al eliminar área: ' . $e->getMessage());
        return false;
    }
}

function obtenerIdTipoEmpleado(PDO $pdo): ?int
{
    $stmt = $pdo->prepare(
        "SELECT id_tipo_usuario FROM tipo_usuario WHERE LOWER(nombre_tipo_usuario) = 'empleado' LIMIT 1"
    );
    $stmt->execute();
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

function autenticarAdministrador(PDO $pdo, string $documento, string $pin, string $password): ?array
{
    $sql = "SELECT u.documento, u.pin, u.password, u.nombre_completo, t.nombre_tipo_usuario
            FROM user u
            INNER JOIN tipo_usuario t ON u.id_tipo_usuario = t.id_tipo_usuario
            WHERE u.documento = :documento
              AND u.estado = 'activo'
              AND LOWER(t.nombre_tipo_usuario) = 'administrador'";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':documento', (int) $documento, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !validarPinUsuario($pin, $user['pin'])) {
        return null;
    }

    if (!password_verify($password, $user['password'])) {
        return null;
    }

    return $user;
}

function obtenerAdministradorPorDocumento($pdo, $documento) {
    $stmt = $pdo->prepare("
        SELECT u.documento, u.nombre_completo
        FROM user u
        INNER JOIN tipo_usuario t ON u.id_tipo_usuario = t.id_tipo_usuario
        WHERE u.documento = ? 
          AND u.estado = 'activo' 
          AND LOWER(t.nombre_tipo_usuario) = 'administrador'
    ");
    $stmt->execute([$documento]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerIdTipoAdministrador(PDO $pdo): ?int
{
    $stmt = $pdo->prepare(
        "SELECT id_tipo_usuario FROM tipo_usuario WHERE LOWER(nombre_tipo_usuario) = 'administrador' LIMIT 1"
    );
    $stmt->execute();
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

function validarFechaYmd(string $fecha): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

function obtenerEmpleadosActivos(PDO $pdo): array
{
    $sql = "SELECT u.documento, u.nombre_completo
            FROM user u
            INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
            WHERE u.estado = 'activo'
              AND LOWER(tu.nombre_tipo_usuario) = 'empleado'
            ORDER BY u.nombre_completo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerAsistenciasFiltradas(
    PDO $pdo,
    string $fechaDesde,
    string $fechaHasta,
    ?int $documento
): array {
    $where  = [
        "LOWER(tu.nombre_tipo_usuario) = 'empleado'",
        'DATE(a.fecha_hora_entrada) >= :desde',
        'DATE(a.fecha_hora_entrada) <= :hasta',
    ];
    $params = [':desde' => $fechaDesde, ':hasta' => $fechaHasta];

    if ($documento !== null) {
        $where[]              = 'a.documento = :documento';
        $params[':documento'] = $documento;
    }

    $sql = "
        SELECT
            a.id_asistencia,
            a.documento,
            u.nombre_completo,
            ar.nombre_area,
            a.fecha_hora_entrada,
            a.fecha_hora_salida,
            a.cantidad_horas_trabajadas
        FROM asistencias a
        INNER JOIN user u ON a.documento = u.documento
        INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
        LEFT JOIN area ar ON u.id_area = ar.id_area
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.fecha_hora_entrada DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerReporteHorasEmpleados(
    PDO $pdo,
    string $fechaDesde,
    string $fechaHasta,
    ?int $documento
): array {
    $where  = [
        "LOWER(tu.nombre_tipo_usuario) = 'empleado'",
        'DATE(a.fecha_hora_entrada) >= :desde',
        'DATE(a.fecha_hora_entrada) <= :hasta',
    ];
    $params = [':desde' => $fechaDesde, ':hasta' => $fechaHasta];

    if ($documento !== null) {
        $where[]              = 'a.documento = :documento';
        $params[':documento'] = $documento;
    }

    $sql = "
        SELECT
            a.documento,
            u.nombre_completo,
            ar.nombre_area,
            COUNT(a.id_asistencia) AS total_registros,
            SUM(CASE WHEN a.fecha_hora_salida IS NOT NULL THEN 1 ELSE 0 END) AS jornadas_completas,
            COALESCE(SUM(a.cantidad_horas_trabajadas), 0) AS total_horas
        FROM asistencias a
        INNER JOIN user u ON a.documento = u.documento
        INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
        LEFT JOIN area ar ON u.id_area = ar.id_area
        WHERE " . implode(' AND ', $where) . "
        GROUP BY a.documento, u.nombre_completo, ar.nombre_area
        ORDER BY u.nombre_completo ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerHorasPorMesEmpleados(PDO $pdo, int $anio, ?int $documento): array
{
    $where  = [
        "LOWER(tu.nombre_tipo_usuario) = 'empleado'",
        'YEAR(a.fecha_hora_entrada) = :anio',
    ];
    $params = [':anio' => $anio];

    if ($documento !== null) {
        $where[]              = 'a.documento = :documento';
        $params[':documento'] = $documento;
    }

    $sql = "
        SELECT
            a.documento,
            u.nombre_completo,
            MONTH(a.fecha_hora_entrada) AS mes,
            COUNT(a.id_asistencia) AS registros_mes,
            COALESCE(SUM(a.cantidad_horas_trabajadas), 0) AS horas_mes
        FROM asistencias a
        INNER JOIN user u ON a.documento = u.documento
        INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
        WHERE " . implode(' AND ', $where) . "
        GROUP BY a.documento, u.nombre_completo, MONTH(a.fecha_hora_entrada)
        ORDER BY u.nombre_completo ASC, mes ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function construirFiltrosReporteAsistencias(string $filtro_doc, string $filtro_desde, string $filtro_hasta, string $filtro_mes = ''): array
{
    $where  = ["LOWER(tu.nombre_tipo_usuario) = 'empleado'"];
    $params = [];

    if ($filtro_doc !== '') {
        $where[]              = 'a.documento = :documento';
        $params[':documento'] = (int) $filtro_doc;
    }
    if ($filtro_desde !== '') {
        $where[]          = 'DATE(a.fecha_hora_entrada) >= :desde';
        $params[':desde'] = $filtro_desde;
    }
    if ($filtro_hasta !== '') {
        $where[]          = 'DATE(a.fecha_hora_entrada) <= :hasta';
        $params[':hasta'] = $filtro_hasta;
    }
    if ($filtro_mes !== '' && preg_match('/^\d{4}-\d{2}$/', $filtro_mes)) {
        $where[]         = "DATE_FORMAT(a.fecha_hora_entrada, '%Y-%m') = :mes";
        $params[':mes'] = $filtro_mes;
    }

    return ['where' => $where, 'params' => $params];
}

/**
 * Detalle de marcajes: JOIN empleados + asistencias.
 */
function obtenerReportesAsistencias(PDO $pdo, string $filtro_doc, string $filtro_desde, string $filtro_hasta, string $filtro_mes = ''): array
{
    $filtros = construirFiltrosReporteAsistencias($filtro_doc, $filtro_desde, $filtro_hasta, $filtro_mes);

    $sql = "
        SELECT
            a.id_asistencia,
            a.documento,
            u.nombre_completo,
            ar.nombre_area,
            a.fecha_hora_entrada,
            a.fecha_hora_salida,
            a.cantidad_horas_trabajadas
        FROM asistencias a
        INNER JOIN user u ON a.documento = u.documento
        INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
        LEFT JOIN area ar ON u.id_area = ar.id_area
        WHERE " . implode(' AND ', $filtros['where']) . "
        ORDER BY a.fecha_hora_entrada DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($filtros['params']);
    $registros  = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalHoras = array_sum(array_column($registros, 'cantidad_horas_trabajadas'));

    return ['registros' => $registros, 'total_horas' => $totalHoras];
}

/**
 * Resumen agrupado por empleado.
 */
function obtenerResumenAsistenciasPorEmpleado(PDO $pdo, string $filtro_doc, string $filtro_desde, string $filtro_hasta, string $filtro_mes = ''): array
{
    $filtros = construirFiltrosReporteAsistencias($filtro_doc, $filtro_desde, $filtro_hasta, $filtro_mes);

    $sql = "
        SELECT
            a.documento,
            u.nombre_completo,
            ar.nombre_area,
            COUNT(a.id_asistencia) AS total_marcajes,
            SUM(CASE WHEN a.fecha_hora_salida IS NULL THEN 1 ELSE 0 END) AS marcajes_abiertos,
            COALESCE(SUM(a.cantidad_horas_trabajadas), 0) AS total_horas
        FROM asistencias a
        INNER JOIN user u ON a.documento = u.documento
        INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
        LEFT JOIN area ar ON u.id_area = ar.id_area
        WHERE " . implode(' AND ', $filtros['where']) . "
        GROUP BY a.documento, u.nombre_completo, ar.nombre_area
        ORDER BY u.nombre_completo ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($filtros['params']);
    $filas      = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalHoras = array_sum(array_column($filas, 'total_horas'));

    return ['filas' => $filas, 'total_horas' => $totalHoras];
}

/**
 * Horas trabajadas por mes (todos los empleados o filtrados).
 */
function obtenerHorasAsistenciasPorMes(PDO $pdo, string $filtro_doc, string $filtro_desde, string $filtro_hasta): array
{
    $filtros = construirFiltrosReporteAsistencias($filtro_doc, $filtro_desde, $filtro_hasta, '');

    $sql = "
        SELECT
            DATE_FORMAT(a.fecha_hora_entrada, '%Y-%m') AS periodo_mes,
            COUNT(a.id_asistencia) AS total_marcajes,
            COUNT(DISTINCT a.documento) AS empleados_distintos,
            COALESCE(SUM(a.cantidad_horas_trabajadas), 0) AS total_horas
        FROM asistencias a
        INNER JOIN user u ON a.documento = u.documento
        INNER JOIN tipo_usuario tu ON u.id_tipo_usuario = tu.id_tipo_usuario
        WHERE " . implode(' AND ', $filtros['where']) . "
        GROUP BY periodo_mes
        ORDER BY periodo_mes DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($filtros['params']);
    $filas      = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalHoras = array_sum(array_column($filas, ' total_horas'));

    return ['filas' => $filas, 'total_horas' => $totalHoras];
}

function etiquetaMesEspanol(string $periodoYm): string
{
    $meses = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
    ];
    if (!preg_match('/^(\d{4})-(\d{2})$/', $periodoYm, $m)) {
        return $periodoYm;
    }
    return ($meses[$m[2]] ?? $m[2]) . ' ' . $m[1];
}

function exportarReporteAsistenciasExcel(string $vista, array $datos, float $totalHoras): void
{
    $nombre = 'reporte_' . $vista . '_' . date('Y-m-d_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    echo "<table border=\"1\">\n";

    if ($vista === 'resumen') {
        echo "<tr><th colspan=\"6\">Resumen por empleado - BancoSena</th></tr>\n";
        echo "<tr><th>Documento</th><th>Nombre</th><th>Departamento</th><th>Marcajes</th><th>Abiertos</th><th>Total horas</th></tr>\n";
        foreach ($datos as $r) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars((string) $r['documento']) . '</td>';
            echo '<td>' . htmlspecialchars($r['nombre_completo']) . '</td>';
            echo '<td>' . htmlspecialchars($r['nombre_area'] ?? 'Sin área') . '</td>';
            echo '<td>' . htmlspecialchars((string) $r['total_marcajes']) . '</td>';
            echo '<td>' . htmlspecialchars((string) $r['marcajes_abiertos']) . '</td>';
            echo '<td>' . htmlspecialchars(number_format((float) $r['total_horas'], 2)) . '</td>';
            echo "</tr>\n";
        }
        echo '<tr><td colspan="5" align="right"><strong>Total general</strong></td>';
        echo '<td><strong>' . htmlspecialchars(number_format($totalHoras, 2)) . '</strong></td></tr>';
    } elseif ($vista === 'mes') {
        echo "<tr><th colspan=\"4\">Horas por mes - BancoSena</th></tr>\n";
        echo "<tr><th>Mes</th><th>Marcajes</th><th>Empleados</th><th>Total horas</th></tr>\n";
        foreach ($datos as $r) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars(etiquetaMesEspanol($r['periodo_mes'])) . '</td>';
            echo '<td>' . htmlspecialchars((string) $r['total_marcajes']) . '</td>';
            echo '<td>' . htmlspecialchars((string) $r['empleados_distintos']) . '</td>';
            echo '<td>' . htmlspecialchars(number_format((float) $r['total_horas'], 2)) . '</td>';
            echo "</tr>\n";
        }
        echo '<tr><td colspan="3" align="right"><strong>Total general</strong></td>';
        echo '<td><strong>' . htmlspecialchars(number_format($totalHoras, 2)) . '</strong></td></tr>';
    } else {
        echo "<tr><th colspan=\"7\">Detalle de marcajes - BancoSena</th></tr>\n";
        echo "<tr><th>ID</th><th>Documento</th><th>Nombre</th><th>Departamento</th><th>Entrada</th><th>Salida</th><th>Horas</th></tr>\n";
        foreach ($datos as $r) {
            $salida = $r['fecha_hora_salida'] ?: 'Abierta';
            $horas  = $r['cantidad_horas_trabajadas'] !== null
                ? number_format((float) $r['cantidad_horas_trabajadas'], 2)
                : '';
            echo '<tr>';
            echo '<td>' . htmlspecialchars((string) $r['id_asistencia']) . '</td>';
            echo '<td>' . htmlspecialchars((string) $r['documento']) . '</td>';
            echo '<td>' . htmlspecialchars($r['nombre_completo']) . '</td>';
            echo '<td>' . htmlspecialchars($r['nombre_area'] ?? 'Sin área') . '</td>';
            echo '<td>' . htmlspecialchars($r['fecha_hora_entrada']) . '</td>';
            echo '<td>' . htmlspecialchars($salida) . '</td>';
            echo '<td>' . htmlspecialchars($horas) . '</td>';
            echo "</tr>\n";
        }
        echo '<tr><td colspan="6" align="right"><strong>Total horas</strong></td>';
        echo '<td><strong>' . htmlspecialchars(number_format($totalHoras, 2)) . '</strong></td></tr>';
    }

    echo "</table>\n";
    exit;
}

?>