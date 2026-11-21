<?php
require __DIR__ . "/_helpers.php";
$badge = $creditBadge($credit);
$paid = (float) $credit["monto_total"] - (float) $credit["saldo"];
$validPayments = array_filter($credit["pagos"], static fn($payment) => (int) $payment["estado"] === 1);
$lastPayment = $_SESSION["credito_last_payment"] ?? null;
unset($_SESSION["credito_last_payment"]);
?>
<div class="page-title">
    <h1>Detalle de crédito</h1>
    <p>Venta <?= $e($creditInvoice($credit)) ?> · <a class="text-link" href="index.php?route=module/creditos/cliente&amp;id=<?= (int) $credit["idcliente"] ?>"><?= $e($credit["cliente"]) ?></a></p>
</div>

<?php if ($message !== ""): ?>
    <div class="alert alert-success" role="status">
        <?= $e($message) ?>
        <?php if ($lastPayment): ?> <a class="text-link" href="index.php?route=module/creditos/constancia&amp;id=<?= (int) $lastPayment ?>">Imprimir constancia</a><?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($error !== ""): ?><div class="alert alert-error" role="alert"><?= $e($error) ?></div><?php endif; ?>

<section class="summary-grid dashboard-summary">
    <article class="summary-card card-blue"><span>Monto del crédito</span><strong>S/ <?= number_format((float) $credit["monto_total"], 2) ?></strong><small>Total de la venta</small></article>
    <article class="summary-card card-green"><span>Pagado</span><strong>S/ <?= number_format($paid, 2) ?></strong><small><?= count($validPayments) ?> pago(s) válido(s)</small></article>
    <article class="summary-card card-orange"><span>Saldo pendiente</span><strong>S/ <?= number_format((float) $credit["saldo"], 2) ?></strong><small>Vence: <?= $credit["fecha_vencimiento"] ? $e(date("d/m/Y", strtotime($credit["fecha_vencimiento"]))) : "Sin fecha" ?> · <?= $e($creditDays($credit)) ?></small></article>
    <article class="summary-card card-violet"><span>Estado</span><strong><span class="status-badge <?= $badge[0] ?>"><?= $badge[1] ?></span></strong><small><?= $e($credit["cliente"] . " · " . $credit["nrodocumento"]) ?></small></article>
</section>

<?php if ((int) $credit["estado"] === 1): ?>
<section class="credit-actions">
    <article class="panel">
        <div class="section-toolbar"><div><h2>Registrar pago</h2><p>El pago no puede superar el saldo pendiente.</p></div></div>
        <form class="form-stack" method="post" action="index.php?route=module/creditos/pagar">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= (int) $credit["idcredito"] ?>">
            <div class="form-field">
                <label for="monto">Monto (S/)</label>
                <div class="input-with-action">
                    <input id="monto" name="monto" type="number" step="0.01" min="0.01" max="<?= $e($credit["saldo"]) ?>" required>
                    <button class="btn btn-light btn-sm" type="button" data-fill-target="#monto" data-fill-value="<?= $e($credit["saldo"]) ?>">Pagar saldo completo</button>
                </div>
            </div>
            <div class="form-field">
                <label for="metodo_pago">Método de pago</label>
                <select id="metodo_pago" name="metodo_pago" required>
                    <?php foreach ($paymentMethods as $value => $label): ?>
                        <option value="<?= $e($value) ?>"><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field" id="operation-field" hidden>
                <label for="nro_operacion">N.° de operación</label>
                <input id="nro_operacion" name="nro_operacion" type="text" maxlength="50" placeholder="Código de la transferencia o del voucher">
            </div>
            <div class="form-field">
                <label for="observacion">Observación (opcional)</label>
                <input id="observacion" name="observacion" type="text" maxlength="150" placeholder="Ej. pago parcial acordado">
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Registrar pago</button>
            </div>
        </form>
    </article>
    <article class="panel">
        <div class="section-toolbar"><div><h2>Prorrogar vencimiento</h2><p>Amplía el plazo de pago dejando registrado el motivo.</p></div></div>
        <form class="form-stack" method="post" action="index.php?route=module/creditos/prorrogar">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= (int) $credit["idcredito"] ?>">
            <div class="form-field">
                <label for="fecha_vencimiento">Nueva fecha de vencimiento</label>
                <input id="fecha_vencimiento" name="fecha_vencimiento" type="date" min="<?= date("Y-m-d") ?>" required>
            </div>
            <div class="form-field">
                <label for="motivo_prorroga">Motivo</label>
                <input id="motivo_prorroga" name="motivo" type="text" maxlength="150" required placeholder="Ej. el cliente solicitó 15 días más">
            </div>
            <div class="form-actions">
                <button class="btn btn-light" type="submit">Prorrogar</button>
            </div>
        </form>
    </article>
