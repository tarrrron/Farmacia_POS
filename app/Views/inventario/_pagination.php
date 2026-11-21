<?php
$firstPage = max(1, min($page - 2, $totalPages - 4));
$lastPage = min($totalPages, $firstPage + 4);
?>
<nav class="table-pagination" aria-label="Paginación">
    <span class="pagination-summary">Mostrando <?= $from ?>–<?= $to ?> de <?= $totalRows ?></span>
    <div class="pagination-controls">
        <?php if ($page > 1): ?>
            <a href="<?= $e($baseUrl . ($page - 1)) ?>" aria-label="Página anterior">Anterior</a>
        <?php else: ?>
            <span class="is-disabled" aria-disabled="true">Anterior</span>
        <?php endif; ?>
        <?php for ($number = $firstPage; $number <= $lastPage; $number++): ?>
            <?php if ($number === $page): ?>
                <span class="is-current" aria-current="page"><?= $number ?></span>
            <?php else: ?>
                <a href="<?= $e($baseUrl . $number) ?>" aria-label="Página <?= $number ?>"><?= $number ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="<?= $e($baseUrl . ($page + 1)) ?>" aria-label="Página siguiente">Siguiente</a>
        <?php else: ?>
            <span class="is-disabled" aria-disabled="true">Siguiente</span>
        <?php endif; ?>
    </div>
</nav>
