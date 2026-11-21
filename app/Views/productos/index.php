<div class="page-title">
    <h1>Gestión de productos</h1>
    <p>Administra precios, stock y clasificación de productos.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Productos registrados</h2>
            <p>Controla información comercial e inventario mínimo.</p>
        </div>
        <a class="btn btn-primary" href="index.php?route=module/productos/crear">Registrar producto</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="product-filters" role="search" aria-label="Filtros de productos">
        <input class="table-filter" type="search" data-filter-target="#productos-table-1" placeholder="Buscar por nombre o código..." aria-label="Buscar por nombre o código">
        <select class="table-filter-select" data-filter-target="#productos-table-1" data-filter-category aria-label="Filtrar por categoría">
            <option value="">Todas las categorías</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= htmlspecialchars($category["nombre"], ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($category["nombre"], ENT_QUOTES, "UTF-8") ?></option>
            <?php endforeach; ?>
        </select>
        <select class="table-filter-select" data-filter-target="#productos-table-1" data-filter-status aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option value="active">Activo</option>
            <option value="inactive">Inactivo</option>
        </select>
    </div>
    <div class="table-wrap">
        <table class="data-table product-table" id="productos-table-1">
            <thead>
                <tr>
                    <th scope="col">Imagen</th>
                    <th scope="col">Producto</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Stock</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$products): ?>
                    <tr><td class="empty-state" colspan="6">No hay productos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $product): ?>
                    <tr data-category="<?= htmlspecialchars($product["categoria"], ENT_QUOTES, "UTF-8") ?>" data-status="<?= (int) $product["estado"] === 1 ? "active" : "inactive" ?>">
                        <td>
                            <?php if (!empty($product["urlimagen"])): ?>
                                <img class="product-table-image" src="<?= htmlspecialchars($product["urlimagen"], ENT_QUOTES, "UTF-8") ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="product-table-image-placeholder" aria-label="Sin imagen">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($product["nombre"], ENT_QUOTES, "UTF-8") ?></strong>
                            <small><?= htmlspecialchars($product["codigobarra"] ?: "Sin código", ENT_QUOTES, "UTF-8") ?></small>
                        </td>
                        <td>S/ <?= number_format((float) $product["pventa"], 2) ?></td>
                        <td>
                            <?= number_format((float) $product["stock"], 0) ?>
                            <small>Mín. <?= number_format((float) $product["stockseguridad"], 0) ?></small>
                        </td>
                        <td><span class="status-badge <?= (int) $product["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $product["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-action-edit btn-sm" data-modal-edit href="index.php?route=module/productos/editar&amp;id=<?= (int) $product["idproducto"] ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m14 5 5 5M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16z"/></svg><span>Editar</span></a>
                            <form action="index.php?route=module/productos/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $product["idproducto"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $product["estado"] === 1 ? 0 : 1 ?>">
                                <?php if ((int) $product["estado"] === 1): ?>
                                    <button class="btn btn-action-disable btn-sm" type="submit" name="accion" value="desactivar" data-confirm="El producto quedará inactivo temporalmente. ¿Continuar?"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 5v14M16 5v14"/></svg><span>Desactivar</span></button>
                                    <button class="btn btn-action-delete btn-sm" type="submit" name="accion" value="eliminar" data-confirm="El producto quedará inactivo y conservará su historial. ¿Continuar?"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg><span>Eliminar</span></button>
                                <?php else: ?>
                                    <button class="btn btn-action-enable btn-sm" type="submit" name="accion" value="activar"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg><span>Activar</span></button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
