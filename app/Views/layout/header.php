<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" type="button" aria-label="Alternar menu" aria-expanded="true">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a class="brand-link" href="index.php?route=dashboard">
            <img src="public/img/farmacia-logo.png" alt="" width="42" height="42">
            <span><strong>FULL HEART</strong><small>Gestión de farmacia</small></span>
        </a>
    </div>
    <div class="topbar-user">
        <span><?= htmlspecialchars($_SESSION["nombre"] ?? "") ?></span>
        <form class="logout-form" action="index.php?route=logout" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["logout_csrf"] ?? "", ENT_QUOTES, "UTF-8") ?>">
            <button class="logout-link" type="submit">Cerrar sesión</button>
        </form>
    </div>
</header>
