<div class="page-title">
    <h1>Gestión de perfiles</h1>
    <p>Crea, edita y activa o desactiva los perfiles del sistema.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Perfiles registrados</h2>
            <p>Administra roles y permisos del sistema.</p>
        </div>
        <a class="btn btn-primary" href="index.php?route=module/perfiles/crear">Crear perfil</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th scope="col">Nombre</th><th scope="col">Usuarios activos</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (!$profiles): ?>
                    <tr><td class="empty-state" colspan="4">No hay perfiles registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td><?= htmlspecialchars($profile["nombre"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= (int) $profile["usuarios_activos"] ?></td>
                        <td><span class="status-badge <?= (int) $profile["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $profile["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-action-edit btn-sm" href="index.php?route=module/perfiles/editar&amp;id=<?= (int) $profile["idperfil"] ?>">Editar</a>
                            <form action="index.php?route=module/perfiles/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $profile["idperfil"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $profile["estado"] === 1 ? 0 : 1 ?>">
                                <?php if ((int) $profile["estado"] === 1): ?>
                                    <button class="btn btn-action-disable btn-sm" type="submit" name="accion" value="desactivar" onclick="return confirm('El perfil quedará inactivo temporalmente. ¿Continuar?')" <?= (int) $profile["usuarios_activos"] > 0 ? 'disabled title="Asigna primero sus usuarios activos a otro perfil."' : '' ?>>Desactivar</button>
                                    <button class="btn btn-action-delete btn-sm" type="submit" name="accion" value="eliminar" onclick="return confirm('El perfil quedará inactivo. ¿Continuar?')" <?= (int) $profile["usuarios_activos"] > 0 ? 'disabled title="Asigna primero sus usuarios activos a otro perfil."' : '' ?>>Eliminar</button>
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
