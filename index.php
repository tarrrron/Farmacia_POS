<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Full Heart - Sistema POS</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body>
    <?php require __DIR__ . '/app/Views/components/header.php'; ?>

    <div class="layout">
        <?php require __DIR__ . '/app/Views/components/sidebar.php'; ?>

        <div class="contenido">
            <main>
                <?php require __DIR__ . '/app/Views/dashboard.php'; ?>
            </main>
            <?php require __DIR__ . '/app/Views/components/footer.php'; ?>
        </div>
    </div>
</body>
</html>
