<div class="page-title">
    <h1>Gestion de clientes</h1>
    <p>Administra los datos de clientes para ventas y comprobantes.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Clientes registrados</h2>
            <p>Consulta documentos, direccion y estado de cada cliente.</p>
        </div>
        <a class="btn btn-primary" href="index.php?route=module/clientes/crear">Registrar cliente</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table client-table">
            <thead>
                <tr>
                    <th scope="col">Cliente</th>
                    <th scope="col">Documento</th>
                    <th scope="col">Direccion</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$clients): ?>
                    <tr><td class="empty-state" colspan="5">No hay clientes registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($client["nombre"], ENT_QUOTES, "UTF-8") ?></strong>
                            <small><?= htmlspecialchars($client["razon_social"] ?: ($client["nombre_comercial"] ?: "Sin razon social"), ENT_QUOTES, "UTF-8") ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($client["tipo_documento"], ENT_QUOTES, "UTF-8") ?>
                            <small><?= htmlspecialchars($client["nrodocumento"], ENT_QUOTES, "UTF-8") ?></small>
                        </td>
                        <td><?= htmlspecialchars($client["direccion"] ?: "Sin direccion", ENT_QUOTES, "UTF-8") ?></td>
                        <td><span class="status-badge <?= (int) $client["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $client["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-light btn-sm" href="index.php?route=module/clientes/editar&amp;id=<?= (int) $client["idcliente"] ?>">Editar</a>
                            <form action="index.php?route=module/clientes/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $client["idcliente"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $client["estado"] === 1 ? 0 : 1 ?>">
                                <button class="btn btn-light btn-sm" type="submit"><?= (int) $client["estado"] === 1 ? "Desactivar" : "Activar" ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
