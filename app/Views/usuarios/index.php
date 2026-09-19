<div class="page-title">
    <h1>Gestión de usuarios</h1>
    <p>Consulta los usuarios del sistema y registra nuevos usuarios.</p>
</div>

<section class="panel">
    <a class="btn btn-light" href="index.php?route=module/usuarios/crear">Registrar usuario</a>
    <?php if ($message !== ""): ?>
        <p role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>
    <div style="overflow-x: auto; margin-top: 16px;">
        <table style="width: 100%; text-align: left; border-spacing: 0 12px;">
            <caption>Usuarios registrados</caption>
            <thead>
                <tr><th scope="col">Nombre</th><th scope="col">Usuario</th><th scope="col">Perfil</th><th scope="col">Estado</th></tr>
            </thead>
            <tbody>
                <?php if (!$users): ?>
                    <tr><td colspan="4">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user["nombre"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($user["usuario"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($user["perfil"] ?? "Sin perfil", ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= (int) $user["estado"] === 1 ? "Activo" : "Inactivo" ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
