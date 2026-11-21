<?php
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
$from = $totalRows > 0 ? (($page - 1) * $perPage) + 1 : 0;
$to = min($page * $perPage, $totalRows);
$baseUrl = "index.php?route=module/inventario&section=" . urlencode($section) . "&page=";
?>

<div class="page-title">
    <h1>Gestión de inventario</h1>
    <p>Control de existencias, entradas y salidas de productos.</p>
</div>

<?php if ($message !== ""): ?>
    <div class="alert alert-success" role="status"><?= $e($message) ?></div>
<?php endif; ?>

<nav class="inventory-tabs" aria-label="Secciones de inventario">
    <a href="index.php?route=module/inventario&amp;section=existencias" <?= $section === "existencias" ? 'aria-current="page"' : "" ?>>Existencias</a>
    <a href="index.php?route=module/inventario&amp;section=historial" <?= $section === "historial" ? 'aria-current="page"' : "" ?>>Historial de movimientos</a>
</nav>

<?php if ($section === "existencias"): ?>
    <section class="panel inventory-summary">
        <div class="section-toolbar">
            <div>
                <h2>Resumen de inventario</h2>
                <p>Estado actual de los productos activos.</p>
            </div>
            <a class="btn btn-primary" href="index.php?route=module/inventario/crear">Registrar movimiento</a>
        </div>

        <div class="inventory-summary-grid">
            <article class="inventory-metric"><span>Productos activos</span><strong><?= (int) $resumen["total_productos"] ?></strong></article>
            <article class="inventory-metric"><span>Stock total</span><strong><?= number_format((float) $resumen["unidades"], 0) ?></strong></article>
            <article class="inventory-metric"><span>Stock bajo</span><strong><?= (int) $resumen["stock_bajo"] ?></strong></article>
            <article class="inventory-metric"><span>Agotados</span><strong><?= (int) $resumen["agotados"] ?></strong></article>
        </div>
    </section>

    <section class="panel">
        <div class="section-toolbar">
            <div><h2>Existencias de productos</h2><p>Consulta el stock disponible y el mínimo.</p></div>
        </div>
        <input class="table-filter" type="search" data-filter-target="#inventario-table-1" placeholder="Buscar productos..." aria-label="Buscar productos">
        <div class="table-wrap">
            <table class="data-table product-table" id="inventario-table-1">
                <thead><tr><th>Producto</th><th>Categoría</th><th>Stock actual</th><th>Stock mínimo</th><th>Estado de stock</th></tr></thead>
                <tbody>
                    <?php if (!$products): ?><tr><td colspan="5" class="empty-state">No hay productos registrados.</td></tr><?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $stock = (float) $product["stock"];
                        $minimo = (float) $product["stockseguridad"];
                        if ((int) $product["estado"] !== 1) { $estado = "Inactivo"; $clase = "is-muted"; }
                        elseif ($stock <= 0) { $estado = "Agotado"; $clase = "is-danger"; }
                        elseif ($stock <= $minimo) { $estado = "Stock bajo"; $clase = "is-warning"; }
                        else { $estado = "Disponible"; $clase = "is-active"; }
                        ?>
                        <tr>
                            <td><strong><?= $e($product["nombre"]) ?></strong><small><?= $e($product["codigobarra"] ?: "Sin código") ?></small></td>
                            <td><?= $e($product["categoria"]) ?></td>
                            <td><?= number_format($stock, 0) ?><small><?= $e($product["unidad"]) ?></small></td>
                            <td><?= number_format($minimo, 0) ?></td>
                            <td><span class="status-badge <?= $clase ?>"><?= $estado ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . "/_pagination.php"; ?>
    </section>
<?php else: ?>
    <section class="panel">
        <div class="section-toolbar">
            <div><h2>Historial de movimientos</h2><p>Consulta las entradas y salidas registradas.</p></div>
            <a class="btn btn-primary" href="index.php?route=module/inventario/crear">Registrar movimiento</a>
        </div>
        <input class="table-filter" type="search" data-filter-target="#inventario-table-2" placeholder="Buscar movimientos..." aria-label="Buscar movimientos">
        <div class="table-wrap">
            <table class="data-table" id="inventario-table-2">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Stock anterior</th><th>Stock nuevo</th><th>Usuario</th><th>Motivo</th></tr></thead>
                <tbody>
                    <?php if (!$movimientos): ?><tr><td colspan="8" class="empty-state">No existen movimientos registrados.</td></tr><?php endif; ?>
                    <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td><?= $e($mov["fecha"]) ?></td>
                            <td><?= $e($mov["producto"]) ?></td>
                            <td><span class="status-badge <?= $mov["tipo"] === "ENTRADA" ? "is-active" : "is-muted" ?>"><?= $e($mov["tipo"]) ?></span></td>
                            <td><?= number_format((float) $mov["cantidad"], 0) ?></td>
                            <td><?= number_format((float) $mov["stock_anterior"], 0) ?></td>
                            <td><?= number_format((float) $mov["stock_nuevo"], 0) ?></td>
                            <td><?= $e($mov["usuario"]) ?></td>
                            <td><?= $e($mov["motivo"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . "/_pagination.php"; ?>
    </section>
<?php endif; ?>
