<div class="page-title">
    <h1>Gestion de productos</h1>
    <p>Administra precios, stock y clasificacion de productos.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Productos registrados</h2>
            <p>Controla informacion comercial e inventario minimo.</p>
        </div>
        <a class="btn btn-primary" href="index.php?route=module/productos/crear">Registrar producto</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table product-table">
            <thead>
                <tr>
                    <th scope="col">Imagen</th>
                    <th scope="col">Producto</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Stock</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$products): ?>
                    <tr><td class="empty-state" colspan="7">No hay productos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if (!empty($product["urlimagen"])): ?>
                                <img class="product-table-image" src="<?= htmlspecialchars($product["urlimagen"], ENT_QUOTES, "UTF-8") ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="product-table-image-placeholder" aria-label="Sin imagen">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($product["nombre"], ENT_QUOTES, "UTF-8") ?></strong>
                            <small><?= htmlspecialchars($product["codigobarra"] ?: "Sin codigo", ENT_QUOTES, "UTF-8") ?></small>
                        </td>
                        <td><?= htmlspecialchars($product["categoria"], ENT_QUOTES, "UTF-8") ?></td>
                        <td>S/ <?= number_format((float) $product["pventa"], 2) ?></td>
                        <td>
                            <?= number_format((float) $product["stock"], 2) ?>
                            <small>Min. <?= number_format((float) $product["stockseguridad"], 2) ?></small>
                        </td>
                        <td><span class="status-badge <?= (int) $product["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $product["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-action-edit btn-sm" href="index.php?route=module/productos/editar&amp;id=<?= (int) $product["idproducto"] ?>">Editar</a>
                            <form action="index.php?route=module/productos/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $product["idproducto"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $product["estado"] === 1 ? 0 : 1 ?>">
                                <?php if ((int) $product["estado"] === 1): ?>
                                    <button class="btn btn-action-disable btn-sm" type="submit" name="accion" value="desactivar" onclick="return confirm('El producto quedará inactivo temporalmente. ¿Continuar?')">Desactivar</button>
                                    <button class="btn btn-action-delete btn-sm" type="submit" name="accion" value="eliminar" onclick="return confirm('El producto quedará inactivo y conservará su historial. ¿Continuar?')">Eliminar</button>
                                <?php else: ?>
                                    <button class="btn btn-action-enable btn-sm" type="submit" name="accion" value="activar">Activar</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
