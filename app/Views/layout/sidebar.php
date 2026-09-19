<?php $currentRoute = $_GET["route"] ?? "dashboard"; ?>

<aside class="sidebar">
    <nav aria-label="Menu principal">
        <a href="index.php?route=dashboard" <?= $currentRoute === "dashboard" ? 'aria-current="page"' : "" ?>>Inicio</a>
        <?php foreach ($menuOptions as $option): ?>
            <?php $route = $option["url"] ?? ""; ?>
            <a href="index.php?route=<?= urlencode($route) ?>" <?= $currentRoute === $route ? 'aria-current="page"' : "" ?>>
                <?= htmlspecialchars($option["descripcion"]) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
