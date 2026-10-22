
<?php
$e = static fn($value) =>
    htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
?>

<div class="page-title">
    <h1>Gestion de inventario</h1>
    <p>Control de existencias, entradas y salidas de productos.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Resumen de inventario</h2>
            <p>Estado actual de los productos activos.</p>
        </div>

        <a class="btn btn-primary"
           href="index.php?route=module/inventario/crear">
            Registrar movimiento
        </a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success" role="status">
            <?= $e($message) ?>
        </div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="panel">
            <h3>Productos activos</h3>
            <h2><?= (int) $resumen["total_productos"] ?></h2>
        </div>

        <div class="panel">
            <h3>Stock total</h3>
            <h2><?= number_format((float) $resumen["unidades"], 2) ?></h2>
        </div>

        <div class="panel">
            <h3>Stock bajo</h3>
            <h2><?= (int) $resumen["stock_bajo"] ?></h2>
        </div>

        <div class="panel">
            <h3>Agotados</h3>
            <h2><?= (int) $resumen["agotados"] ?></h2>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Existencias de productos</h2>
            <p>Consulta el stock disponible y el minimo.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table product-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoria</th>
                    <th>Stock actual</th>
                    <th>Stock minimo</th>
                    <th>Estado de stock</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$products): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            No hay productos registrados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($products as $product): ?>
                    <?php
                    $stock = (float) $product["stock"];
                    $minimo = (float) $product["stockseguridad"];

                    if ((int) $product["estado"] !== 1) {
                        $estado = "Inactivo";
                        $clase = "is-muted";
                    } elseif ($stock <= 0) {
                        $estado = "Agotado";
                        $clase = "is-danger";
                    } elseif ($stock <= $minimo) {
                        $estado = "Stock bajo";
                        $clase = "is-warning";
                    } else {
                        $estado = "Disponible";
                        $clase = "is-active";
                    }
                    ?>

                    <tr>
                        <td>
                            <strong><?= $e($product["nombre"]) ?></strong>
                            <small>
                                <?= $e($product["codigobarra"] ?: "Sin codigo") ?>
                            </small>
                        </td>

                        <td><?= $e($product["categoria"]) ?></td>

                        <td>
                            <?= number_format($stock, 2) ?>
                            <small><?= $e($product["unidad"]) ?></small>
                        </td>

                        <td><?= number_format($minimo, 2) ?></td>

                        <td>
                            <span class="status-badge <?= $clase ?>">
                                <?= $estado ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Historial de movimientos</h2>
            <p>Ultimos 100 movimientos registrados.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Stock anterior</th>
                    <th>Stock nuevo</th>
                    <th>Usuario</th>
                    <th>Motivo</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$movimientos): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            No existen movimientos registrados.
                        </td>
                    </tr>
                <?php endif; ?>

                
                <?php foreach ($movimientos as $mov): ?>
                    <tr>
                        <td><?= $e($mov["fecha"]) ?></td>
                        <td><?= $e($mov["producto"]) ?></td>
                        <td>
                            <span class="status-badge <?= $mov["tipo"] === "ENTRADA" ? "is-active" : "is-muted" ?>">
                                <?= $e($mov["tipo"]) ?>
                            </span>
                        </td>
                        <td><?= number_format((float) $mov["cantidad"], 2) ?></td>
                        <td><?= number_format((float) $mov["stock_anterior"], 2) ?></td>
                        <td><?= number_format((float) $mov["stock_nuevo"], 2) ?></td>
                        <td><?= $e($mov["usuario"]) ?></td>
                        <td><?= $e($mov["motivo"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
