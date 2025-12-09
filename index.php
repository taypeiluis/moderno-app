<?php
require 'inc/header.php';

// Fallback por si e() no existe
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// Número de WhatsApp en formato internacional SIN '+' (ej: 51 + número)
$whatsappNumero = '51999999999'; // <-- CAMBIA ESTE NÚMERO POR EL TUYO

if (!function_exists('esURL')) {
    function esURL($cadena) {
        return filter_var($cadena, FILTER_VALIDATE_URL) !== false;
    }
}

// Obtener banners (si existen)
$banners = $conn->query("SELECT * FROM banners ORDER BY orden ASC")->fetch_all(MYSQLI_ASSOC);

// Obtener categorías
$cats = $conn->query("SELECT id,nombre FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// ----------------------
// FILTROS (categoría + búsqueda)
// ----------------------
$where = "1=1";

if (!empty($_GET['cat'])) {
    $where .= " AND p.categoria_id = " . (int)$_GET['cat'];
}

if (!empty($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $where .= " AND (p.nombre LIKE '%$q%' OR p.descripcion LIKE '%$q%')";
}

$sql = "SELECT p.*, c.nombre AS categoria 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE $where
        ORDER BY p.id DESC";
$productos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-4 text-light"><!-- TEXTO CLARO EN TODO EL CONTENIDO -->

  <!-- HERO / BANNERS -->
  <div class="row mb-4">
    <div class="col-12">
      <?php if (!empty($banners)): ?>
        <div id="heroCarousel" class="carousel slide hero" data-bs-ride="carousel">
          <div class="carousel-inner">
            <?php foreach ($banners as $i => $bn): ?>
              <?php
              // Detectar si es URL externa o imagen subida
              if (!empty($bn['imagen'])) {

                  if (esURL($bn['imagen'])) {
                      $bannerSrc = $bn['imagen'];
                  } elseif (file_exists('uploads/banners/'.$bn['imagen'])) {
                      $bannerSrc = 'uploads/banners/'.$bn['imagen'];
                  } else {
                      $bannerSrc = 'assets/hero/streaming-1.jpg';
                  }

              } else {
                  $bannerSrc = 'assets/hero/streaming-1.jpg';
              }
              ?>
              <div class="carousel-item <?php if ($i === 0) echo 'active'; ?>">
                <img src="<?= e($bannerSrc) ?>"
                     class="d-block w-100"
                     style="height:50vh;max-height:380px;object-fit:cover"
                     alt="<?= e($bn['titulo'] ?? 'Banner') ?>">

                <?php if (!empty($bn['titulo']) || !empty($bn['enlace'])): ?>
                  <div class="carousel-caption d-none d-md-block">
                    <?php if (!empty($bn['titulo'])): ?>
                      <h2 class="text-light"><?= e($bn['titulo']) ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($bn['enlace'])): ?>
                      <a class="btn btn-sm btn-primary" href="<?= e($bn['enlace']) ?>">
                        Ver más
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <button class="carousel-control-prev" type="button"
                  data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button"
                  data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div>
      <?php else: ?>
        <img src="assets/hero/streaming-1.jpg"
             class="img-fluid hero"
             style="height:50vh;max-height:380px;object-fit:cover"
             alt="Streaming Market">
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <!-- SIDEBAR: INFO DISTRIBUIDOR + FILTROS -->
    <div class="col-12 col-lg-3 mb-3">
      <!-- Info distribuidor -->
      <div class="card glass mb-3 p-3 text-light"><!-- card con texto claro -->
        <h5 class="mb-2">Acceso distribuidor</h5>

        <?php if (!empty($esMayorista) && $esMayorista): ?>
          <p class="text-success small mb-1">
            <i class="bi bi-unlock"></i> Modo distribuidor activo.
          </p>
          <p class="small text-secondary mb-0">
            Estás viendo precios especiales en todo el catálogo.
          </p>
        <?php else: ?>
          <p class="small text-secondary mb-0">
            Si eres distribuidor, usa el botón
            <strong>“Acceso Distribuidor”</strong> en la parte superior
            e ingresa tu código para ver precios especiales.
          </p>
        <?php endif; ?>
      </div>

      <!-- Filtros -->
      <div class="card glass p-3 text-light"><!-- card con texto claro -->
        <h5 class="mb-3">Filtrar</h5>
        <form method="get">
          <div class="mb-3">
            <label class="form-label">Categoría</label>
            <select name="cat" class="form-select bg-dark text-light border-secondary">
              <option value="">Todas</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>"
                  <?php if (($_GET['cat'] ?? '') == $c['id']) echo 'selected'; ?>>
                  <?= e($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if (!empty($_GET['q'])): ?>
            <input type="hidden" name="q" value="<?= e($_GET['q']) ?>">
          <?php endif; ?>

          <button class="btn btn-sm btn-primary w-100">Aplicar</button>
        </form>
      </div>
    </div>

    <!-- CONTENIDO PRINCIPAL: BUSCADOR + PRODUCTOS -->
    <div class="col-12 col-lg-9">
      <!-- Buscador -->
      <form class="mb-3" method="get">
        <div class="input-group">
          <input name="q"
                 value="<?= e($_GET['q'] ?? '') ?>"
                 class="form-control bg-dark text-light border-secondary"
                 placeholder="Buscar producto o descripción...">
          <?php if (!empty($_GET['cat'])): ?>
            <input type="hidden" name="cat" value="<?= (int)$_GET['cat'] ?>">
          <?php endif; ?>
          <button class="btn btn-primary">Buscar</button>
        </div>
      </form>

      <!-- Grid de productos -->
      <div class="row g-3">
        <?php foreach ($productos as $p): ?>
          <div class="col-6 col-sm-4 col-md-3 col-xl-3">

            <div class="card card-product h-100 d-flex flex-column text-light bg-transparent border-0">
              <?php
              // Imagen del producto (URL externa o subida)
              if (!empty($p['imagen'])) {

                  if (esURL($p['imagen'])) {
                      $imgSrc = $p['imagen'];
                  } elseif (file_exists('uploads/productos/'.$p['imagen'])) {
                      $imgSrc = 'uploads/productos/'.$p['imagen'];
                  } else {
                      $imgSrc = 'assets/placeholder.png';
                  }

              } else {
                  $imgSrc = 'assets/placeholder.png';
              }

              // Precio que se muestra (normal o distribuidor)
              if (!empty($esMayorista) && $esMayorista && !empty($p['precio_mayor'])) {
                  $precioMostrar = $p['precio_mayor'];
              } else {
                  $precioMostrar = $p['precio_unitario'];
              }

              // Texto para WhatsApp: "Nombre del producto - S/ Precio"
              $textoProducto = $p['nombre'].' - S/ '.number_format($precioMostrar, 2);
              $mensajeWa = 'Hola, quisiera más información del producto: '.$textoProducto;
              $waLink   = 'https://wa.me/'.$whatsappNumero.'?text='.urlencode($mensajeWa);
              ?>

              <!-- Imagen responsiva con relación fija: no se deforma -->
              <div class="product-thumb-wrapper">
                <img src="<?= e($imgSrc) ?>"
                     alt="<?= e($p['nombre']) ?>"
                     class="product-thumb-img">
              </div>

              <div class="card-body d-flex flex-column p-2">
                <?php if (!empty($p['categoria'])): ?>
                  <small class="badge badge-cat mb-1">
                    <?= e($p['categoria']) ?>
                  </small>
                <?php endif; ?>

                <h6 class="card-title mb-1 text-truncate" title="<?= e($p['nombre']) ?>">
                  <?= e($p['nombre']) ?>
                </h6>

                <p class="small text-secondary mb-2" style="min-height:3rem;">
                  <?= e(substr($p['descripcion'], 0, 80)) ?>
                  <?php if (strlen($p['descripcion']) > 80) echo '…'; ?>
                </p>

                <div class="mt-auto">
                  <?php if (!empty($esMayorista) && $esMayorista && !empty($p['precio_mayor'])): ?>
                    <div class="price-wholesale">
                      S/. <?= number_format($p['precio_mayor'], 2) ?>
                    </div>
                  <?php else: ?>
                    <div class="price-unit">
                      S/. <?= number_format($p['precio_unitario'], 2) ?>
                    </div>
                  <?php endif; ?>

                  <a href="producto.php?id=<?=
                     (int)$p['id'] ?>"
                     class="btn btn-sm btn-outline-light mt-2 w-100">
                    Detalles
                  </a>

                  <a href="<?= e($waLink) ?>"
                   target="_blank"
                   rel="noopener"
                   class="btn btn-sm btn-whatsapp mt-1">
                  <i class="bi bi-whatsapp"></i> Comprar
                </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($productos)): ?>
          <div class="col-12">
            <div class="alert alert-info">
              No se encontraron productos.
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require 'inc/footer.php'; ?>
