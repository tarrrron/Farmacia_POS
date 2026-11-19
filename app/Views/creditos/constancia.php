<?php
require __DIR__ . "/_helpers.php";
$credit = $payment["credito"];
?>
<div class="page-title no-print">
    <h1>Constancia de pago</h1>
    <p>Imprime o guarda en PDF la constancia para entregarla al cliente.</p>
</div>

<section class="panel payment-receipt">
    <div class="receipt-header">
        <img src="public/img/farmacia-logo.png" alt="" width="56" height="56">
        <div>
            <h2>Farmacia Full Heart</h2>
            <p>Constancia de pago de crédito N.° <?= str_pad((string) (int) $payment["idpago"], 6, "0", STR_PAD_LEFT) ?></p>
        </div>
    </div>
    <?php if ((int) $payment["estado"] !== 1): ?><div class="alert alert-error" role="alert">Este pago fue anulado: <?= $e($payment["motivo_anulacion"]) ?></div><?php endif; ?>
    <dl class="receipt-data">
        <dt>Cliente</dt><dd><?= $e($credit["cliente"] . " · " . $credit["nrodocumento"]) ?></dd>
        <dt>Venta</dt><dd><?= $e($creditInvoice($credit)) ?></dd>
        <dt>Fecha de pago</dt><dd><?= $e(date("d/m/Y H:i", strtotime($payment["fecha"]))) ?></dd>
        <dt>Método</dt><dd><?= $e($paymentMethods[$payment["metodo_pago"]] ?? $payment["metodo_pago"]) ?><?= $payment["nro_operacion"] ? " · Op. " . $e($payment["nro_operacion"]) : "" ?></dd>
        <dt>Monto pagado</dt><dd><strong>S/ <?= number_format((float) $payment["monto"], 2) ?></strong></dd>
        <dt>Monto del crédito</dt><dd>S/ <?= number_format((float) $credit["monto_total"], 2) ?></dd>
        <dt>Saldo actual</dt><dd><strong>S/ <?= number_format((float) $credit["saldo"], 2) ?></strong></dd>
        <dt>Atendido por</dt><dd><?= $e($payment["usuario"]) ?></dd>
    </dl>
    <div class="form-actions no-print">
        <button class="btn btn-primary" type="button" data-print>Imprimir</button>
        <a class="btn btn-light" href="index.php?route=module/creditos/ver&amp;id=<?= (int) $credit["idcredito"] ?>">Volver al crédito</a>
    </div>
</section>
