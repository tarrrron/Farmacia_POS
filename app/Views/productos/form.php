<div class="page-title">
    <h1><?= $editing ? "Editar producto" : "Registrar producto" ?></h1>
    <p><?= $editing ? "Actualiza los datos comerciales y de inventario." : "Registra un producto activo para venta e inventario." ?></p>
</div>

<section class="panel">
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$categories || !$units || !$affectations): ?>
        <div class="alert alert-error" role="status">Faltan categorias, unidades o afectaciones activas para registrar productos.</div>
    <?php endif; ?>

    <form class="form-stack" method="post" action="index.php?route=module/productos/<?= $editing ? 'actualizar' : 'guardar' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $product["idproducto"] ?>">
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">

        <div class="form-grid">
            <div class="form-field">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" type="text" maxlength="150" required value="<?= htmlspecialchars($product["nombre"], ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="codigobarra">Codigo de barra</label>
                <input id="codigobarra" name="codigobarra" type="text" maxlength="50" value="<?= htmlspecialchars($product["codigobarra"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="pventa">Precio venta</label>
                <input id="pventa" name="pventa" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string) $product["pventa"], ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="pcompra">Precio compra</label>
                <input id="pcompra" name="pcompra" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string) $product["pcompra"], ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="stock">Stock</label>
                <input id="stock" name="stock" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string) $product["stock"], ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="stockseguridad">Stock minimo</label>
                <input id="stockseguridad" name="stockseguridad" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string) $product["stockseguridad"], ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="idcategoria">Categoria</label>
                <select id="idcategoria" name="idcategoria" required>
                    <option value="">Selecciona categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category["idcategoria"] ?>" <?= (string) $category["idcategoria"] === (string) $product["idcategoria"] ? "selected" : "" ?>><?= htmlspecialchars($category["nombre"], ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="idunidad">Unidad</label>
                <select id="idunidad" name="idunidad" required>
                    <option value="">Selecciona unidad</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= (int) $unit["idunidad"] ?>" <?= (string) $unit["idunidad"] === (string) $product["idunidad"] ? "selected" : "" ?>><?= htmlspecialchars($unit["descripcion"], ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="idafectacion">Afectacion</label>
                <select id="idafectacion" name="idafectacion" required>
                    <option value="">Selecciona afectacion</option>
                    <?php foreach ($affectations as $affectation): ?>
                        <option value="<?= (int) $affectation["idafectacion"] ?>" <?= (string) $affectation["idafectacion"] === (string) $product["idafectacion"] ? "selected" : "" ?>><?= htmlspecialchars($affectation["codigo"] . " - " . $affectation["descripcion"], ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit" <?= (!$categories || !$units || !$affectations) ? "disabled" : "" ?>><?= $editing ? "Guardar cambios" : "Guardar producto" ?></button>
            <a class="btn btn-light" href="index.php?route=module/productos">Cancelar</a>
        </div>
    </form>
</section>
