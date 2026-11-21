<?php $currentRoute = $_GET["route"] ?? "dashboard"; ?>

<aside class="sidebar">
    <nav aria-label="Menu principal">
        <a href="index.php?route=dashboard" <?= $currentRoute === "dashboard" ? 'aria-current="page"' : "" ?>><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg></span><span>Inicio</span></a>
        <?php foreach ($menuOptions as $option): ?>
            <?php $route = $option["url"] ?? ""; ?>
            <?php
                $icons = [
                    "module/usuarios" => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1zM16 5.5a3 3 0 0 1 0 5.8M18 14a5 5 0 0 1 3 4.5V20h-3"/>',
                    "module/perfiles" => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 3.5h6M9 10h6M9 14h6M9 18h3"/>',
                    "module/productos" => '<path d="m4 7 8-4 8 4-8 4zM4 7v10l8 4 8-4V7M12 11v10"/>',
                    "module/categorias" => '<path d="M20 13 11 22l-9-9V3h10z"/><circle cx="7" cy="7" r="1"/>',
                    "module/clientes" => '<rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="9" r="3"/><path d="M8 17a4 4 0 0 1 8 0"/>',
                    "module/ventas" => '<path d="M3 4h2l2.5 11h11L21 7H6M9 19h.01M18 19h.01"/>',
                    "module/inventario" => '<path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6M9 10h.01M15 10h.01"/>',
                    "module/top-productos" => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
                    "module/creditos" => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/>'
                ];
                $icon = $icons[$route] ?? '<circle cx="12" cy="12" r="8"/>';
            ?>
            <a href="index.php?route=<?= urlencode($route) ?>" <?= ($currentRoute === $route || str_starts_with($currentRoute, $route . "/")) ? 'aria-current="page"' : "" ?>>
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?= $icon ?></svg></span>
                <span><?= htmlspecialchars($option["descripcion"]) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
