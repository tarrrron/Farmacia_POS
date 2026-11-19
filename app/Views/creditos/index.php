<div class="page-title">
    <h1>Créditos</h1>
    <p>Controla las ventas a crédito y el saldo pendiente de cada cliente.</p>
</div>

<section class="summary-grid dashboard-summary">
    <article class="summary-card card-blue"><span>Total por cobrar</span><strong>S/ <?= number_format($summary["por_cobrar"], 2) ?></strong><small>Saldo de créditos pendientes</small></article>
    <article class="summary-card card-green"><span>Créditos pendientes</span><strong><?= number_format($summary["pendientes"]) ?></strong><small>Con saldo por cobrar</small></article>
    <article class="summary-card card-orange"><span>Créditos vencidos</span><strong><?= number_format($summary["vencidos"]) ?></strong><small>Pasaron su fecha de vencimiento</small></article>
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
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, "UTF-8") ?>" <?= $status === $value ? "selected" : "" ?>><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($message !== ""): ?><div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <?php if ($error !== ""): ?><div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
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
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($credit["cliente"], ENT_QUOTES, "UTF-8") ?></strong>
                            <small><?= htmlspecialchars($credit["nrodocumento"], ENT_QUOTES, "UTF-8") ?></small>
                        </td>
                        <td><?= htmlspecialchars(($credit["serie"] ?? "") . "-" . str_pad((string) ($credit["correlativo"] ?? ""), 8, "0", STR_PAD_LEFT), ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars(date("d/m/Y", strtotime($credit["fecha"])), ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= $credit["fecha_vencimiento"] ? htmlspecialchars(date("d/m/Y", strtotime($credit["fecha_vencimiento"])), ENT_QUOTES, "UTF-8") : "Sin fecha" ?></td>
                        <td>S/ <?= number_format((float) $credit["monto_total"], 2) ?></td>
                        <td><strong>S/ <?= number_format((float) $credit["saldo"], 2) ?></strong></td>
                        <td><span class="status-badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-light btn-sm" href="index.php?route=module/ventas/ver&amp;id=<?= (int) $credit["idventa"] ?>">Ver venta</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
