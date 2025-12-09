<?php 
require 'inc/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="container py-4"><div class="alert alert-danger">Producto no válido</div></div>';
    require 'inc/footer.php';
    exit;
}

// Función para escapar, por si acaso
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// Función para detectar si es URL externa
if (!function_exists('esURL')) {
    function esURL($cadena) {
        return filter_var($cadena, FILTER_VALIDATE_URL) !== false;
    }
}

$stmt = $conn->prepare("
  SELECT p.*, c.nombre AS categoria
  FROM productos p
  LEFT JOIN categorias c ON p.categoria_id = c.id
  WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    echo '<div class="container py-4"><div class="alert alert-danger mt-4">Producto no encontrado</div></div>';
    require 'inc/footer.php';
    exit;
}

$p = $res->fetch_assoc();

// ----------------------
// RESOLVER IMAGEN IGUAL QUE EN index.php
// ----------------------
$imgSrc = null;

if (!empty($p['imagen'])) {

    if (esURL($p['imagen'])) {
        // URL externa (https://...)
        $imgSrc = $p['imagen'];

    } elseif (file_exists('uploads/productos/' . $p['imagen'])) {
        // Archivo dentro de uploads/productos/
        $imgSrc = 'uploads/productos/' . $p['imagen'];

    } elseif (file_exists('uploads/' . $p['imagen'])) {
        // Fallback por si algunas están en uploads/ a secas
        $imgSrc = 'uploads/' . $p['imagen'];
    }
}

// ----------------------
// PRECIOS Y WHATSAPP
// ----------------------

// Usa el mismo número que en index.php (formato internacional sin '+')
$whatsappNumero = '51999999999'; // <-- CAMBIA ESTO POR TU NÚMERO REAL

// Elegir el precio según si es distribuidor o no
if (!empty($esMayorista) && $esMayorista && !empty($p['precio_mayor'])) {
    $precioMostrar = $p['precio_mayor'];
} else {
    $precioMostrar = $p['precio_unitario'];
}

// Texto que se enviará por WhatsApp
$textoProducto = $p['nombre'] . ' - S/ ' . number_format($precioMostrar, 2);
$mensaje = "Hola, quisiera más información del producto: " . $textoProducto;

// Enlace a WhatsApp
$wa = "https://wa.me/" . $whatsappNumero . "?text=" . urlencode($mensaje);
?>

<div class="product-page">

  <div class="product-hero">
    <div class="row g-4 align-items-start">
      <!-- Columna imagen / badges -->
      <div class="col-md-4">
        <div class="product-card-left">
          <?php if ($imgSrc): ?>
            <!-- Imagen corregida y responsiva -->
            <img src="<?= e($imgSrc) ?>" 
                 class="product-main-img img-fluid" 
                 alt="<?= e($p['nombre']) ?>">
          <?php else: ?>
            <!-- Placeholder si no hay imagen válida -->
            <div class="product-placeholder">
              <?= strtoupper(substr($p['nombre'], 0, 2)) ?>
            </div>
          <?php endif; ?>

          <div class="mt-3 small text-muted">
            <span class="badge badge-soft">En stock</span>
            <?php if (!empty($p['categoria'])): ?>
              <span class="badge badge-cat"><?= e($p['categoria']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Columna info principal -->
      <div class="col-md-8">
        <div class="product-main-info">
          <h1 class="product-title">
            <?= e($p['nombre']) ?>
          </h1>

          <p class="product-subtitle">
            PLAN MENSUAL · 1 DISPOSITIVO · CALIDAD UHD · GARANTÍA 30 DÍAS
          </p>

          <div class="product-price-box">
            <span class="label">Precio</span>
            <div class="price-line">
              <?php if (!empty($esMayorista) && $esMayorista && !empty($p['precio_mayor'])): ?>
                <span class="price-main">S/. <?= number_format($p['precio_mayor'], 2) ?></span>
                <span class="price-tag">Precio distribuidor</span>
              <?php else: ?>
                <span class="price-main">S/. <?= number_format($p['precio_unitario'], 2) ?></span>
                <span class="price-tag">Precio público</span>
              <?php endif; ?>
            </div>
          </div>

          <form class="product-actions mt-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <div class="qty-box">
                <label for="qty" class="small text-muted mb-1">Cantidad</label>
                <input type="number" id="qty" name="cantidad" min="1" value="1">
              </div>

              <button type="button" class="btn-primary-main">
                Comprar ahora
              </button>

              <a href="<?= $wa ?>" target="_blank" class="btn-whatsapp-main">
                Comprar vía WhatsApp
              </a>

            </div>
          </form>

          <?php if (!empty($p['descripcion'])): ?>
          <div class="product-short-description mt-4">
            <?= nl2br(e($p['descripcion'])) ?>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <!-- Sección tipo “Descripción / info extra” -->
  <div class="product-extra mt-4">
    <div class="tabs-header">
      <button class="tab-btn active" type="button">Descripción</button>
      <!-- Si luego quieres más pestañas, las agregas aquí -->
    </div>
    <div class="tabs-body">
      <p>
        <!-- Aquí puedes usar una descripción más larga si la tuvieras -->
        <?= !empty($p['descripcion_larga']) 
              ? nl2br(e($p['descripcion_larga'])) 
              : nl2br(e($p['descripcion'] ?? '')) ?>
      </p>

      <h5 class="mt-3">¿Tienes alguna duda?</h5>
      <p class="small text-muted">
        Escríbenos por WhatsApp y te ayudamos con tu compra o con cualquier consulta
        sobre este servicio.
      </p>
      <a href="<?= $wa ?>" target="_blank" class="btn-whatsapp-outline">
        Hablar por WhatsApp
      </a>
    </div>
  </div>

  <div class="mt-4">
    <a href="index.php" class="btn-back">
      ← Volver al catálogo
    </a>
  </div>
</div>

<?php require 'inc/footer.php'; ?>
