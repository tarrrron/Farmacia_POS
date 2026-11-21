<div class="page-title">
    <h1>Gestión de clientes</h1>
    <p>Administra los datos de clientes para ventas y comprobantes.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Clientes registrados</h2>
            <p>Consulta documentos, dirección y estado de cada cliente.</p>
        </div>
        <a class="btn btn-primary" href="index.php?route=module/clientes/crear">Registrar cliente</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <input class="table-filter" type="search" data-filter-target="#clientes-table-1" placeholder="Buscar clientes..." aria-label="Buscar clientes">
    <div class="table-wrap">
        <table class="data-table client-table" id="clientes-table-1">
            <thead>
                <tr>
                    <th scope="col">Cliente</th>
                    <th scope="col">Documento</th>
                    <th scope="col">Dirección</th>
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
                            <small><?= htmlspecialchars($client["razon_social"] ?: ($client["nombre_comercial"] ?: "Sin razón social"), ENT_QUOTES, "UTF-8") ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($client["tipo_documento"], ENT_QUOTES, "UTF-8") ?>
                            <small><?= htmlspecialchars($client["nrodocumento"], ENT_QUOTES, "UTF-8") ?></small>
                        </td>
                        <td><?= htmlspecialchars($client["direccion"] ?: "Sin dirección", ENT_QUOTES, "UTF-8") ?></td>
                        <td><span class="status-badge <?= (int) $client["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $client["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-action-edit btn-sm" data-modal-edit href="index.php?route=module/clientes/editar&amp;id=<?= (int) $client["idcliente"] ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m14 5 5 5M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16z"/></svg><span>Editar</span></a>
                            <form action="index.php?route=module/clientes/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $client["idcliente"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $client["estado"] === 1 ? 0 : 1 ?>">
                                <?php if ((int) $client["estado"] === 1): ?>
                                    <button class="btn btn-action-disable btn-sm" type="submit" name="accion" value="desactivar" data-confirm="El cliente quedará inactivo temporalmente. ¿Continuar?"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 5v14M16 5v14"/></svg><span>Desactivar</span></button>
                                    <button class="btn btn-action-delete btn-sm" type="submit" name="accion" value="eliminar" data-confirm="El cliente quedará inactivo y conservará su historial de ventas. ¿Continuar?"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg><span>Eliminar</span></button>
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
