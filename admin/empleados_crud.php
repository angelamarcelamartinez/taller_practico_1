<?php
session_start();

require_once __DIR__ . '/../connection/archivo_conexion.php';
require_once __DIR__ . '/../functions/product_functions.php';

if (!isset($_SESSION['tip_user']) || $_SESSION['tip_user'] !== 'administrador') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die('Error de conexión a la base de datos');
}

$tipos = obtenerTodosLosTipos($pdo);

$accion = $_GET['accion'] ?? 'menu';
$mensaje = '';


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CREAR
    if (isset($_POST['crear'])) {
        $nombre = trim($_POST['product_name'] ?? '');
        $precio = (isset($_POST['product_price']) && $_POST['product_price'] !== '') ? floatval($_POST['product_price']) : null;
        $tipoid  =isset($_POST['type_id']) && $_POST['type_id'] !== '' ? intval($_POST['type_id']): null;

        $imagen = null;
        $alert_img = '';

        $rutadestino = __DIR__ . '/img/';

        if (!is_dir($rutadestino)) {
            mkdir($rutadestino, 0777, true);
        }

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $ext = strtolower
            (pathinfo($_FILES['imagen'] ['name'], PATHINFO_EXTENSION));
            $max = 3 * 1024 * 1024; //3MB

            if ($ext !== 'png') {
                $alert_img= "Solo se permiten archivos .png";
            } elseif
                ($_FILES['imagen']['size'] > $max) {
                    $alert_img = "El archivo no debe superar los 3 MB";
                } else {
                    $nombre_img = uniqid('img_') . '.png';
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/img/' . $nombre_img)) {
                        $imagen = $nombre_img;
                    } else {
                        $alert_img = "Error al guardar la imagen en la carpeta /img";
                    }
                 }
            } else {
                $alert_img = "No se cargó ninguna imagen. El producto se registró correctamente";
            }

        if ($nombre !== '') {
            $id_nuevo = insertarProducto($pdo, $nombre, $precio, $tipoid, $imagen);
            $mensaje = $id_nuevo ? "Producto creado con éxito. ID: $id_nuevo" : "Error al insertar";
        } else {
            $mensaje = "El nombre es obligatorio";
        }
    }

    // ACTUALIZAR
    if (isset($_POST['actualizar'])) {
        $id     = intval($_POST['product_id'] ?? 0);
        $nombre = trim($_POST['product_name'] ?? '');
        $precio = (isset($_POST['product_price']) && $_POST['product_price'] !== '') ? floatval($_POST['product_price']) : null;
        $tipoid  =isset($_POST['type_id']) && $_POST['type_id'] !== '' ? intval($_POST['type_id']): null;

        $imagen = null;
        $alert_img = '';

        $rutadestino = __DIR__ . '/img/';

        if (!is_dir($rutadestino)) {
            mkdir($rutadestino, 0777, true);
        }

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $ext = strtolower
            (pathinfo($_FILES['imagen'] ['name'], PATHINFO_EXTENSION));
            $max = 3 * 1024 * 1024; //3MB

            if ($ext !== 'png') {
                $alert_img= "Solo se permiten archivos .png";
            } elseif
                ($_FILES['imagen']['size'] > $max) {
                    $alert_img = "El archivo no debe superar los 3 MB";
                } else {
                    $nombre_img = uniqid('img_') . '.png';
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/img/' . $nombre_img)) {
                        //borrar imagen anterior si existe
                        $old = obtenerProductoPorId($pdo,$id);
                        if (!empty($old['image']) && file_exists(__DIR__ . '/img/' . $old['image'])) {
                            unlink(__DIR__ . '/img/' . $old['image']);
                        }
                        $imagen = $nombre_img;
                    } else {
                        $alert_img = "Error al guardar la imagen en la carpeta /img";
                    }
                 }
            }
             elseif (isset($_POST['borrar_imagen'])) {
                $imagen = '';
            }
            if ($id > 0 && $nombre !== '' && $alert_img===''){
                $ok = actualizarProducto($pdo, $id, $nombre, $precio, $tipoid, $imagen);
                $mensaje = $ok ? "Producto actualizado" : " No se encontró el producto";
            } else{
                $mensaje = $alert_img ?: "Datos invalidos";
            }
    }

    // ELIMINAR
    if (isset($_POST['eliminar'])) {
        $id = intval($_POST['product_id'] ?? 0);

        if ($id > 0) {
            $ok = eliminarProducto($pdo, $id);
            $mensaje = $ok ? "Producto eliminado correctamente" : "No se encontró el ID del producto para eliminar";
        } else {
            $mensaje = "El ID proporcionado es inválido";
        }
    }

    // === GESTIÓN DE TIPOS (POST) ===
    if (isset($_POST['crear_tipo'])) {
        $nombre_tipo = trim($_POST['nombre_tipo']);
        if ($nombre_tipo != '') {
            $res = crearTipo($pdo, $nombre_tipo);
            $mensaje = $res ? "✅ Tipo creado correctamente" : "❌ Error al crear tipo";
        }

    } elseif (isset($_POST['actualizar_tipo'])) {
        $id_tipo = intval($_POST['id_tipo']);
        $nombre_tipo = trim($_POST['nombre_tipo']);
        if ($id_tipo > 0 && $nombre_tipo != '') {
            $res = actualizarTipo($pdo, $id_tipo, $nombre_tipo);
            $mensaje = $res ? "✅ Tipo actualizado" : "❌ Error al actualizar";
        }

    } elseif (isset($_POST['eliminar_tipo'])) {
        $id_tipo = intval($_POST['id_tipo']);
        if ($id_tipo > 0) {
            $res = eliminarTipo($pdo, $id_tipo);
            $mensaje = $res ? "✅ Tipo eliminado" : "❌ Error al eliminar";
        }
    }

}

