<div class="page-title">
    <h1><?= $editing ? "Editar usuario" : "Registrar usuario" ?></h1>
    <p><?= $editing ? "Actualiza los datos del usuario. Deja la contraseña vacía para conservar la actual." : "El nuevo usuario se creará con estado activo." ?></p>
</div>

<section class="panel">
    <?php if ($errors): ?>
        <div class="form-message" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (!$profiles): ?>
        <p role="status">No hay perfiles activos disponibles. Activa o crea un perfil antes de guardar usuarios.</p>
    <?php endif; ?>
    <form method="post" action="index.php?route=module/usuarios/<?= $editing ? 'actualizar' : 'guardar' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $user["idusuario"] ?>">
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" type="text" maxlength="100" required value="<?= htmlspecialchars($user["nombre"], ENT_QUOTES, "UTF-8") ?>">
        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" type="text" maxlength="50" autocomplete="off" required value="<?= htmlspecialchars($user["usuario"], ENT_QUOTES, "UTF-8") ?>">
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
        <label for="idperfil">Perfil</label>
        <select id="idperfil" name="idperfil" required>
            <option value="">Selecciona un perfil</option>
            <?php foreach ($profiles as $profile): ?>
                <option value="<?= (int) $profile["idperfil"] ?>" <?= (string) $profile["idperfil"] === (string) $user["idperfil"] ? "selected" : "" ?>><?= htmlspecialchars($profile["nombre"], ENT_QUOTES, "UTF-8") ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit" <?= !$profiles ? "disabled" : "" ?>><?= $editing ? "Guardar cambios" : "Guardar usuario" ?></button>
        <a class="btn btn-light" href="index.php?route=module/usuarios">Cancelar</a>
    </form>
</section>
