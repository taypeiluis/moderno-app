<?php
require 'inc/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
    echo '<div class="alert alert-danger mt-4">Producto no encontrado</div>';
    require 'inc/footer.php';
    exit;
}

$p = $res->fetch_assoc();
?>

<div class="product-page">

  <div class="product-hero">
    <div class="row g-4 align-items-start">
      <!-- Columna imagen / badges -->
      <div class="col-md-4">
        <div class="product-card-left">
          <?php if (!empty($p['imagen'])): ?>
            <img src="uploads/<?= e($p['imagen']) ?>" class="product-main-img" alt="<?= e($p['nombre']) ?>">
          <?php else: ?>
            <div class="product-placeholder">
              <?= strtoupper(substr($p['nombre'],0,2)) ?>
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

              <?php
                // Puedes construir tu enlace de WhatsApp aquí
                $mensaje = urlencode("Hola, quiero comprar el producto: ".$p['nombre']);
                $wa = "https://wa.me/51999999999?text=".$mensaje;
              ?>
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
