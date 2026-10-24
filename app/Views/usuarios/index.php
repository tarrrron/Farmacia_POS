<div class="page-title">
    <h1>Gestión de usuarios</h1>
    <p>Consulta los usuarios del sistema y registra nuevos usuarios.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Usuarios registrados</h2>
            <p>Gestiona accesos, perfiles y estado de cada usuario.</p>
        </div>
        <a class="btn btn-primary" href="index.php?route=module/usuarios/crear">Registrar usuario</a>
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
                <tr><th scope="col">Nombre</th><th scope="col">Usuario</th><th scope="col">Perfil</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (!$users): ?>
                    <tr><td class="empty-state" colspan="5">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user["nombre"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($user["usuario"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($user["perfil"] ?? "Sin perfil", ENT_QUOTES, "UTF-8") ?></td>
                        <td><span class="status-badge <?= (int) $user["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $user["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-action-edit btn-sm" href="index.php?route=module/usuarios/editar&amp;id=<?= (int) $user["idusuario"] ?>">Editar</a>
                            <form action="index.php?route=module/usuarios/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $user["idusuario"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $user["estado"] === 1 ? 0 : 1 ?>">
                                <?php if ((int) $user["estado"] === 1): ?>
                                    <button class="btn btn-action-disable btn-sm" type="submit" name="accion" value="desactivar" onclick="return confirm('El usuario quedará inactivo temporalmente. ¿Continuar?')">Desactivar</button>
                                    <button class="btn btn-action-delete btn-sm" type="submit" name="accion" value="eliminar" onclick="return confirm('El usuario quedará inactivo y conservará sus registros. ¿Continuar?')">Eliminar</button>
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
