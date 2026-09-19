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

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th scope="col">Nombre</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (!$profiles): ?>
                    <tr><td class="empty-state" colspan="3">No hay perfiles registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td><?= htmlspecialchars($profile["nombre"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><span class="status-badge <?= (int) $profile["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $profile["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-light btn-sm" href="index.php?route=module/perfiles/editar&amp;id=<?= (int) $profile["idperfil"] ?>">Editar</a>
                            <form action="index.php?route=module/perfiles/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $profile["idperfil"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $profile["estado"] === 1 ? 0 : 1 ?>">
                                <button class="btn btn-light btn-sm" type="submit"><?= (int) $profile["estado"] === 1 ? "Desactivar" : "Activar" ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
