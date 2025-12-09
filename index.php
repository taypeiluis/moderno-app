<?php
require 'inc/header.php';

// Número de WhatsApp en formato internacional SIN '+' (ej: 51 + número)
$whatsappNumero = '51999999999';

function esURL($cadena) {
    return filter_var($cadena, FILTER_VALIDATE_URL) !== false;
}

// Obtener banners (si existen)
$banners = $conn->query("SELECT * FROM banners ORDER BY orden ASC")->fetch_all(MYSQLI_ASSOC);

// Obtener categorías
$cats = $conn->query("SELECT id,nombre FROM categorias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Preparar filtro de búsqueda y categoría
$where = "1=1";
if (!empty($_GET['cat'])) {
    $where = "categoria_id = " . (int)$_GET['cat'];
}
if (!empty($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $where .= " AND (p.nombre LIKE '%$q%' OR p.descripcion LIKE '%$q%')";
}

// Consultar productos (paginación simple no incluida)
$sql = "SELECT p.*, c.nombre as categoria 
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE $where
        ORDER BY p.id DESC";
$productos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<div class="row mb-4">
  <div class="col-12">
    <?php if(!empty($banners)): ?>
      <div id="heroCarousel" class="carousel slide hero" data-bs-ride="carousel">
        <div class="carousel-inner">
          <?php foreach($banners as $i=>$bn): ?>

            <?php
            // Detectar si es URL externa
            if (!empty($bn['imagen'])) {

                if (esURL($bn['imagen'])) {
                    // Si es URL se usa directamente
                    $bannerSrc = $bn['imagen'];

                } elseif (file_exists('uploads/banners/'.$bn['imagen'])) {
                    // Si es archivo subido local
                    $bannerSrc = 'uploads/banners/'.$bn['imagen'];

                } else {
                    // Si no existe archivo ni URL válida
                    $bannerSrc = 'assets/hero/streaming-1.jpg';
                }

            } else {
                // Si está vacío, usamos una imagen por defecto
                $bannerSrc = 'assets/hero/streaming-1.jpg';
            }
            ?>

            <div class="carousel-item <?php if($i==0) echo 'active'; ?>">
              <img src="<?= e($bannerSrc) ?>" class="d-block w-100" style="height:380px;object-fit:cover">

              <div class="carousel-caption d-none d-md-block">
                <h2><?= e($bn['titulo']) ?></h2>
                <?php if(!empty($bn['enlace'])): ?>
                    <a class="btn btn-sm btn-primary" href="<?= e($bn['enlace']) ?>">Ver</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
        </button>
      </div>
    <?php else: ?>
      <img src="assets/hero/streaming-1.jpg" class="img-fluid hero" style="height:380px;object-fit:cover">
    <?php endif; ?>
  </div>
</div>

<div class="row">
  <div class="col-md-3 mb-3">
    <div class="card glass p-3">
      <h5 class="mb-3">Filtrar</h5>
      <form method="get">
        <div class="mb-3">
          <label class="form-label">Categoría</label>
          <select name="cat" class="form-select">
            <option value="">Todas</option>
            <?php foreach($cats as $c): ?>
              <option value="<?= $c['id'] ?>" <?php if(($_GET['cat'] ?? '')==$c['id']) echo 'selected'; ?>>
                <?= e($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-sm btn-primary w-100">Aplicar</button>
      </form>
    </div>
  </div>

  <div class="col-md-9">
    <form class="mb-3" method="get">
      <div class="input-group">
        <input name="q" value="<?= e($_GET['q'] ?? '') ?>" ...s="form-control" placeholder="Buscar producto o descripción...">
        <button class="btn btn-primary">Buscar</button>
      </div>
    </form>

    <div class="row g-3">
      <?php foreach($productos as $p): ?>
        <div class="col-6 col-md-3">
          <div class="card card-product h-100">

          <?php
          // Detectar si es URL externa
          if (!empty($p['imagen'])) {

              if (esURL($p['imagen'])) {
                  // Si es URL se usa directamente
                  $imgSrc = $p['imagen'];

              } elseif (file_exists('uploads/productos/'.$p['imagen'])) {
                  // Si es archivo subido local
                  $imgSrc = 'uploads/productos/'.$p['imagen'];

              } else {
                  // Si no existe archivo ni URL válida
                  $imgSrc = 'assets/placeholder.png';
              }

          } else {
              $imgSrc = 'assets/placeholder.png';
          }

          // Precio que se está mostrando (para el mensaje de WhatsApp)
          if ($esMayorista) {
              $precioMostrar = $p['precio_mayor'];
          } else {
              $precioMostrar = $p['precio_unitario'];
          }

          // Texto: "Nombre del producto - S/ Precio"
          $textoProducto = $p['nombre'] . ' - S/ ' . number_format($precioMostrar, 2);
          $mensajeWa    = "Hola, quisiera más información del producto: " . $textoProducto;
          $waLink       = "https://wa.me/" . $whatsappNumero . "?text=" . urlencode($mensajeWa);
          ?>

          <!-- Imagen un poco más pequeña para que la tarjeta sea más compacta -->
          <img src="<?= e($imgSrc) ?>" class="card-img-top" style="height:150px;object-fit:cover">

          <div class="card-body d-flex flex-column">
            <small class="badge badge-cat"><?= e($p['categoria']) ?></small>
            <h6 class="card-title mb-1"><?= e($p['nombre']) ?></h6>

            <!-- Descripción más corta para que no crezca tanto -->
            <p class="small text-muted mb-2">
              <?= e(substr($p['descripcion'],0,80)) ?>
              <?php if(strlen($p['descripcion']) > 80) echo '…'; ?>
            </p>

            <div class="mt-auto">
              <?php if($esMayorista): ?>
                <div class="price-wholesale">S/. <?= number_format($p['precio_mayor'],2) ?></div>
              <?php else: ?>
                <div class="price-unit">S/. <?= number_format($p['precio_unitario'],2) ?></div>
              <?php endif; ?>

              <!-- Botón Detalles -->
              <a href="producto.php?id=<?= $p['id'] ?>"
                 class="btn btn-sm btn-outline-primary mt-2 w-100">
                Detalles
              </a>

              <!-- Botón verde WhatsApp -->
              <a href="<?= e($waLink) ?>"
                target="_blank"
                rel="noopener"
                class="btn btn-sm btn-whatsapp w-100 mt-1">
                <i class="bi bi-whatsapp"></i> Comprar vía Whatsapp
              </a>

            </div>
          </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if(empty($productos)): ?>
        <div class="col-12"><div class="alert alert-info">No se encontraron productos.</div></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require 'inc/footer.php'; ?>
