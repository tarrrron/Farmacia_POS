<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farmacia POS - Inicio</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body class="app-body">
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
    <script src="public/js/app.js"></script>
</body>
</html>
