<div class="page-title">
    <h1>Gestión de perfiles</h1>
    <p>Crea, edita y activa o desactiva los perfiles del sistema.</p>
</div>

<section class="panel">
    <a class="btn btn-light" href="index.php?route=module/perfiles/crear">Crear perfil</a>
    <?php if ($message !== ""): ?>
        <p role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>
    <div style="overflow-x: auto; margin-top: 16px;">
        <table style="width: 100%; text-align: left; border-spacing: 0 12px;">
            <caption>Perfiles registrados</caption>
            <thead>
                <tr><th scope="col">Nombre</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (!$profiles): ?>
                    <tr><td colspan="3">No hay perfiles registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td><?= htmlspecialchars($profile["nombre"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= (int) $profile["estado"] === 1 ? "Activo" : "Inactivo" ?></td>
                        <td>
                            <a class="btn btn-light" href="index.php?route=module/perfiles/editar&amp;id=<?= (int) $profile["idperfil"] ?>">Editar</a>
                            <form action="index.php?route=module/perfiles/estado" method="post" style="display: inline-block;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $profile["idperfil"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $profile["estado"] === 1 ? 0 : 1 ?>">
                                <button class="btn btn-light" type="submit"><?= (int) $profile["estado"] === 1 ? "Desactivar" : "Activar" ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
