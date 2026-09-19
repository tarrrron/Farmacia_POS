<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farmacia POS - Login</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body class="login-body">
    <main class="login-page">
        <section class="login-card">
            <div class="brand-mark">+</div>
            <h1>Farmacia POS</h1>
            <p>Ingresa con tu usuario para continuar.</p>

            <form method="post" action="index.php?route=login" autocomplete="off">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="admin" required>

                <label for="clave">Clave</label>
                <input type="password" id="clave" name="clave" placeholder="1234" required>

                <button type="submit" class="btn btn-primary">Ingresar</button>

                <?php if (!empty($error)): ?>
                    <div class="form-message" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </form>
        </section>
    </main>
</body>
</html>
