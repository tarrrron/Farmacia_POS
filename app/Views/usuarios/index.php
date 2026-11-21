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

    <input class="table-filter" type="search" data-filter-target="#usuarios-table-1" placeholder="Buscar usuarios..." aria-label="Buscar usuarios">
    <div class="table-wrap">
        <table class="data-table" id="usuarios-table-1">
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
                            <a class="btn btn-action-edit btn-sm" data-modal-edit href="index.php?route=module/usuarios/editar&amp;id=<?= (int) $user["idusuario"] ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m14 5 5 5M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16z"/></svg><span>Editar</span></a>
                            <form action="index.php?route=module/usuarios/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $user["idusuario"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $user["estado"] === 1 ? 0 : 1 ?>">
                                <?php if ((int) $user["estado"] === 1): ?>
                                    <button class="btn btn-action-disable btn-sm" type="submit" name="accion" value="desactivar" data-confirm="El usuario quedará inactivo temporalmente. ¿Continuar?"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 5v14M16 5v14"/></svg><span>Desactivar</span></button>
                                    <button class="btn btn-action-delete btn-sm" type="submit" name="accion" value="eliminar" data-confirm="El usuario quedará inactivo y conservará sus registros. ¿Continuar?"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg><span>Eliminar</span></button>
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
