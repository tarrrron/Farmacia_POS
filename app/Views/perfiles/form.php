<div class="page-title">
    <h1><?= $editing ? "Editar perfil" : "Crear perfil" ?></h1>
    <p><?= $editing ? "Actualiza el nombre del perfil." : "El nuevo perfil se creará con estado activo." ?></p>
</div>

<section class="panel">
    <?php if ($error !== ""): ?>
        <div class="form-message" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <form method="post" action="index.php?route=module/perfiles/<?= $editing ? 'editar&amp;id=' . (int) $profile['idperfil'] : 'crear' ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
        <label for="nombre">Nombre del perfil</label>
        <input id="nombre" name="nombre" type="text" maxlength="50" required value="<?= htmlspecialchars($profile["nombre"], ENT_QUOTES, "UTF-8") ?>">
        <button class="btn btn-primary" type="submit"><?= $editing ? "Guardar cambios" : "Crear perfil" ?></button>
        <a class="btn btn-light" href="index.php?route=module/perfiles">Cancelar</a>
    </form>
</section>
