<div class="page-title">
    <h1>Gestion de categorias</h1>
    <p>Administra las familias de productos de la farmacia.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Categorias registradas</h2>
            <p>Organiza los productos para busqueda, inventario y ventas.</p>
        </div>
        <a class="btn btn-primary" href="index.php?route=module/categorias/crear">Registrar categoria</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$categories): ?>
                    <tr><td class="empty-state" colspan="3">No hay categorias registradas.</td></tr>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= htmlspecialchars($category["nombre"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><span class="status-badge <?= (int) $category["estado"] === 1 ? "is-active" : "is-muted" ?>"><?= (int) $category["estado"] === 1 ? "Activo" : "Inactivo" ?></span></td>
                        <td class="action-cell">
                            <a class="btn btn-light btn-sm" href="index.php?route=module/categorias/editar&amp;id=<?= (int) $category["idcategoria"] ?>">Editar</a>
                            <form action="index.php?route=module/categorias/estado" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                                <input type="hidden" name="id" value="<?= (int) $category["idcategoria"] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $category["estado"] === 1 ? 0 : 1 ?>">
                                <button class="btn btn-light btn-sm" type="submit"><?= (int) $category["estado"] === 1 ? "Desactivar" : "Activar" ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
