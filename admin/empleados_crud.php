<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_admin.php';

$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die('Error de conexión a la base de datos');
}

$tipos_usuario = obtenerTodosLosTiposUsuario($pdo);
$accion        = $_GET['accion'] ?? 'menu';
$mensaje       = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['crear'])) {
        $documento        = isset($_POST['documento']) && $_POST['documento'] !== '' ? (int) $_POST['documento'] : 0;
        $pin              = trim($_POST['pin'] ?? '');
        $password_plain   = trim($_POST['password'] ?? '');
        $nombre_completo  = trim($_POST['nombre_completo'] ?? '');
        $id_area          = isset($_POST['id_area']) && $_POST['id_area'] !== '' ? (int) $_POST['id_area'] : null;
        $estado           = trim($_POST['estado'] ?? 'activo');
        $id_tipo_empleado = obtenerIdTipoEmpleado($pdo);

        if ($documento <= 0 || $pin === '' || $password_plain === '' || $nombre_completo === '') {
            $mensaje = ' El documento, PIN, contraseña y nombre completo son obligatorios.';
        } elseif ($id_tipo_empleado === null) {
            $mensaje = 'No existe el tipo de usuario Empleado en la base de datos.';
        } elseif (!preg_match('/^\d{4}$/', $pin)) {
            $mensaje = ' El PIN debe tener exactamente 4 dígitos.';
        } elseif (!preg_match('/^[A-Za-z0-9]{10}$/', $password_plain)) {
            $mensaje = ' La contraseña debe tener 10 caracteres alfanuméricos.';
        } else {
            $ok = insertarUsuario(
                $pdo,
                $documento,
                $pin,
                password_hash($password_plain, PASSWORD_DEFAULT),
                $nombre_completo,
                $id_area,
                $id_tipo_empleado,
                $estado
            );
            $mensaje = $ok ? "✅ Usuario creado con éxito. Documento: $documento" : ' Error al insertar el usuario';
        }
    }

    if (isset($_POST['actualizar'])) {
        $documento        = isset($_POST['documento']) && $_POST['documento'] !== '' ? (int) $_POST['documento'] : 0;
        $pin              = trim($_POST['pin'] ?? '');
        $password_plain   = trim($_POST['password'] ?? '');
        $nombre_completo  = trim($_POST['nombre_completo'] ?? '');
        $id_area          = isset($_POST['id_area']) && $_POST['id_area'] !== '' ? (int) $_POST['id_area'] : null;
        $id_tipo_usuario  = isset($_POST['id_tipo_usuario']) && $_POST['id_tipo_usuario'] !== '' ? (int) $_POST['id_tipo_usuario'] : null;
        $estado           = trim($_POST['estado'] ?? 'activo');

        if ($documento <= 0 || $nombre_completo === '') {
            $mensaje = ' Datos inválidos. Verifique los campos obligatorios.';
        } else {
            $pin_actualizar = null;
            if (!empty($_POST['cambiar_pin'])) {
                if (!preg_match('/^\d{4}$/', $pin)) {
                    $mensaje = ' El nuevo PIN debe tener exactamente 4 dígitos.';
                } else {
                    $pin_actualizar = ($pin);
                }
            }
            if ($mensaje === '' && $password_plain !== '' && !preg_match('/^[A-Za-z0-9]{10}$/', $password_plain)) {
                $mensaje = ' La contraseña debe tener 10 caracteres alfanuméricos.';
            }
            if ($mensaje === '') {
                $password_hash = ($password_plain !== '') ? password_hash($password_plain, PASSWORD_DEFAULT) : null;
                $ok = actualizarUsuario($pdo, $documento, $pin_actualizar, $nombre_completo, $id_area, $id_tipo_usuario, $estado, $password_hash);
                $mensaje = $ok ? ' Usuario actualizado correctamente' : ' No se realizaron cambios o el usuario no existe';
            }
        }
    }

    if (isset($_POST['eliminar'])) {
        $documento = isset($_POST['documento']) && $_POST['documento'] !== '' ? (int) $_POST['documento'] : 0;
        if ($documento > 0) {
            $ok = eliminarUsuario($pdo, $documento);
            $mensaje = $ok ? ' Usuario eliminado correctamente' : ' No se encontró el documento del usuario';
        } else {
            $mensaje = ' El documento proporcionado es inválido';
        }
    }

    if (isset($_POST['crear_admin'])) {
        $documento       = isset($_POST['documento']) && $_POST['documento'] !== '' ? (int) $_POST['documento'] : 0;
        $pin             = trim($_POST['pin'] ?? '');
        $password_plain  = trim($_POST['password'] ?? '');
        $nombre_completo = trim($_POST['nombre_completo'] ?? '');
        $id_tipo_admin   = obtenerIdTipoAdministrador($pdo);

        if ($documento <= 0 || $pin === '' || $password_plain === '' || $nombre_completo === '') {
            $mensaje = ' Todos los campos son obligatorios.';
        } elseif ($id_tipo_admin === null) {
            $mensaje = 'No existe el tipo Administrador en la base de datos.';
        } elseif (!preg_match('/^\d{4}$/', $pin)) {
            $mensaje = ' El PIN debe tener 4 dígitos.';
        } elseif (!preg_match('/^[A-Za-z0-9]{10}$/', $password_plain)) {
            $mensaje = ' La contraseña debe tener 10 caracteres alfanuméricos.';
        } elseif (obtenerUsuarioPorDocumento($pdo, $documento)) {
            $mensaje = ' Ya existe un usuario con ese documento.';
        } else {
            $ok = insertarUsuario($pdo, $documento, ($pin), password_hash($password_plain, PASSWORD_DEFAULT), $nombre_completo, null, $id_tipo_admin, 'activo');
            $mensaje = $ok ? " Administrador creado. Documento: $documento" : ' Error al crear administrador';
        }
    }

    if (isset($_POST['crear_tipo'])) {
        $nombre_tipo = trim($_POST['nombre_tipo'] ?? '');
        if ($nombre_tipo !== '') {
            $res = crearTipoUsuario($pdo, $nombre_tipo);
            $mensaje = $res ? 'Tipo de usuario creado correctamente' : ' Error al crear tipo de usuario';
        }
    } elseif (isset($_POST['actualizar_tipo'])) {
        $id_tipo     = (int) ($_POST['id_tipo'] ?? 0);
        $nombre_tipo = trim($_POST['nombre_tipo'] ?? '');
        if ($id_tipo > 0 && $nombre_tipo !== '') {
            $res = actualizarTipoUsuario($pdo, $id_tipo, $nombre_tipo);
            $mensaje = $res ? ' Tipo de usuario actualizado' : 'Error al actualizar';
        }
    } elseif (isset($_POST['eliminar_tipo'])) {
        $id_tipo = (int) ($_POST['id_tipo'] ?? 0);
        if ($id_tipo > 0) {
            $res = eliminarTipoUsuario($pdo, $id_tipo);
            $mensaje = $res ? ' Tipo de usuario eliminado' : ' Error al eliminar';
        }
    }

    if (isset($_POST['crear_area'])) {
        $nombre_area = trim($_POST['nombre_area'] ?? '');
        if ($nombre_area !== '') {
            $res = crearArea($pdo, $nombre_area);
            $mensaje = $res ? 'Área creada correctamente' : ' Error al crear el área';
        }
    } elseif (isset($_POST['actualizar_area'])) {
        $id_area     = (int) ($_POST['id_area'] ?? 0);
        $nombre_area = trim($_POST['nombre_area'] ?? '');
        if ($id_area > 0 && $nombre_area !== '') {
            $res = actualizarArea($pdo, $id_area, $nombre_area);
            $mensaje = $res ? ' Área actualizada' : 'Error al actualizar';
        }
    } elseif (isset($_POST['eliminar_area'])) {
        $id_area = (int) ($_POST['id_area'] ?? 0);
        if ($id_area > 0) {
            $res = eliminarArea($pdo, $id_area);
            $mensaje = $res ? ' Área eliminada' : ' Error al eliminar (puede tener usuarios asignados)';
        }
    }
}

