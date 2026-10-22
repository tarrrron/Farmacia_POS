<div class="page-title">
    <h1>Top de productos</h1>
    <p>Consulta los productos con mayor movimiento en ventas.</p>
</div>

<section class="panel">
    <div class="section-toolbar">
        <div>
            <h2>Ranking de ventas</h2>
            <p>Productos ordenados por cantidad vendida.</p>
        </div>
        <form class="period-filter" method="GET" action="index.php">
            <input type="hidden" name="route" value="module/top-productos">
            <label for="mes">Periodo</label>
            <select id="mes" name="mes" onchange="this.form.submit()">
                <option value="">Todos los meses</option>
                <?php
                $meses = [1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                foreach ($meses as $numero => $nombre):
                ?>
                    <option value="<?= $numero ?>" <?= (string) ($_GET["mes"] ?? "") === (string) $numero ? "selected" : "" ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="table-wrap">
        <table class="data-table ranking-table">
            <thead>
                <tr><th>Puesto</th><th>Producto</th><th>Stock actual</th><th>Ventas</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($topProductos)): ?>
                    <?php $puesto = 1; ?>
                    <?php foreach ($topProductos as $producto): ?>
                        <tr>
                            <td><span class="ranking-number"><?= $puesto++ ?></span></td>
                            <td><strong><?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?></strong><small><?= htmlspecialchars($producto["codigobarra"] ?: "Sin codigo", ENT_QUOTES, "UTF-8") ?></small></td>
                            <td><?= htmlspecialchars((string) $producto["stock"], ENT_QUOTES, "UTF-8") ?> unid.</td>
                            <td><span class="ranking-sales"><?= htmlspecialchars((string) $producto["total_vendido"], ENT_QUOTES, "UTF-8") ?> ventas</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty-state">No hay ventas registradas para este periodo.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
