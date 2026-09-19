<aside class="sidebar">
    <nav aria-label="Menu principal">
        <a href="index.php?route=dashboard" aria-current="page">Inicio</a>
        <?php foreach ($menuOptions as $option): ?>
            <a href="#">
                <?= htmlspecialchars($option["descripcion"]) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
