<?php
if ((int) $credit["estado"] === 2) {
    $badge = ["is-active", "Pagado"];
} elseif ((int) $credit["estado"] === 0) {
    $badge = ["is-muted", "Anulado"];
} elseif ((int) $credit["vencido"] === 1) {
    $badge = ["is-danger", "Vencido"];
} else {
    $badge = ["is-warning", "Pendiente"];
}
$invoice = ($credit["serie"] ?? "") . "-" . str_pad((string) ($credit["correlativo"] ?? ""), 8, "0", STR_PAD_LEFT);
$paid = (float) $credit["monto_total"] - (float) $credit["saldo"];
?>
<div class="page-title">
    <h1>Detalle de crédito</h1>
    <p>Venta <?= htmlspecialchars($invoice, ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars($credit["cliente"], ENT_QUOTES, "UTF-8") ?></p>
</div>

<?php if ($message !== ""): ?><div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<?php if ($error !== ""): ?><div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>

<section class="summary-grid dashboard-summary">
    <article class="summary-card card-blue"><span>Monto del crédito</span><strong>S/ <?= number_format((float) $credit["monto_total"], 2) ?></strong><small>Total de la venta</small></article>
    <article class="summary-card card-green"><span>Pagado</span><strong>S/ <?= number_format($paid, 2) ?></strong><small><?= count($credit["pagos"]) ?> pago(s) registrado(s)</small></article>
    <article class="summary-card card-orange"><span>Saldo pendiente</span><strong>S/ <?= number_format((float) $credit["saldo"], 2) ?></strong><small>Vence: <?= $credit["fecha_vencimiento"] ? htmlspecialchars(date("d/m/Y", strtotime($credit["fecha_vencimiento"])), ENT_QUOTES, "UTF-8") : "Sin fecha" ?></small></article>
    <article class="summary-card card-violet"><span>Estado</span><strong><span class="status-badge <?= $badge[0] ?>"><?= $badge[1] ?></span></strong><small><?= htmlspecialchars($credit["cliente"] . " · " . $credit["nrodocumento"], ENT_QUOTES, "UTF-8") ?></small></article>
</section>

<?php if ((int) $credit["estado"] === 1): ?>
<section class="panel">
    <div class="section-toolbar"><div><h2>Registrar pago</h2><p>El pago no puede superar el saldo pendiente.</p></div></div>
    <form class="form-stack" method="post" action="index.php?route=module/creditos/pagar">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
        <input type="hidden" name="id" value="<?= (int) $credit["idcredito"] ?>">
        <div class="form-field">
            <label for="monto">Monto (S/)</label>
            <input id="monto" name="monto" type="number" step="0.01" min="0.01" max="<?= htmlspecialchars((string) $credit["saldo"], ENT_QUOTES, "UTF-8") ?>" required>
        </div>
        <div class="form-field">
            <label for="observacion">Observación (opcional)</label>
            <input id="observacion" name="observacion" type="text" maxlength="150" placeholder="Ej. pago en efectivo">
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Registrar pago</button>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="section-toolbar">
        <div><h2>Historial de pagos</h2><p>Pagos registrados para este crédito.</p></div>
        <a class="btn btn-light" href="index.php?route=module/creditos">Volver a créditos</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Fecha</th><th scope="col">Monto</th><th scope="col">Observación</th><th scope="col">Registrado por</th></tr></thead>
            <tbody>
                <?php if (!$credit["pagos"]): ?>
                    <tr><td class="empty-state" colspan="4">Aún no hay pagos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($credit["pagos"] as $payment): ?>
                    <tr>
                        <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($payment["fecha"])), ENT_QUOTES, "UTF-8") ?></td>
                        <td><strong>S/ <?= number_format((float) $payment["monto"], 2) ?></strong></td>
                        <td><?= htmlspecialchars($payment["observacion"] ?? "—", ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($payment["usuario"], ENT_QUOTES, "UTF-8") ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
