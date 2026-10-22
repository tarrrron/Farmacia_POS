<div class="page-title dashboard-title">
    <div><h1>Hola, <?= htmlspecialchars($_SESSION["nombre"] ?? "") ?></h1><p>Resumen de actividad de tu farmacia.</p></div>
    <span><?= htmlspecialchars(date("d/m/Y"), ENT_QUOTES, "UTF-8") ?></span>
</div>

<section class="summary-grid dashboard-summary">
    <article class="summary-card card-blue"><span>Ventas de hoy</span><strong>S/ <?= number_format($summary["ventas_dia"], 2) ?></strong><small>Ventas registradas durante el dia</small></article>
    <article class="summary-card card-green"><span>Ventas del mes</span><strong>S/ <?= number_format($summary["ventas_mes"], 2) ?></strong><small><?= (int)$summary["ventas_mes_cantidad"] ?> comprobantes este mes</small></article>
    <article class="summary-card card-orange"><span>Productos con stock bajo</span><strong><?= number_format($summary["stock_bajo"]) ?></strong><small>Igual o por debajo del minimo</small></article>
    <article class="summary-card card-violet"><span>Productos activos</span><strong><?= number_format($summary["productos"]) ?></strong><small>Catalogo disponible para venta</small></article>
</section>

<section class="dashboard-charts">
    <article class="panel dashboard-chart-panel">
        <div class="section-toolbar"><div><h2>Ventas de los ultimos 7 dias</h2><p>Importe diario de comprobantes registrados.</p></div><span class="chart-legend"><i></i> Ventas</span></div>
        <div class="chart-canvas-wrap"><canvas id="weekly-sales-chart" role="img" aria-label="Grafico de ventas diarias de los ultimos siete dias"></canvas></div>
        <div class="chart-accessible-data" id="weekly-sales-data" aria-live="polite"></div>
    </article>
    <article class="panel dashboard-stock-panel">
        <div class="section-toolbar"><div><h2>Estado del stock</h2><p>Productos activos por nivel de existencias.</p></div></div>
        <div class="stock-chart-wrap"><canvas id="stock-status-chart" role="img" aria-label="Grafico del estado de stock de productos"></canvas><div class="stock-chart-center"><strong><?= number_format(array_sum($stockStatus)) ?></strong><span>productos</span></div></div>
        <ul class="stock-legend" id="stock-legend"></ul>
    </article>
</section>

<section class="dashboard-lower-grid">
    <article class="panel">
        <div class="section-toolbar"><div><h2>Reponer pronto</h2><p>Productos en su minimo o sin existencias.</p></div><a class="text-link" href="index.php?route=module/productos">Ver productos</a></div>
        <?php if (!$lowStockProducts): ?><div class="dashboard-empty"><span class="empty-check">✓</span><strong>Todo en orden</strong><p>No hay productos por debajo del stock minimo.</p></div>
        <?php else: ?><div class="low-stock-list"><?php foreach ($lowStockProducts as $product): ?><div class="low-stock-item"><span class="stock-alert-icon <?= (float)$product["stock"] <= 0 ? "is-out" : "" ?>">!</span><span class="low-stock-name"><strong><?= htmlspecialchars($product["nombre"], ENT_QUOTES, "UTF-8") ?></strong><small>Minimo: <?= number_format((float)$product["stockseguridad"], 2) ?> <?= htmlspecialchars($product["unidad"], ENT_QUOTES, "UTF-8") ?></small></span><span class="stock-quantity <?= (float)$product["stock"] <= 0 ? "is-out" : "" ?>"><?= (float)$product["stock"] <= 0 ? "Agotado" : number_format((float)$product["stock"], 2) . " " . htmlspecialchars($product["unidad"], ENT_QUOTES, "UTF-8") ?></span></div><?php endforeach; ?></div><?php endif; ?>
    </article>
    <article class="panel">
        <div class="section-toolbar"><div><h2>Ventas recientes</h2><p>Ultimos comprobantes registrados.</p></div><a class="text-link" href="index.php?route=module/ventas">Ver todas</a></div>
        <?php if (!$recentSales): ?><div class="dashboard-empty"><strong>Aun no hay ventas</strong><p>Cuando registres una venta, aparecera aqui.</p><a class="btn btn-primary btn-sm" href="index.php?route=module/ventas/crear">Registrar venta</a></div>
        <?php else: ?><div class="recent-sales-list"><?php foreach ($recentSales as $sale): ?><a class="recent-sale-item" href="index.php?route=module/ventas/ver&amp;id=<?= (int)$sale["idventa"] ?>"><span class="recent-sale-icon">↗</span><span class="recent-sale-info"><strong><?= htmlspecialchars($sale["cliente"] ?: "Cliente general", ENT_QUOTES, "UTF-8") ?></strong><small><?= htmlspecialchars($sale["comprobante"] . " " . $sale["serie"] . "-" . str_pad((string)$sale["correlativo"], 8, "0", STR_PAD_LEFT), ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars(date("d/m H:i", strtotime($sale["fecha"])), ENT_QUOTES, "UTF-8") ?></small></span><strong class="recent-sale-total">S/ <?= number_format((float)$sale["total"], 2) ?></strong></a><?php endforeach; ?></div><?php endif; ?>
    </article>
</section>

<script type="application/json" id="dashboard-chart-data"><?= json_encode(["sales"=>$weeklySales,"stock"=>$stockStatus], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
