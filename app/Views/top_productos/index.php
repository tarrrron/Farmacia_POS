<div style="padding: 10px 20px;">
    <div style="margin-bottom: 25px;">
        <h2 style="color: #1a252f; font-weight: 700; font-size: 1.6rem; margin-bottom: 5px;">Top de productos</h2>
        <p style="color: #6c757d; font-size: 0.95rem; margin: 0;">Visualiza los artículos con mayor rotación en ventas.</p>
    </div>

    <div style="background: #ffffff; border-radius: 6px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        
        <!-- Cabecera flex para el Título y el Buscador -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h4 style="color: #212529; font-weight: 700; font-size: 1.1rem; margin-bottom: 3px;">Ranking de ventas</h4>
                <p style="color: #6c757d; font-size: 0.9rem; margin: 0;">Controla la salida comercial de tu inventario.</p>
            </div>
            
            <!-- Buscador por mes -->
            <div>
                <form method="GET" action="index.php" style="display: flex; align-items: center; gap: 10px;">
                    <input type="hidden" name="route" value="module/top-productos">
                    <select name="mes" onchange="this.form.submit()" style="padding: 8px 15px; border-radius: 4px; border: 1px solid #ced4da; outline: none; font-size: 0.9rem; cursor: pointer; color: #495057; background-color: #fff;">
                        <option value="">Todos los meses</option>
                        <option value="1" <?= (isset($_GET['mes']) && $_GET['mes'] == '1') ? 'selected' : '' ?>>Enero</option>
                        <option value="2" <?= (isset($_GET['mes']) && $_GET['mes'] == '2') ? 'selected' : '' ?>>Febrero</option>
                        <option value="3" <?= (isset($_GET['mes']) && $_GET['mes'] == '3') ? 'selected' : '' ?>>Marzo</option>
                        <option value="4" <?= (isset($_GET['mes']) && $_GET['mes'] == '4') ? 'selected' : '' ?>>Abril</option>
                        <option value="5" <?= (isset($_GET['mes']) && $_GET['mes'] == '5') ? 'selected' : '' ?>>Mayo</option>
                        <option value="6" <?= (isset($_GET['mes']) && $_GET['mes'] == '6') ? 'selected' : '' ?>>Junio</option>
                        <option value="7" <?= (isset($_GET['mes']) && $_GET['mes'] == '7') ? 'selected' : '' ?>>Julio</option>
                        <option value="8" <?= (isset($_GET['mes']) && $_GET['mes'] == '8') ? 'selected' : '' ?>>Agosto</option>
                        <option value="9" <?= (isset($_GET['mes']) && $_GET['mes'] == '9') ? 'selected' : '' ?>>Septiembre</option>
                        <option value="10" <?= (isset($_GET['mes']) && $_GET['mes'] == '10') ? 'selected' : '' ?>>Octubre</option>
                        <option value="11" <?= (isset($_GET['mes']) && $_GET['mes'] == '11') ? 'selected' : '' ?>>Noviembre</option>
                        <option value="12" <?= (isset($_GET['mes']) && $_GET['mes'] == '12') ? 'selected' : '' ?>>Diciembre</option>
                    </select>
                </form>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <!-- Fondo gris para la cabecera idéntico a la vista de productos -->
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th style="padding: 12px 15px; font-size: 0.75rem; color: #495057; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #dee2e6;">Puesto</th>
                        <th style="padding: 12px 15px; font-size: 0.75rem; color: #495057; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #dee2e6;">Producto</th>
                        <th style="padding: 12px 15px; font-size: 0.75rem; color: #495057; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #dee2e6;">Stock Actual</th>
                        <th style="padding: 12px 15px; font-size: 0.75rem; color: #495057; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #dee2e6;">Estado de Venta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($topProductos)): ?>
                        <?php $puesto = 1; ?>
                        <?php foreach($topProductos as $producto): ?>
                            <tr style="border-bottom: 1px solid #f2f4f6;">
                                <td style="padding: 15px; color: #212529; font-weight: 700;">
                                    <?= $puesto++; ?>
                                </td>
                                <td style="padding: 15px;">
                                    <div style="color: #212529; font-weight: 700; font-size: 0.95rem; margin-bottom: 3px;">
                                        <?= htmlspecialchars($producto['nombre']); ?>
                                    </div>
                                    <!-- Subtítulo gris como en la vista de productos -->
                                    <div style="color: #adb5bd; font-size: 0.8rem;">
                                        <?= htmlspecialchars($producto['codigobarra']); ?>
                                    </div>
                                </td>
                                <td style="padding: 15px;">
                                    <div style="color: #212529; font-size: 0.95rem;">
                                        <?= htmlspecialchars($producto['stock']); ?> unid.
                                    </div>
                                </td>
                                <td style="padding: 15px;">
                                    <span style="background-color: #d1e7dd; color: #0f5132; padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                                        <?= htmlspecialchars($producto['total_vendido']); ?> ventas
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 30px; text-align: center; color: #6c757d; font-size: 0.95rem;">
                                No hay datos de ventas registrados para este periodo.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>