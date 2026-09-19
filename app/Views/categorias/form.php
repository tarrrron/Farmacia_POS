<div class="page-title">
    <h1><?= $editing ? "Editar categoria" : "Registrar categoria" ?></h1>
    <p><?= $editing ? "Actualiza el nombre de la categoria." : "Crea una categoria activa para clasificar productos." ?></p>
</div>

<section class="panel">
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="form-stack" method="post" action="index.php?route=module/categorias/<?= $editing ? 'actualizar' : 'guardar' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $category["idcategoria"] ?>">
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">

        <div class="form-field">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" type="text" maxlength="100" required value="<?= htmlspecialchars($category["nombre"], ENT_QUOTES, "UTF-8") ?>">
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $editing ? "Guardar cambios" : "Guardar categoria" ?></button>
            <a class="btn btn-light" href="index.php?route=module/categorias">Cancelar</a>
        </div>
    </form>
</section>
