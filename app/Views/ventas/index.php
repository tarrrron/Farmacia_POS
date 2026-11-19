<?php
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
$from = $_GET["desde"] ?? "";
$to = $_GET["hasta"] ?? "";
$filtered = $from !== "" || $to !== "";
?>
<div class="page-title">
    <h1>Ventas</h1>
    <p>Registra operaciones y consulta los comprobantes emitidos.</p>
</div>
<section class="panel">
    <div class="section-toolbar">
        <div><h2><?= $filtered ? "Ventas del periodo" : "Ventas recientes" ?></h2><p><?= $filtered ? "Comprobantes dentro del rango de fechas seleccionado." : "Últimos 100 comprobantes registrados." ?></p></div>
        <a class="btn btn-primary" href="index.php?route=module/ventas/crear">Nueva venta</a>
    </div>
    <?php if ($message !== ""): ?><div class="alert alert-success" role="status"><?= $e($message) ?></div><?php endif; ?>
    <?php if ($error !== ""): ?><div class="alert alert-error" role="alert"><?= $e($error) ?></div><?php endif; ?>

    <div class="list-tools">
        <form class="date-filter" method="GET" action="index.php">
            <input type="hidden" name="route" value="module/ventas">
            <label for="desde">Desde</label><input type="date" id="desde" name="desde" value="<?= $e($from) ?>">
            <label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="<?= $e($to) ?>">
            <button class="btn btn-light btn-sm" type="submit">Filtrar</button>
            <?php if ($filtered): ?><a class="text-link" href="index.php?route=module/ventas">Quitar filtro</a><?php endif; ?>
        </form>
        <input class="table-filter" type="search" data-filter-target="#sales-table" placeholder="Buscar por comprobante, cliente o vendedor..." aria-label="Buscar ventas">
    </div>
    <?php if ($filtered): ?><p class="list-summary">Total vendido en el periodo (sin anuladas): <strong>S/ <?= number_format($salesTotal, 2) ?></strong></p><?php endif; ?>

    <div class="table-wrap"><table class="data-table sales-table" id="sales-table"><thead><tr><th>Comprobante</th><th>Fecha</th><th>Cliente</th><th>Productos</th><th>Pago</th><th>Total</th><th>Vendedor</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    <?php if (!$sales): ?><tr><td class="empty-state" colspan="9">No hay ventas para mostrar.</td></tr><?php endif; ?>
    <?php foreach ($sales as $sale): ?><tr>
        <td><strong><?= $e(($sale["serie"] ?? "") . "-" . str_pad((string)($sale["correlativo"] ?? ""), 8, "0", STR_PAD_LEFT)) ?></strong><small><?= $e($sale["comprobante"]) ?></small></td>
        <td><?= $e(date("d/m/Y H:i", strtotime($sale["fecha"]))) ?></td>
        <td><?= $e($sale["cliente"] ?: "Cliente general") ?><?php if (!empty($sale["nrodocumento"])): ?><small><?= $e($sale["nrodocumento"]) ?></small><?php endif; ?></td>
        <td><?= (int)$sale["productos"] ?></td><td><?= ($sale["formapago"] ?? "C") === "R" ? "Crédito" : "Contado" ?></td>
        <td><strong>S/ <?= number_format((float)$sale["total"], 2) ?></strong></td><td><?= $e($sale["vendedor"]) ?></td>
        <td><span class="status-badge <?= (int)$sale["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int)$sale["estado"] === 1 ? "Activa" : "Anulada" ?></span></td>
        <td class="action-cell"><a class="btn btn-light btn-sm" href="index.php?route=module/ventas/ver&amp;id=<?= (int)$sale["idventa"] ?>">Ver</a>
            <?php if ((int)$sale["estado"] === 1): ?>
                <form action="index.php?route=module/ventas/anular" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$sale["idventa"] ?>">
                    <button class="btn btn-action-delete btn-sm" type="submit" onclick="return confirm('La venta se anulará: conservará su comprobante y se devolverá el stock de sus productos. ¿Continuar?')">Anular</button>
                </form>
            <?php endif; ?>
        </td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
