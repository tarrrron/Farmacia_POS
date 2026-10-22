
<?php
$e = static fn($value) =>
    htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");

$old = $old ?? [];
$errors = $errors ?? [];
?>

<div class="page-title">
    <h1>Registrar movimiento</h1>
    <p>Registra una entrada o salida del inventario.</p>
</div>

<section class="panel">

    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= $e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$products): ?>
        <div class="alert alert-error">
            No existen productos activos disponibles.
        </div>
    <?php endif; ?>

    <form
        class="form-stack"
        method="post"
        action="index.php?route=module/inventario/guardar"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= $e($csrfToken) ?>"
        >

        <div class="form-grid">

            <div class="form-field">
                <label for="idproducto">Producto</label>

                <select
                    id="idproducto"
                    name="idproducto"
                    required
                >
                    <option value="">Seleccione un producto</option>

                    <?php foreach ($products as $product): ?>
                        <option
                            value="<?= (int) $product["idproducto"] ?>"
                            <?= (string) ($old["idproducto"] ?? "") ===
                                (string) $product["idproducto"]
                                ? "selected" : "" ?>
                        >
                            <?= $e($product["nombre"]) ?>
                            (Stock: <?= number_format(
                                (float) $product["stock"], 2
                            ) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="tipo">Tipo de movimiento</label>

                <select id="tipo" name="tipo" required>
                    <option value="">Seleccione el tipo</option>

                    <option
                        value="ENTRADA"
                        <?= ($old["tipo"] ?? "") === "ENTRADA"
                            ? "selected" : "" ?>
                    >
                        Entrada de mercaderia
                    </option>

                    <option
                        value="SALIDA"
                        <?= ($old["tipo"] ?? "") === "SALIDA"
                            ? "selected" : "" ?>
                    >
                        Salida de mercaderia
                    </option>
                </select>
            </div>

            <div class="form-field">
                <label for="cantidad">Cantidad</label>

                <input
                    type="number"
                    id="cantidad"
                    name="cantidad"
                    min="0.01"
                    max="999999999.99"
                    step="0.01"
                    required
                    value="<?= $e($old["cantidad"] ?? "") ?>"
                >
            </div>

            <div class="form-field">
                <label for="motivo">Motivo del movimiento</label>

                <input
                    type="text"
                    id="motivo"
                    name="motivo"
                    maxlength="255"
                    placeholder="Ej: Compra de mercaderia"
                    required
                    value="<?= $e($old["motivo"] ?? "") ?>"
                >
            </div>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                class="btn btn-primary"
                <?= !$products ? "disabled" : "" ?>
            >
                Guardar movimiento
            </button>

            <a
                href="index.php?route=module/inventario"
                class="btn btn-light"
            >
                Cancelar
            </a>

        </div>

    </form>
</section>
