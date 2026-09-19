<div class="page-title">
    <h1>Dashboard</h1>
    <p>Resumen inicial del sistema.</p>
</div>

<section class="summary-grid">
    <article class="summary-card">
        <span>Usuarios activos</span>
        <strong><?= number_format($summary["usuarios"]) ?></strong>
    </article>
    <article class="summary-card">
        <span>Productos activos</span>
        <strong><?= number_format($summary["productos"]) ?></strong>
    </article>
    <article class="summary-card">
        <span>Clientes registrados</span>
        <strong><?= number_format($summary["clientes"]) ?></strong>
    </article>
    <article class="summary-card">
        <span>Ventas registradas</span>
        <strong><?= number_format($summary["ventas"]) ?></strong>
    </article>
</section>

<section class="panel">
    <h2>Primer avance</h2>
    <p>La base de datos, el login y la estructura MVC ya estan listos para continuar con los siguientes modulos.</p>
</section>
