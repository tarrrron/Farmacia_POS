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
        <a class="logout-link" href="index.php?route=logout">Cerrar sesion</a>
    </div>
</header>