$usuarios = [];
if ($accion === 'listar') {
    $usuarios = obtenerTodosLosUsuarios($pdo);
}

$usuario_editar = null;
if ($accion === 'editar_form' && isset($_GET['documento'])) {
    $doc_buscar = (int) $_GET['documento'];
    if ($doc_buscar > 0) {
        $usuario_editar = obtenerUsuarioPorDocumento($pdo, $doc_buscar);
    }
}

// Iniciales para cabecera
$inicialesHeader = 'A';
if (isset($_SESSION['nombre_completo'])) {
    $inicialesHeader = strtoupper(substr($_SESSION['nombre_completo'], 0, 1));
    if (strpos($_SESSION['nombre_completo'], ' ') !== false) {
        $inicialesHeader .= strtoupper(substr(strstr($_SESSION['nombre_completo'], ' '), 1, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles_index.css">
</head>
<body class="bg-light">
<div class="container py-4">

    <?php if (isset($_SESSION['nombre_completo'])): ?>
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                 style="width:50px; height:50px; font-size:1.2rem; font-weight:bold;">
                <?= htmlspecialchars($inicialesHeader) ?>
            </div>
            <div>
                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($_SESSION['nombre_completo']) ?></h6>
                <small class="text-muted">
                    <?= htmlspecialchars((string) ($_SESSION['admin_id'] ?? '')) ?> |
                    <span class="badge bg-info text-dark">admin</span>
                </small>
            </div>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a>
    </div>
    <?php endif; ?>

    <h1 class="mb-4">Gestión de Usuarios</h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-info">
            <strong><?= htmlspecialchars($mensaje) ?></strong>
        </div>
        <a href="?accion=menu" class="btn btn-secondary">← Volver al menú</a>

    <?php else: ?>

        <?php if ($accion === 'menu'): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><strong>Seleccione una opción:</strong></h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="?accion=listar" class="text-decoration-none text-primary">Listar todos los usuarios</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=crear_form" class="text-decoration-none text-primary">Crear nuevo usuario</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=editar_form" class="text-decoration-none text-primary">Actualizar usuario</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=eliminar_form" class="text-decoration-none text-primary">Eliminar usuario</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=type_products" class="text-decoration-none text-warning">Gestionar tipos de usuarios</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=gestionar_areas" class="text-decoration-none text-warning">Gestionar áreas</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=crear_admin_form" class="text-decoration-none text-primary">Crear admin</a>
                        </li>
                    </ul>
                </div>
            </div>

        <?php elseif ($accion === 'listar'): ?>
            <h2 class="mb-3">Listado de Usuarios</h2>
            <?php if (count($usuarios) > 0): ?>
                <table class="table table-striped table-bordered align-middle bg-white">
                    <thead class="table-dark">
                        <tr>
                            <th>Documento</th>
                            <th>Nombre Completo</th>
                            <th>PIN</th>
                            <th>Área</th>
                            <th>Tipo Usuario</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $u['documento']) ?></td>
                                <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                                <td><code>****</code></td>
                                <td><?= htmlspecialchars($u['nombre_area'] ?? 'Sin área asignada') ?></td>
                                <td><?= htmlspecialchars($u['nombre_tipo_usuario'] ?? 'Sin tipo') ?></td>
                                <td>
                                    <span class="badge bg-<?= $u['estado'] === 'activo' ? 'success' : 'danger' ?>">
                                        <?= htmlspecialchars($u['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="alert alert-info">No hay usuarios registrados.</p>
            <?php endif; ?>
            <a href="?accion=menu" class="btn btn-secondary mt-3">← Volver al menú</a>

        <?php elseif ($accion === 'crear_form'):
            $areas = obtenerTodasLasAreas($pdo);
        ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Nuevo Usuario</h2>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Documento de Identidad:</label>
                            <input type="number" name="documento" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo:</label>
                            <input type="text" name="nombre_completo" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">PIN inicial (4 dígitos):</label>
                            <input type="password" name="pin" required maxlength="4" minlength="4" pattern="\d{4}" class="form-control" inputmode="numeric">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña (10 alfanuméricos):</label>
                            <input type="password" name="password" required minlength="10" maxlength="10" pattern="[A-Za-z0-9]{10}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Área / Departamento:</label>
                            <select name="id_area" class="form-control">
                                <option value="">-- Sin Área Asignada --</option>
                                <?php foreach ($areas as $a): ?>
                                    <option value="<?= (int) $a['id_area'] ?>"><?= htmlspecialchars($a['nombre_area']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado:</label>
                            <select name="estado" class="form-control">
                                <option value="activo" selected>Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <p class="text-muted small mb-3">Se registrará automáticamente como <strong>Empleado</strong>.</p>
                        <button type="submit" name="crear" class="btn btn-primary">Guardar Usuario</button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'editar_form' && !$usuario_editar): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Actualizar Usuario</h2>
                    <p>Ingrese el documento del usuario que desea editar:</p>
                    <form method="GET">
                        <input type="hidden" name="accion" value="editar_form">
                        <div class="mb-3">
                            <label class="form-label">Documento:</label>
                            <input type="number" name="documento" required class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'editar_form' && $usuario_editar):
            $areas = obtenerTodasLasAreas($pdo);
        ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Editar Usuario #<?= htmlspecialchars((string) $usuario_editar['documento']) ?></h2>
                    <form method="POST">
                        <input type="hidden" name="documento" value="<?= (int) $usuario_editar['documento'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Documento (No modificable):</label>
                            <input type="number" value="<?= (int) $usuario_editar['documento'] ?>" class="form-control" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo:</label>
                            <input type="text" name="nombre_completo" value="<?= htmlspecialchars($usuario_editar['nombre_completo']) ?>" required class="form-control">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="cambiar_pin" value="1" id="cambiar_pin" class="form-check-input">
                            <label class="form-check-label" for="cambiar_pin">Cambiar PIN (validación explícita)</label>
                        </div>
                        <div class="mb-3" id="pin_nuevo_wrap" style="display:none;">
                            <label class="form-label">Nuevo PIN (4 dígitos):</label>
                            <input type="password" name="pin" maxlength="4" minlength="4" pattern="\d{4}" class="form-control" inputmode="numeric" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña (opcional):</label>
                            <input type="password" name="password" class="form-control" minlength="10" maxlength="10" pattern="[A-Za-z0-9]{10}" placeholder="Dejar en blanco para no cambiar">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Área del taller:</label>
                            <select name="id_area" class="form-control">
                                <option value="">-- Sin Área Asignada --</option>
                                <?php foreach ($areas as $a): ?>
                                    <option value="<?= (int) $a['id_area'] ?>" <?= ((int) $usuario_editar['id_area'] === (int) $a['id_area']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['nombre_area']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuario:</label>
                            <select name="id_tipo_usuario" class="form-control">
                                <option value="">-- Sin Tipo --</option>
                                <?php foreach ($tipos_usuario as $t): ?>
                                    <option value="<?= (int) $t['id_tipo_usuario'] ?>" <?= ((int) $usuario_editar['id_tipo_usuario'] === (int) $t['id_tipo_usuario']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nombre_tipo_usuario']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado:</label>
                            <select name="estado" class="form-control">
                                <option value="activo" <?= $usuario_editar['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $usuario_editar['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" name="actualizar" class="btn btn-warning">Actualizar Usuario</button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'eliminar_form'): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Eliminar Usuario</h2>
                    <p>Ingrese el documento del usuario a eliminar:</p>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Documento del Usuario:</label>
                            <input type="number" name="documento" required class="form-control">
                        </div>
                        <button type="submit" name="eliminar" class="btn btn-danger"
                                onclick="return confirm('¿Está seguro de que desea eliminar este usuario?')">
                            Eliminar Usuario
                        </button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'type_products'):
            $lista_tipos = obtenerTodosLosTiposUsuario($pdo);
        ?>
            <h2>Tipos de Usuarios</h2>
            <div class="card mb-3 bg-light">
                <div class="card-body">
                    <form method="POST" class="d-flex gap-2 align-items-center">
                        <input type="text" name="nombre_tipo" class="form-control" placeholder="Nuevo tipo (ej: Empleado, Administrador)" required>
                        <button type="submit" name="crear_tipo" class="btn btn-success">+ Agregar</button>
                    </form>
                </div>
            </div>
            <?php if (count($lista_tipos) > 0): ?>
                <table class="table table-hover bg-white">
                    <thead>
                        <tr><th>ID</th><th>Nombre del Tipo de Usuario</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_tipos as $tu): ?>
                            <tr>
                                <td><?= (int) $tu['id_tipo_usuario'] ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="id_tipo" value="<?= (int) $tu['id_tipo_usuario'] ?>">
                                        <input type="text" name="nombre_tipo" value="<?= htmlspecialchars($tu['nombre_tipo_usuario']) ?>" class="form-control form-control-sm" required>
                                        <button type="submit" name="actualizar_tipo" class="btn btn-sm btn-primary">Guardar</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar este tipo?');">
                                        <input type="hidden" name="id_tipo" value="<?= (int) $tu['id_tipo_usuario'] ?>">
                                        <button type="submit" name="eliminar_tipo" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay tipos de usuario registrados.</p>
            <?php endif; ?>
            <a href="?accion=menu" class="btn btn-secondary mt-3">← Volver al menú</a>

        <?php elseif ($accion === 'gestionar_areas'):
            $lista_areas = obtenerTodasLasAreas($pdo);
        ?>
            <h2>Gestionar Áreas</h2>
            <div class="card mb-3 bg-light">
                <div class="card-body">
                    <form method="POST" class="d-flex gap-2 align-items-center">
                        <input type="text" name="nombre_area" class="form-control" placeholder="Nueva área / departamento" required>
                        <button type="submit" name="crear_area" class="btn btn-success">+ Agregar</button>
                    </form>
                </div>
            </div>
            <?php if (count($lista_areas) > 0): ?>
                <table class="table table-hover bg-white">
                    <thead>
                        <tr><th>ID</th><th>Nombre del Área</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_areas as $ar): ?>
                            <tr>
                                <td><?= (int) $ar['id_area'] ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="id_area" value="<?= (int) $ar['id_area'] ?>">
                                        <input type="text" name="nombre_area" value="<?= htmlspecialchars($ar['nombre_area']) ?>" class="form-control form-control-sm" required>
                                        <button type="submit" name="actualizar_area" class="btn btn-sm btn-primary">Guardar</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar esta área?');">
                                        <input type="hidden" name="id_area" value="<?= (int) $ar['id_area'] ?>">
                                        <button type="submit" name="eliminar_area" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay áreas registradas.</p>
            <?php endif; ?>
            <a href="?accion=menu" class="btn btn-secondary mt-3">← Volver al menú</a>

        <?php elseif ($accion === 'crear_admin_form'): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Crear Administrador</h2>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Documento:</label>
                            <input type="number" name="documento" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo:</label>
                            <input type="text" name="nombre_completo" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">PIN (4 dígitos):</label>
                            <input type="password" name="pin" required maxlength="4" minlength="4" pattern="\d{4}" class="form-control" inputmode="numeric">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña panel (10 alfanuméricos):</label>
                            <input type="password" name="password" required minlength="10" maxlength="10" pattern="[A-Za-z0-9]{10}" class="form-control">
                        </div>
                        <button type="submit" name="crear_admin" class="btn btn-primary">Registrar Administrador</button>
                        <a href="?accion=menu" class="btn btn-secondary"> Cancelar</a>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
(function () {
    const chk = document.getElementById('cambiar_pin');
    const wrap = document.getElementById('pin_nuevo_wrap');
    const pinInput = wrap ? wrap.querySelector('input[name="pin"]') : null;
    if (!chk || !wrap || !pinInput) return;
    chk.addEventListener('change', function () {
        const on = chk.checked;
        wrap.style.display = on ? 'block' : 'none';
        pinInput.disabled = !on;
        pinInput.required = on;
        if (!on) pinInput.value = '';
    });
} )();
</script>
</body>
<footer>
    <?php 
    // Cargar el footer reutilizable
    require_once __DIR__ . '/../includes/footer.php'; 
    ?>
</footer>
</html>
