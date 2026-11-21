<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FULL HEART | Farmacia</title>
    <link rel="stylesheet" href="public/css/styles.css?v=20261120-stock-alert-toasts">
</head>
<body class="app-body">
    <dialog class="confirm-dialog" id="action-confirmation" aria-labelledby="confirmation-title" aria-describedby="confirmation-message">
        <div class="confirm-dialog-icon" aria-hidden="true">!</div>
        <h2 id="confirmation-title">Confirmación</h2>
        <p id="confirmation-message"></p>
        <div class="confirm-dialog-actions">
            <button class="btn btn-primary" type="button" data-confirm-accept>Sí, continuar</button>
            <button class="btn btn-light" type="button" data-confirm-cancel>Cancelar</button>
        </div>
    </dialog>
    <dialog class="edit-modal" id="record-edit-modal" aria-labelledby="record-edit-title">
        <header class="edit-modal-header">
            <h2 id="record-edit-title">Editar registro</h2>
            <button class="edit-modal-close" type="button" aria-label="Cerrar">&times;</button>
        </header>
        <div class="edit-modal-content" aria-live="polite"></div>
    </dialog>
    <?php require __DIR__ . "/header.php"; ?>

    <div class="layout">
        <?php require __DIR__ . "/sidebar.php"; ?>

        <div class="contenido">
            <main>
                <?php require $view; ?>
            </main>
            <?php require __DIR__ . "/footer.php"; ?>
        </div>
    </div>
    <script src="public/js/app.js?v=20261120-stock-alert-toasts"></script>
</body>
</html>
