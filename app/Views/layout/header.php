<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" type="button" aria-label="Alternar menu" aria-expanded="true">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    <div class="topbar-user">
        <span><?= htmlspecialchars($_SESSION["nombre"] ?? "") ?></span>
        <a class="logout-link" href="index.php?route=logout">Cerrar sesion</a>
    </div>
</header>
