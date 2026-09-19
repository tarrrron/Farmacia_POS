<header class="topbar">
    <div>
        <strong>Farmacia POS</strong>
        <span>Sistema de ventas</span>
    </div>
    <div class="topbar-user">
        <span><?= htmlspecialchars($_SESSION["nombre"] ?? "") ?></span>
        <a class="btn btn-light" href="index.php?route=logout">Salir</a>
    </div>
</header>