</section>
<?php endif; ?>

<section class="panel">
    <div class="section-toolbar">
        <div><h2>Historial de pagos</h2><p>Pagos registrados para este crédito.</p></div>
        <a class="btn btn-light" href="index.php?route=module/creditos">Volver a créditos</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Fecha</th><th scope="col">Monto</th><th scope="col">Método</th><th scope="col">Observación</th><th scope="col">Registrado por</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody>
                <?php if (!$credit["pagos"]): ?>
                    <tr><td class="empty-state" colspan="7">Aún no hay pagos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($credit["pagos"] as $payment): ?>
                    <?php $active = (int) $payment["estado"] === 1; ?>
                    <tr class="<?= $active ? "" : "is-cancelled" ?>">
                        <td><?= $e(date("d/m/Y H:i", strtotime($payment["fecha"]))) ?></td>
                        <td><strong>S/ <?= number_format((float) $payment["monto"], 2) ?></strong></td>
                        <td><?= $e($paymentMethods[$payment["metodo_pago"]] ?? $payment["metodo_pago"]) ?><?php if ($payment["nro_operacion"]): ?><small>Op. <?= $e($payment["nro_operacion"]) ?></small><?php endif; ?></td>
                        <td><?= $e($payment["observacion"] ?? "—") ?><?php if (!$active): ?><small>Anulado: <?= $e($payment["motivo_anulacion"]) ?> (<?= $e($payment["usuario_anula"] ?? "") ?>)</small><?php endif; ?></td>
                        <td><?= $e($payment["usuario"]) ?></td>
                        <td><span class="status-badge <?= $active ? "is-active" : "is-muted" ?>"><?= $active ? "Válido" : "Anulado" ?></span></td>
                        <td class="action-cell">
                            <?php if ($active): ?>
                                <a class="btn btn-light btn-sm" href="index.php?route=module/creditos/constancia&amp;id=<?= (int) $payment["idpago"] ?>">Constancia</a>
                                <?php if ($isAdmin && (int) $credit["estado"] !== 0): ?>
                                    <form action="index.php?route=module/creditos/anular-pago" method="post" data-require-reason="Motivo de la anulación del pago (obligatorio):">
                                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $credit["idcredito"] ?>">
                                        <input type="hidden" name="idpago" value="<?= (int) $payment["idpago"] ?>">
                                        <input type="hidden" name="motivo" value="">
                                        <button class="btn btn-action-delete btn-sm" type="submit"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 8 8 8"/></svg><span>Anular</span></button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($credit["prorrogas"]): ?>
<section class="panel">
    <div class="section-toolbar"><div><h2>Prórrogas</h2><p>Cambios en la fecha de vencimiento.</p></div></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Fecha</th><th scope="col">Vencimiento anterior</th><th scope="col">Nuevo vencimiento</th><th scope="col">Motivo</th><th scope="col">Registrado por</th></tr></thead>
            <tbody>
                <?php foreach ($credit["prorrogas"] as $extension): ?>
                    <tr>
                        <td><?= $e(date("d/m/Y H:i", strtotime($extension["fecha"]))) ?></td>
                        <td><?= $extension["fecha_anterior"] ? $e(date("d/m/Y", strtotime($extension["fecha_anterior"]))) : "Sin fecha" ?></td>
                        <td><?= $e(date("d/m/Y", strtotime($extension["fecha_nueva"]))) ?></td>
                        <td><?= $e($extension["motivo"]) ?></td>
                        <td><?= $e($extension["usuario"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
