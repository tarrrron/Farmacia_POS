<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Farmacia POS</title>
    <link rel="stylesheet" href="public/css/styles.css?v=20261024-login">
</head>
<body class="login-body">
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-title">
            <div class="login-brand-panel">
                <img class="login-logo" src="public/img/farmacia-logo.png" alt="Logo de Farmacia POS">
                <div class="login-brand-copy">
                    <span class="login-eyebrow">GESTIÓN FARMACÉUTICA</span>
                    <h1>Farmacia Full Heart</h1>
                    <p>Todo tu negocio, bajo control.</p>
                </div>
                <span class="login-brand-caption">Punto de venta y Gestión</span>
            </div>
            <div class="login-form-panel">
                <span class="login-eyebrow">BIENVENIDO</span>
                <h2 id="login-title">Inicia sesión</h2>
                <p class="login-intro">Ingresa tus credenciales para continuar.</p>
                <form method="post" action="index.php?route=login" autocomplete="on">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Tu usuario" autocomplete="username" required>

                    <label for="clave">Clave</label>
                    <input type="password" id="clave" name="clave" placeholder="Tu contraseña" autocomplete="current-password" required>

                    <?php if (!empty($error)): ?>
                        <div class="form-message" role="alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Ingresar <span aria-hidden="true">→</span></button>
                </form>
                <p class="login-security">Acceso seguro para personal autorizado</p>
            </div>
        </section>
    </main>
</body>
</html>
