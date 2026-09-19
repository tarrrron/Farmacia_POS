<div class="page-title">
    <h1><?= $editing ? "Editar perfil" : "Crear perfil" ?></h1>
    <p><?= $editing ? "Actualiza el nombre del perfil." : "El nuevo perfil se creará con estado activo." ?></p>
</div>

<section class="panel">
    <?php if ($error !== ""): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <form class="form-stack" method="post" action="index.php?route=module/perfiles/<?= $editing ? 'editar&amp;id=' . (int) $profile['idperfil'] : 'crear' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
        <div class="form-field">
            <label for="nombre">Nombre del perfil</label>
            <input id="nombre" name="nombre" type="text" maxlength="50" required value="<?= htmlspecialchars($profile["nombre"], ENT_QUOTES, "UTF-8") ?>">
        </div>

        <fieldset class="access-grid">
            <legend>Accesos del perfil</legend>
            <?php foreach ($accessOptions as $option): ?>
                <label class="check-card">
                    <input type="checkbox" name="opciones[]" value="<?= (int) $option["idopcion"] ?>" <?= (int) $option["permitido"] === 1 ? "checked" : "" ?>>
                    <span>
                        <strong><?= htmlspecialchars($option["descripcion"], ENT_QUOTES, "UTF-8") ?></strong>
                        <small><?= htmlspecialchars($option["url"], ENT_QUOTES, "UTF-8") ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $editing ? "Guardar cambios" : "Crear perfil" ?></button>
            <a class="btn btn-light" href="index.php?route=module/perfiles">Cancelar</a>
        </div>
    </form>
</section>
