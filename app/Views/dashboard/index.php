<div class="page-title dashboard-title">
    <h1>Dashboard</h1>
    <span>Inicio</span>
</div>

<section class="summary-grid">
    <article class="summary-card card-blue">
        <strong><?= number_format($summary["ventas_dia"], 2) ?></strong>
        <span>Ventas del Dia</span>
    </article>
    <article class="summary-card card-blue">
        <strong><?= number_format($summary["ventas_ayer"], 2) ?></strong>
        <span>Ventas de Ayer</span>
    </article>
    <article class="summary-card card-green">
        <strong><?= number_format($summary["ventas_mes"], 2) ?></strong>
        <span>Ventas del Mes</span>
    </article>
    <article class="summary-card card-orange">
        <strong><?= number_format($summary["usuarios"]) ?></strong>
        <span>Usuarios Activos</span>
    </article>
</section>
