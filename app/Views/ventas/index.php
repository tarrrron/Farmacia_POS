<div class="page-title">
    <h1>Ventas</h1>
    <p>Registra operaciones y consulta los comprobantes emitidos.</p>
</div>
<section class="panel">
    <div class="section-toolbar"><div><h2>Ventas recientes</h2><p>Ultimos 100 comprobantes registrados.</p></div><a class="btn btn-primary" href="index.php?route=module/ventas/crear">Nueva venta</a></div>
    <?php if ($message !== ""): ?><div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <div class="table-wrap"><table class="data-table sales-table"><thead><tr><th>Comprobante</th><th>Fecha</th><th>Cliente</th><th>Productos</th><th>Pago</th><th>Total</th><th>Vendedor</th><th></th></tr></thead><tbody>
    <?php if (!$sales): ?><tr><td class="empty-state" colspan="8">Aun no hay ventas registradas.</td></tr><?php endif; ?>
    <?php foreach ($sales as $sale): ?><tr>
        <td><strong><?= htmlspecialchars(($sale["serie"] ?? "") . "-" . str_pad((string)($sale["correlativo"] ?? ""), 8, "0", STR_PAD_LEFT), ENT_QUOTES, "UTF-8") ?></strong><small><?= htmlspecialchars($sale["comprobante"], ENT_QUOTES, "UTF-8") ?></small></td>
        <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($sale["fecha"])), ENT_QUOTES, "UTF-8") ?></td>
        <td><?= htmlspecialchars($sale["cliente"] ?: "Cliente general", ENT_QUOTES, "UTF-8") ?></td>
        <td><?= (int)$sale["productos"] ?></td><td><?= ($sale["formapago"] ?? "C") === "R" ? "Credito" : "Contado" ?></td>
        <td><strong>S/ <?= number_format((float)$sale["total"], 2) ?></strong></td><td><?= htmlspecialchars($sale["vendedor"], ENT_QUOTES, "UTF-8") ?></td>
        <td><a class="btn btn-light btn-sm" href="index.php?route=module/ventas/ver&amp;id=<?= (int)$sale["idventa"] ?>">Ver</a></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
