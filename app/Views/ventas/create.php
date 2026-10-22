<div class="page-title"><h1>Nueva venta</h1><p>Agrega productos al detalle y confirma el cobro.</p></div>
<?php if ($error !== ""): ?><div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<form class="sale-layout" action="index.php?route=module/ventas/guardar" method="post" id="sale-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
    <section class="panel sale-main">
        <div class="section-toolbar"><div><h2>Productos</h2><p>Busca por nombre o codigo de barras.</p></div><span class="sale-count" id="sale-count">0 productos</span></div>
        <label class="sale-search-label" for="product-search">Buscar producto</label>
        <input id="product-search" type="search" placeholder="Escribe un nombre o codigo..." autocomplete="off">
        <div class="sale-product-results" id="product-results" role="listbox" aria-label="Productos disponibles"></div>
        <div class="table-wrap"><table class="data-table sale-detail-table"><thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Cantidad</th><th>Importe</th><th></th></tr></thead><tbody id="sale-lines"><tr><td class="empty-state" colspan="6">Busca y agrega productos a la venta.</td></tr></tbody></table></div>
    </section>
    <aside class="panel sale-checkout">
        <h2>Datos de venta</h2>
        <label for="sale-client">Cliente</label><select name="idcliente" id="sale-client"><option value="">Cliente general</option><?php foreach ($clients as $client): ?><option value="<?= (int)$client["idcliente"] ?>"><?= htmlspecialchars($client["nombre"] . " · " . $client["nrodocumento"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select>
        <label for="sale-document">Comprobante</label><select name="idtipocomprobante" id="sale-document" required><?php foreach ($documentTypes as $type): ?><option value="<?= (int)$type["idtipocomprobante"] ?>"><?= htmlspecialchars($type["nombre"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select>
        <label for="sale-payment">Forma de pago</label><select name="formapago" id="sale-payment"><option value="C">Contado</option><option value="R">Credito</option></select>
        <div class="sale-total"><span>Total a pagar</span><strong id="sale-total">S/ 0.00</strong><small>Precios incluyen impuestos segun afectacion del producto.</small></div>
        <button class="btn btn-primary sale-submit" type="submit" id="sale-submit" disabled>Registrar venta</button>
        <a class="btn btn-light sale-cancel" href="index.php?route=module/ventas">Cancelar</a>
    </aside>
</form>
<script type="application/json" id="sale-products"><?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
