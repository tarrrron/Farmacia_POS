<div class="page-title">
    <h1><?= $editing ? "Editar cliente" : "Registrar cliente" ?></h1>
    <p><?= $editing ? "Actualiza datos de documento y ubicacion." : "Registra un cliente activo para ventas." ?></p>
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

    <?php if (!$documentTypes): ?>
        <div class="alert alert-error" role="status">No hay tipos de documento activos para registrar clientes.</div>
    <?php endif; ?>

    <form class="form-stack" method="post" action="index.php?route=module/clientes/<?= $editing ? 'actualizar' : 'guardar' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $client["idcliente"] ?>">
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">

        <div class="form-grid">
            <div class="form-field">
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" type="text" maxlength="150" required value="<?= htmlspecialchars($client["nombre"], ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="idtipodocumento">Tipo de documento</label>
                <select id="idtipodocumento" name="idtipodocumento" required>
                    <option value="">Selecciona tipo</option>
                    <?php foreach ($documentTypes as $documentType): ?>
                        <option value="<?= (int) $documentType["idtipodocumento"] ?>" <?= (string) $documentType["idtipodocumento"] === (string) $client["idtipodocumento"] ? "selected" : "" ?>><?= htmlspecialchars($documentType["nombre"], ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="nrodocumento">Numero de documento</label>
                <input id="nrodocumento" name="nrodocumento" type="text" maxlength="20" required value="<?= htmlspecialchars($client["nrodocumento"], ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="razon_social">Razon social</label>
                <input id="razon_social" name="razon_social" type="text" maxlength="150" value="<?= htmlspecialchars($client["razon_social"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="nombre_comercial">Nombre comercial</label>
                <input id="nombre_comercial" name="nombre_comercial" type="text" maxlength="150" value="<?= htmlspecialchars($client["nombre_comercial"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="direccion">Direccion</label>
                <input id="direccion" name="direccion" type="text" maxlength="200" value="<?= htmlspecialchars($client["direccion"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="departamento">Departamento</label>
                <input id="departamento" name="departamento" type="text" maxlength="80" value="<?= htmlspecialchars($client["departamento"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="provincia">Provincia</label>
                <input id="provincia" name="provincia" type="text" maxlength="80" value="<?= htmlspecialchars($client["provincia"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="form-field">
                <label for="distrito">Distrito</label>
                <input id="distrito" name="distrito" type="text" maxlength="80" value="<?= htmlspecialchars($client["distrito"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit" <?= !$documentTypes ? "disabled" : "" ?>><?= $editing ? "Guardar cambios" : "Guardar cliente" ?></button>
            <a class="btn btn-light" href="index.php?route=module/clientes">Cancelar</a>
        </div>
    </form>
</section>
