<?php require __DIR__ . "/_helpers.php"; ?>
<div class="page-title">
    <h1>Estado de cuenta</h1>
    <p><?= $e($account["nombre"]) ?> · <?= $e($account["tipo_documento"] . " " . $account["nrodocumento"]) ?></p>
</div>

<section class="summary-grid dashboard-summary">
    <article class="summary-card card-orange"><span>Deuda pendiente</span><strong>S/ <?= number_format($account["deuda"], 2) ?></strong><small>Saldo de créditos pendientes</small></article>
    <article class="summary-card card-violet"><span>Deuda vencida</span><strong>S/ <?= number_format($account["vencido"], 2) ?></strong><small><?= $account["vencido"] > 0 ? "No puede recibir nuevos créditos" : "Sin deudas vencidas" ?></small></article>
    <article class="summary-card card-green"><span>Total pagado</span><strong>S/ <?= number_format($account["pagado"], 2) ?></strong><small>Pagos válidos registrados</small></article>
    <article class="summary-card card-blue"><span>Créditos</span><strong><?= count($account["creditos"]) ?></strong><small>Registrados para el cliente</small></article>
</section>

<section class="panel">
    <div class="section-toolbar">
        <div><h2>Créditos del cliente</h2><p>Del más reciente al más antiguo, primero los pendientes.</p></div>
        <div class="toolbar-actions no-print">
            <button class="btn btn-primary" type="button" data-print>Imprimir</button>
            <a class="btn btn-light" href="index.php?route=module/creditos">Volver a créditos</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Venta</th><th scope="col">Fecha</th><th scope="col">Vencimiento</th><th scope="col">Cuotas</th><th scope="col">Monto</th><th scope="col">Saldo</th><th scope="col">Estado</th><th scope="col" class="no-print">Acciones</th></tr></thead>
            <tbody>
                <?php if (!$account["creditos"]): ?>
                    <tr><td class="empty-state" colspan="8">El cliente no tiene créditos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($account["creditos"] as $credit): ?>
                    <?php $badge = $creditBadge($credit); ?>
                    <tr>
                        <td><?= $e($creditInvoice($credit)) ?></td>
                        <td><?= $e(date("d/m/Y", strtotime($credit["fecha"]))) ?></td>
                        <td><?= $credit["fecha_vencimiento"] ? $e(date("d/m/Y", strtotime($credit["fecha_vencimiento"]))) : "Sin fecha" ?><small><?= $e($creditDays($credit)) ?></small></td>
                        <td><?= (int) $credit["numero_cuotas"] ?></td>
                        <td>S/ <?= number_format((float) $credit["monto_total"], 2) ?></td>
                        <td><strong>S/ <?= number_format((float) $credit["saldo"], 2) ?></strong></td>
                        <td><span class="status-badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
                        <td class="action-cell no-print"><a class="btn btn-action-edit btn-sm" href="index.php?route=module/creditos/ver&amp;id=<?= (int) $credit["idcredito"] ?>">Detalle</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="section-toolbar"><div><h2>Pagos realizados</h2><p>Todos los pagos del cliente, incluidos los anulados.</p></div></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Fecha</th><th scope="col">Venta</th><th scope="col">Monto</th><th scope="col">Aplicado a</th><th scope="col">Método</th><th scope="col">Estado</th></tr></thead>
            <tbody>
                <?php if (!$account["pagos"]): ?>
                    <tr><td class="empty-state" colspan="6">Aún no hay pagos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($account["pagos"] as $payment): ?>
                    <tr class="<?= (int) $payment["estado"] === 1 ? "" : "is-cancelled" ?>">
                        <td><?= $e(date("d/m/Y H:i", strtotime($payment["fecha"]))) ?></td>
                        <td><?= $e($creditInvoice($payment)) ?></td>
                        <td>S/ <?= number_format((float) $payment["monto"], 2) ?></td>
                        <td><?= $e($payment["cuotas_aplicadas"] ?: "—") ?></td>
                        <td><?= $e($paymentMethods[$payment["metodo_pago"]] ?? $payment["metodo_pago"]) ?></td>
                        <td><span class="status-badge <?= (int) $payment["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $payment["estado"] === 1 ? "Válido" : "Anulado" ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
