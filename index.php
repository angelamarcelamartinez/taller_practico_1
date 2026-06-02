<?php
session_start();

// 🔗 Conexión y funciones
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/funciones.php';

// ️ Conexión a BD
$db = new Database();
$pdo = $db->conectar();
if (!$pdo) {
    die('<div class="alert alert-danger text-center mt-5"> Error de conexión a la base de datos</div>');
}

// 📦 Obtener productos con sus tipos
$productos = obtenerTodosLosProductos($pdo);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TiendaPro | Catálogo</title>
    <!-- Bootstrap 5 + FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!--  NAVBAR -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cube me-2"></i>TiendaPro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="#catalogo">Catálogo</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <a href="login.php" class="btn btn-login btn-sm px-3">
                        <i class="fas fa-user-lock me-1"></i> Iniciar Sesión
                    </a>

                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <header class="hero">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Bienvenido a TiendaPro</h1>
            <p class="lead mb-4 opacity-90">Productos seleccionados con calidad garantizada y precios competitivos</p>
            <a href="#catalogo" class="btn btn-light btn-lg px-4 fw-semibold">Explorar Catálogo</a>
        </div>
    </header>

    <!-- CATÁLOGO DE PRODUCTOS -->
    <section id="catalogo" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold position-relative">
                Nuestros Productos
                <span class="d-block w-25 mx-auto mt-2" style="height:3px; background:var(--primary); border-radius:2px;"></span>
            </h2>

            <?php if (count($productos) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($productos as $p): ?>
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                            <div class="card product-card h-100">
                                <img src="<?= !empty($p['image']) ? 'img/' . htmlspecialchars($p['image']) : 'https://previews.123rf.com/images/koblizeek/koblizeek2208/koblizeek220800128/190320173-no-image-vector-symbol-missing-available-icon-no-gallery-for-this-moment-placeholder.jpg' ?>"
                                    class="product-img" alt="<?= htmlspecialchars($p['product_name']) ?>">
                                <div class="card-body d-flex flex-column p-3">
                                    <div class="mb-2">
                                        <span class="badge-type"><?= htmlspecialchars($p['type_name'] ?? 'Sin Categoría') ?></span>
                                    </div>
                                    <h5 class="card-title fw-bold mb-1"><?= htmlspecialchars($p['product_name']) ?></h5>
                                    <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars(substr($p['product_name'], 0, 45)) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                        <span class="fs-5 fw-bold text-primary">$<?= number_format($p['product_price'], 2) ?></span>
                                        <button class="btn btn-outline-primary btn-sm"><i class="fas fa-cart-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted fs-5">Aún no hay productos en el catálogo.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!--  FOOTER -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> TiendaPro. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>