// Listar productos
$products = [];
if ($accion === 'listar') {
    $products = obtenerTodosLosProductos($pdo);
}

// Buscar producto para editar
$producto_editar = null;
if ($accion === 'editar_form' && isset($_GET['product_id'])) {
    $id_buscar = intval($_GET['product_id']);
    if ($id_buscar > 0) {
        $producto_editar = obtenerProductoPorId($pdo, $id_buscar);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container py-4">
    <div class="container py-4">
    <?php if (isset($_SESSION['nombre'])): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
            <div class ="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                style="width:50px; height:50px; font-size: 1.2rem; font-weight:bold;">
                    <?php 
                    $iniciales = strtoupper(substr($_SESSION['nombre'], 0,1));
                    if (strpos($_SESSION['nombre'], ' ')!== false)  {
                        $iniciales = strtoupper(substr(strstr($_SESSION['nombre'], ' '),1,1));
                    }
                    echo $iniciales;
                    ?>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($_SESSION['nombre']) ?></h6>
                    <small class="text-muted">
                        <?= htmlspecialchars($_SESSION['email'] ?? 'usuario@sistema.com') ?> |
                        <span class = "badge bg-info text-dark">
                            <?= htmlspecialchars($_SESSION['type_user'] ?? 'admin') ?>
                        </span>
                    </small>
                </div>
            </div>
        </div>
</div>
<?php endif; ?>
    <h1>CRUD de Productos</h1>

    <?php if ($mensaje): ?>
        <!-- MENSAJE DE RESULTADO -->
        <div class="alert alert-info">
            <strong><?= htmlspecialchars($mensaje) ?></strong>
        </div>
        <a href="?accion=menu" class="btn btn-secondary">← Volver al menú</a>

    <?php else: ?>

        <?php if ($accion === 'menu'): ?>
            <!-- MENÚ PRINCIPAL -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><strong>Seleccione una opción:</strong></h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="?accion=listar" class="text-decoration-none text-primary">Listar todos los productos</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=crear_form" class="text-decoration-none text-primary">Crear nuevo producto</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=editar_form" class="text-decoration-none text-primary">Actualizar producto</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=eliminar_form" class="text-decoration-none text-primary">Eliminar producto</a>
                        </li>
                        <li class="list-group-item">
                            <a href="?accion=type_products" class="text-decoration-none text-warning">Gestionar tipos de productos</a>
                        </li>
                    </ul>
                </div>
            </div>

        <?php elseif ($accion === 'listar'): ?>
            <!-- LISTAR PRODUCTOS -->
            <h2 class="mb-3">Listado de Productos</h2>
            <?php if (count($products) > 0): ?>
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['product_id']) ?></td>
                                <td>
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="img/<?= htmlspecialchars($p['image']) ?>" style="max-height:50px; border-radius:4px;" alt="Producto">
                                    <?php else: ?>
                                        <span class="text-muted">Sin foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['product_name']) ?></td>
                                <td><?= htmlspecialchars($p['type_name'] ?? 'Sin tipo') ?></td>
                                <td>$<?= number_format($p['product_price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="alert alert-info">No hay productos registrados.</p>
            <?php endif; ?>
            <a href="?accion=menu" class="btn btn-secondary mt-3">← Volver al menú</a>

        <?php elseif ($accion === 'crear_form'): ?>
            <!-- CREAR PRODUCTO -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Nuevo Producto <i class="fa-solid fa-circle-plus"></i></h2>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Producto:</label>
                            <input type="text" name="product_name" required class="form-control" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio del Producto:</label>
                            <input type="number" step="0.01" name="product_price" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">tipo de Producto:</label>
                            <select name="type_id" class="form-control">
                                <option value=""> Sin Titulo</option>
                                <?php foreach ($tipos as $t):?>
                                    <option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen </label>
                            <input type="file" name="imagen" class="form-control" accept=".png">
                        </div>
                        <button type="submit" name="crear" class="btn btn-primary">Guardar</button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'editar_form' && !$producto_editar): ?>
            <!-- BUSCAR PRODUCTO PARA EDITAR -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Editar Producto</h2>
                    <p>Ingrese el ID del producto que desea editar:</p>
                    <form action="" method="GET">
                        <input type="hidden" name="accion" value="editar_form">
                        <div class="mb-3">
                            <label class="form-label">ID del Producto:</label>
                            <input type="number" name="product_id" required min="1" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'editar_form' && $producto_editar): ?>
            <!-- FORMULARIO DE EDICIÓN CON DATOS CARGADOS -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Editar Producto #<?= htmlspecialchars($producto_editar['product_id']) ?></h2>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($producto_editar['product_id']) ?>">
                        <div class="mb-3">
                            <label class="form-label">ID:</label>
                            <input type="number" value="<?= htmlspecialchars($producto_editar['product_id']) ?>" class="form-control" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre:</label>
                            <input type="text" name="product_name" value="<?= htmlspecialchars($producto_editar['product_name']) ?>" required class="form-control" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio:</label>
                            <input type="number" step="0.01" name="product_price" value="<?= htmlspecialchars($producto_editar['product_price']) ?>" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Producto:</label>
                            <select name="type_id" class="form-control">
                                <?php foreach ($tipos as $t):?>
                                    <option value="<?= $t['type_id'] ?>">
                                        <option value="<?= $t['type_id'] ?>"
                                        <?= ($producto_editar['type_id'] == $t['type_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['type_name']) ?>
                                    </option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="" class="form-label">Imagen actual</label>
                            <?php if ($producto_editar['image']): ?>
                                <img src="img/<?= htmlspecialchars($producto_editar['image']) ?>"
                                style="max-height: 100px; border-radius: 4px;" class="d-block mb-2">
                                <div class="form-check">
                                    <input class= "form-check-input" type="checkbox" name="borrar_imagen" id="delImg">
                                    <label class="form-check-label" for="delImg">Eliminar imagen actual</label>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Sin Imagen</span>
                            <?php endif; ?>
                            <label for="" class="form-label mt-2">Subir nueva (.png, max 3 MB):</label>
                            <input type="file" name="imagen" class="form-control" accept=".png">
                        </div>
                        <button type="submit" name="actualizar" class="btn btn-warning">Actualizar</button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'eliminar_form'): ?>
            <!-- ELIMINAR PRODUCTO -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Eliminar Producto <i class="fa-solid fa-trash-can"></i></h2>
                    <p>Ingrese el ID del producto a eliminar:</p>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">ID del Producto:</label>
                            <input type="number" name="product_id" required min="1" class="form-control">
                        </div>
                        <button type="submit" name="eliminar" class="btn btn-danger"
                            onclick="return confirm('¿Está seguro de que desea eliminar este producto?')">
                            Eliminar
                        </button>
                        <a href="?accion=menu" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        <?php elseif ($accion === 'type_products'):
            $lista_tipos=obtenerTodosLosTipos($pdo)
        ?>
            <h2> Tipos de Productos</h2>

            <!-- FORMULARIO RAPIDO PARA AGREGAR-->
             <div class= "card mb-3 bg-light">
                <div class="card-body">
                    <form method="POST" class="d-flex gap-2 align-items-center">
                        <input type="text" name="nombre_tipo" class="form-control" placeholder="Nuevo tipo (ej: Bafles)" required></inpput>
                        <button type="submit" name="crear_tipo" class="btn btn-success">+ Agregar</button>
                    </form>
                </div>
             </div>
        <?php if (count($lista_tipos)>0): ?>
            <table class="table table-hover"
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_tipos as $tipos): ?>
                        <tr>
                            <td><?= $tipos['type_id']?></td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="id_tipo" value="<?= $tipos['type_id'] ?>">
                                    <input type="text" name="nombre_tipo" value="<?= htmlspecialchars($tipos['type_name']) ?>"
                                           class="form-control form-control-sm" required>
                                    <button type="submit" name="actualizar_tipo" class="btn btn-sm btn-primary">Guardar</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Eliminar este tipo? Los productos perderan su categoria.');">
                                    <input type="hidden" name="id_tipo" value=<?= $tipos['type_id'] ?>">
                                    <button type="submit" name="eliminar_tipo" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay tipo registrados.</p>
        <?php endif; ?>
        <a href="?accion=menu" class="btn btn-secondary mt-3"><-volver al menú</a>
        
    <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>