<?php require __DIR__ . "/_helpers.php"; ?>
<div class="page-title">
    <h1>Créditos</h1>
    <p>Controla las ventas a crédito y el saldo pendiente de cada cliente.</p>
</div>

<section class="summary-grid dashboard-summary">
    <article class="summary-card card-blue"><span>Total por cobrar</span><strong>S/ <?= number_format($summary["por_cobrar"], 2) ?></strong><small>Saldo de créditos pendientes</small></article>
    <article class="summary-card card-green"><span>Créditos pendientes</span><strong><?= number_format($summary["pendientes"]) ?></strong><small>Con saldo por cobrar</small></article>
    <article class="summary-card card-orange"><span>Créditos vencidos</span><strong><?= number_format($summary["vencidos"]) ?></strong><small>S/ <?= number_format($summary["monto_vencido"], 2) ?> vencidos</small></article>
    <article class="summary-card card-violet"><span>Clientes con deuda</span><strong><?= number_format($summary["clientes"]) ?></strong><small>Con al menos un crédito pendiente</small></article>
</section>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Créditos registrados</h2>
            <p>Se generan al registrar una venta con forma de pago crédito.</p>
        </div>
        <form class="period-filter" method="GET" action="index.php">
            <input type="hidden" name="route" value="module/creditos">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" onchange="this.form.submit()">
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= $e($value) ?>" <?= $status === $value ? "selected" : "" ?>><?= $e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($message !== ""): ?><div class="alert alert-success" role="status"><?= $e($message) ?></div><?php endif; ?>
    <?php if ($error !== ""): ?><div class="alert alert-error" role="alert"><?= $e($error) ?></div><?php endif; ?>

    <input class="table-filter" type="search" data-filter-target="#credits-table" placeholder="Buscar por cliente, DNI/RUC o comprobante..." aria-label="Buscar créditos">
    <div class="table-wrap">
        <table class="data-table" id="credits-table">
            <thead>
                <tr>
                    <th scope="col">Cliente</th>
                    <th scope="col">Venta</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">Vencimiento</th>
                    <th scope="col">Monto</th>
                    <th scope="col">Saldo</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$credits): ?>
                    <tr><td class="empty-state" colspan="8">No hay créditos para este filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($credits as $credit): ?>
                    <?php $badge = $creditBadge($credit); ?>
                    <tr>
                        <td>
                            <a class="text-link" href="index.php?route=module/creditos/cliente&amp;id=<?= (int) $credit["idcliente"] ?>" title="Ver estado de cuenta"><strong><?= $e($credit["cliente"]) ?></strong></a>
                            <small><?= $e($credit["nrodocumento"]) ?></small>
                        </td>
                        <td><?= $e($creditInvoice($credit)) ?></td>
                        <td><?= $e(date("d/m/Y", strtotime($credit["fecha"]))) ?></td>
                        <td>
                            <?= $credit["fecha_vencimiento"] ? $e(date("d/m/Y", strtotime($credit["fecha_vencimiento"]))) : "Sin fecha" ?>
                            <small class="<?= (int) $credit["vencido"] === 1 ? "text-danger" : "" ?>"><?= $e($creditDays($credit)) ?></small>
                        </td>
                        <td>S/ <?= number_format((float) $credit["monto_total"], 2) ?></td>
                        <td><strong>S/ <?= number_format((float) $credit["saldo"], 2) ?></strong></td>
                        <td><span class="status-badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-action-edit btn-sm" href="index.php?route=module/creditos/ver&amp;id=<?= (int) $credit["idcredito"] ?>"><?= (int) $credit["estado"] === 1 ? "Pagos" : "Detalle" ?></a>
                            <a class="btn btn-light btn-sm" href="index.php?route=module/ventas/ver&amp;id=<?= (int) $credit["idventa"] ?>">Ver venta</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
