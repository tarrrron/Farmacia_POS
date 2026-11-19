<div class="page-title"><h1>Nueva venta</h1><p>Agrega productos al detalle y confirma el cobro.</p></div>
<?php if ($error !== ""): ?><div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<form class="sale-layout" action="index.php?route=module/ventas/guardar" method="post" id="sale-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
    <section class="panel sale-main">
        <div class="section-toolbar"><div><h2>Productos</h2><p>Busca por nombre o escanea el código de barras y presiona Enter.</p></div><span class="sale-count" id="sale-count">0 productos</span></div>
        <label class="sale-search-label" for="product-search">Buscar producto</label>
        <input id="product-search" type="search" placeholder="Escribe un nombre o código..." autocomplete="off" autofocus>
        <div class="sale-product-results" id="product-results" role="listbox" aria-label="Productos disponibles"></div>
        <div class="table-wrap"><table class="data-table sale-detail-table"><thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Cantidad</th><th>Importe</th><th></th></tr></thead><tbody id="sale-lines"><tr><td class="empty-state" colspan="6">Busca y agrega productos a la venta.</td></tr></tbody></table></div>
    </section>
    <aside class="panel sale-checkout">
        <h2>Datos de venta</h2>
        <label for="sale-client">Cliente</label>
        <select name="idcliente" id="sale-client">
            <option value="" data-deuda="0" data-vencida="0">Cliente general</option>
            <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client["idcliente"] ?>" data-deuda="<?= htmlspecialchars((string)$client["deuda"], ENT_QUOTES, "UTF-8") ?>" data-vencida="<?= htmlspecialchars((string)$client["deuda_vencida"], ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($client["nombre"] . " · " . $client["nrodocumento"], ENT_QUOTES, "UTF-8") ?></option>
            <?php endforeach; ?>
        </select>
        <p class="client-debt" id="client-debt" hidden></p>
        <label for="sale-document">Comprobante</label><select name="idtipocomprobante" id="sale-document" required><?php foreach ($documentTypes as $type): ?><option value="<?= (int)$type["idtipocomprobante"] ?>"><?= htmlspecialchars($type["nombre"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select>
        <label for="sale-payment">Forma de pago</label><select name="formapago" id="sale-payment"><option value="C">Contado</option><option value="R">Crédito</option></select>
        <div id="sale-credit-fields" hidden>
            <label for="sale-due-date">Vencimiento del crédito</label>
            <div class="quick-days" role="group" aria-label="Plazos rápidos">
                <button class="btn btn-light btn-sm" type="button" data-days="15">15 días</button>
                <button class="btn btn-light btn-sm" type="button" data-days="30">30 días</button>
                <button class="btn btn-light btn-sm" type="button" data-days="60">60 días</button>
            </div>
            <input type="date" name="vencimiento" id="sale-due-date" min="<?= date("Y-m-d") ?>" value="<?= date("Y-m-d", strtotime("+30 days")) ?>">
            <small>Las ventas a crédito requieren un cliente registrado y sin deudas vencidas.</small>
        </div>
        <div id="sale-cash-fields">
            <label for="sale-received">Monto recibido (opcional)</label>
            <input type="number" id="sale-received" min="0" step="0.10" inputmode="decimal" placeholder="0.00">
            <p class="sale-change" id="sale-change" hidden></p>
        </div>
        <div class="sale-total"><span>Total a pagar</span><strong id="sale-total">S/ 0.00</strong><small>Precios incluyen impuestos según afectación del producto.</small></div>
        <button class="btn btn-primary sale-submit" type="submit" id="sale-submit" disabled>Registrar venta</button>
        <a class="btn btn-light sale-cancel" href="index.php?route=module/ventas">Cancelar</a>
    </aside>
</form>
<script type="application/json" id="sale-products"><?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
