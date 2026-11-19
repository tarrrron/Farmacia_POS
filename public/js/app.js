const menuToggle = document.querySelector(".menu-toggle");

if (menuToggle) {
    menuToggle.addEventListener("click", () => {
        document.body.classList.toggle("sidebar-collapsed");
        const expanded = !document.body.classList.contains("sidebar-collapsed");
        menuToggle.setAttribute("aria-expanded", String(expanded));
    });
}

const productImageInput = document.querySelector("#imagen");
const productImagePreview = document.querySelector("#product-image-preview");
if (productImageInput && productImagePreview) {
    let previewUrl = null;
    productImageInput.addEventListener("change", () => {
        const file = productImageInput.files?.[0];
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = file ? URL.createObjectURL(file) : null;
        productImagePreview.src = previewUrl || productImagePreview.dataset.originalSrc || "";
        productImagePreview.hidden = !previewUrl && !productImagePreview.dataset.originalSrc;
    });
}

const dashboardDataElement = document.querySelector("#dashboard-chart-data");
if (dashboardDataElement) {
    const dashboardData = JSON.parse(dashboardDataElement.textContent);
    const formatSoles = (value) => `S/ ${Number(value).toLocaleString("es-PE", {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    const colors = {"Agotados":"#dc5a56", "Stock bajo":"#e6a23c", "Disponibles":"#159580"};
    const salesCanvas = document.querySelector("#weekly-sales-chart");
    const stockCanvas = document.querySelector("#stock-status-chart");

    function prepareCanvas(canvas) {
        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        canvas.width = Math.max(1, rect.width * ratio); canvas.height = Math.max(1, rect.height * ratio);
        const context = canvas.getContext("2d"); context.setTransform(ratio, 0, 0, ratio, 0, 0);
        return {context, width:rect.width, height:rect.height};
    }

    function drawSales() {
        const {context:ctx, width, height} = prepareCanvas(salesCanvas);
        const data = dashboardData.sales;
        const pad = {top:18, right:14, bottom:38, left:58};
        const chartWidth = Math.max(1, width - pad.left - pad.right), chartHeight = Math.max(1, height - pad.top - pad.bottom);
        const max = Math.max(1, ...data.map((item) => Number(item.total)));
        const step = Math.pow(10, Math.floor(Math.log10(max)));
        const ceiling = Math.ceil(max / step) * step;
        ctx.clearRect(0, 0, width, height); ctx.font = "11px Arial"; ctx.textBaseline = "middle";
        for (let tick = 0; tick <= 4; tick++) {
            const value = ceiling * tick / 4, y = pad.top + chartHeight - chartHeight * tick / 4;
            ctx.strokeStyle = "#e9eef4"; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke();
            ctx.fillStyle = "#7b8797"; ctx.textAlign = "right"; ctx.fillText(value >= 1000 ? `${(value / 1000).toFixed(value % 1000 ? 1 : 0)}k` : value.toFixed(0), pad.left - 9, y);
        }
        const slot = chartWidth / data.length, barWidth = Math.min(38, slot * .52);
        data.forEach((item, index) => {
            const value = Number(item.total), barHeight = chartHeight * value / ceiling;
            const x = pad.left + slot * index + (slot - barWidth) / 2, y = pad.top + chartHeight - barHeight;
            ctx.fillStyle = "#159580";
            if (barHeight > 0) { ctx.beginPath(); ctx.roundRect(x, y, barWidth, Math.max(barHeight, 3), [5,5,0,0]); ctx.fill(); }
            const [year, month, day] = item.fecha.split("-").map(Number);
            const label = new Date(year, month - 1, day).toLocaleDateString("es-PE", {weekday:"short"}).replace(".", "");
            ctx.fillStyle = "#64748b"; ctx.textAlign = "center"; ctx.fillText(label, x + barWidth / 2, height - 15);
            salesCanvas.setAttribute("aria-label", `Ventas de los ultimos siete dias: ${data.map((entry) => `${entry.fecha}, ${formatSoles(entry.total)}`).join("; ")}`);
        });
        const accessible = document.querySelector("#weekly-sales-data");
        accessible.innerHTML = `<ul>${data.map((item) => `<li>${item.fecha}: ${formatSoles(item.total)}</li>`).join("")}</ul>`;
    }

    function drawStock() {
        const {context:ctx, width, height} = prepareCanvas(stockCanvas);
        const entries = Object.entries(dashboardData.stock), total = entries.reduce((sum, [, value]) => sum + Number(value), 0);
        const cx = width / 2, cy = height / 2, radius = Math.min(width, height) * .43, thickness = Math.max(14, radius * .24);
        ctx.clearRect(0, 0, width, height); ctx.lineWidth = thickness; ctx.lineCap = "butt";
        ctx.beginPath(); ctx.strokeStyle = "#eef2f6"; ctx.arc(cx, cy, radius - thickness / 2, 0, Math.PI * 2); ctx.stroke();
        if (total) {
            let start = -Math.PI / 2;
            entries.forEach(([label, value]) => {
                const angle = Math.PI * 2 * Number(value) / total;
                if (angle > 0) { ctx.beginPath(); ctx.strokeStyle = colors[label]; ctx.arc(cx, cy, radius - thickness / 2, start, start + angle); ctx.stroke(); }
                start += angle;
            });
        }
        stockCanvas.setAttribute("aria-label", entries.map(([label, value]) => `${label}: ${value}`).join("; "));
        document.querySelector("#stock-legend").innerHTML = entries.map(([label, value]) => `<li><span><i style="background:${colors[label]}"></i>${label}</span><strong>${Number(value)}</strong></li>`).join("");
    }

    const drawDashboardCharts = () => { drawSales(); drawStock(); };
    drawDashboardCharts();
    let resizeTimer;
    window.addEventListener("resize", () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(drawDashboardCharts, 120); });
}

const saleForm = document.querySelector("#sale-form");
if (saleForm) {
    const products = JSON.parse(document.querySelector("#sale-products").textContent);
    const search = document.querySelector("#product-search");
    const results = document.querySelector("#product-results");
    const linesBody = document.querySelector("#sale-lines");
    const cart = new Map();
    const money = (value) => `S/ ${Number(value).toFixed(2)}`;
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[char]));

    function renderResults() {
        const query = search.value.trim().toLocaleLowerCase("es");
        results.innerHTML = "";
        if (!query) return;
        const matches = products.filter((product) => product.nombre.toLocaleLowerCase("es").includes(query) || (product.codigobarra || "").toLocaleLowerCase("es").includes(query)).slice(0, 8);
        if (!matches.length) { results.innerHTML = '<p class="sale-no-results">No se encontraron productos disponibles.</p>'; return; }
        matches.forEach((product) => {
            const button = document.createElement("button");
            button.type = "button"; button.className = "sale-product-option"; button.setAttribute("role", "option");
            const image = product.urlimagen
                ? `<img class="sale-product-image" src="${escapeHtml(product.urlimagen)}" alt="">`
                : '<span class="sale-product-image-placeholder" aria-hidden="true">—</span>';
            button.innerHTML = `${image}<span class="sale-product-copy"><strong>${escapeHtml(product.nombre)}</strong><small>${escapeHtml(product.codigobarra || product.unidad)} · Stock ${Number(product.stock)}</small></span><b>${money(product.pventa)}</b>`;
            button.addEventListener("click", () => addProduct(product)); results.append(button);
        });
    }

    function addProduct(product) {
        const current = cart.get(Number(product.idproducto));
        if (current) current.cantidad = Math.min(Number(current.cantidad) + 1, Number(product.stock));
        else cart.set(Number(product.idproducto), {product, cantidad: 1});
        search.value = ""; results.innerHTML = ""; renderLines(); search.focus();
    }

    function renderLines() {
        linesBody.innerHTML = "";
        if (!cart.size) linesBody.innerHTML = '<tr><td class="empty-state" colspan="6">Busca y agrega productos a la venta.</td></tr>';
        cart.forEach(({product, cantidad}, id) => {
            const row = document.createElement("tr");
            const availableUnits = Math.floor(Number(product.stock));
            row.innerHTML = `<td><strong>${escapeHtml(product.nombre)}</strong><small>${escapeHtml(product.unidad)}</small><input type="hidden" name="items[${id}][id]" value="${id}"></td><td>${money(product.pventa)}</td><td>${Number(product.stock)}</td><td><input class="sale-quantity" type="number" name="items[${id}][cantidad]" value="${cantidad}" min="1" max="${availableUnits}" step="1" inputmode="numeric" aria-label="Cantidad de ${escapeHtml(product.nombre)}" required></td><td class="line-total">${money(Number(product.pventa) * Number(cantidad))}</td><td><button class="sale-remove" type="button" aria-label="Quitar ${escapeHtml(product.nombre)}">Quitar</button></td>`;
            const input = row.querySelector(".sale-quantity");
            const applyQuantity = () => { const quantity = Number(input.value); if (!Number.isInteger(quantity) || quantity < 1 || quantity > availableUnits) return false; cart.get(id).cantidad = quantity; row.querySelector(".line-total").textContent = money(Number(product.pventa) * quantity); updateTotal(); return true; };
            input.addEventListener("input", applyQuantity);
            input.addEventListener("change", () => { if (!applyQuantity()) { input.value = String(cart.get(id).cantidad); } });
            row.querySelector(".sale-remove").addEventListener("click", () => { cart.delete(id); renderLines(); });
            linesBody.append(row);
        });
        updateTotal();
    }

    const payment = document.querySelector("#sale-payment");
    const creditFields = document.querySelector("#sale-credit-fields");
    const cashFields = document.querySelector("#sale-cash-fields");
    const dueDate = document.querySelector("#sale-due-date");
    const received = document.querySelector("#sale-received");
    const changeBox = document.querySelector("#sale-change");
    const clientSelect = document.querySelector("#sale-client");
    const clientDebt = document.querySelector("#client-debt");
    let saleTotal = 0;

    function currentTotal() {
        let total = 0;
        cart.forEach(({product, cantidad}) => { total += Number(product.pventa) * Number(cantidad); });
        return Math.round(total * 100) / 100;
    }

    function updateChange() {
        const value = received.value.trim();
        if (payment.value !== "C" || value === "" || !cart.size) { changeBox.hidden = true; return true; }
        const amount = Number(value);
        changeBox.hidden = false;
        if (!Number.isFinite(amount) || amount < saleTotal) {
            changeBox.className = "sale-change is-short";
            changeBox.textContent = `Faltan ${money(Math.max(0, saleTotal - (Number.isFinite(amount) ? amount : 0)))}`;
            return false;
        }
        changeBox.className = "sale-change";
        changeBox.textContent = `Vuelto: ${money(amount - saleTotal)}`;
        return true;
    }

    function updateClientDebt() {
        const option = clientSelect.selectedOptions[0];
        const debt = Number(option?.dataset.deuda || 0), overdue = Number(option?.dataset.vencida || 0);
        if (!clientSelect.value || debt <= 0) { clientDebt.hidden = true; return; }
        clientDebt.hidden = false;
        clientDebt.className = overdue > 0 ? "client-debt is-overdue" : "client-debt";
        clientDebt.textContent = overdue > 0
            ? `Deuda pendiente: ${money(debt)} (vencido: ${money(overdue)}). No se le puede vender a crédito.`
            : `Deuda pendiente: ${money(debt)}.`;
    }

    function togglePayment() {
        const isCredit = payment.value === "R";
        creditFields.hidden = !isCredit;
        dueDate.disabled = !isCredit;
        cashFields.hidden = isCredit;
        updateChange();
    }

    function updateTotal() {
        let quantity = 0;
        cart.forEach(({cantidad}) => { quantity += Number(cantidad); });
        saleTotal = currentTotal();
        document.querySelector("#sale-total").textContent = money(saleTotal);
        document.querySelector("#sale-count").textContent = `${quantity} ${quantity === 1 ? "producto" : "productos"}`;
        document.querySelector("#sale-submit").disabled = cart.size === 0;
        updateChange();
    }

    // Enter en el buscador agrega el producto (lectores de código de barras) y nunca envía la venta.
    search.addEventListener("keydown", (event) => {
        if (event.key !== "Enter") return;
        event.preventDefault();
        const query = search.value.trim().toLocaleLowerCase("es");
        if (!query) return;
        const exact = products.find((product) => (product.codigobarra || "").toLocaleLowerCase("es") === query);
        const matches = products.filter((product) => product.nombre.toLocaleLowerCase("es").includes(query) || (product.codigobarra || "").toLocaleLowerCase("es").includes(query));
        if (exact) addProduct(exact);
        else if (matches.length === 1) addProduct(matches[0]);
    });
    saleForm.addEventListener("keydown", (event) => {
        if (event.key === "Enter" && event.target.tagName === "INPUT") event.preventDefault();
    });
    payment.addEventListener("change", togglePayment);
    received.addEventListener("input", updateChange);
    clientSelect.addEventListener("change", updateClientDebt);
    document.querySelectorAll("[data-days]").forEach((button) => button.addEventListener("click", () => {
        const date = new Date(); date.setDate(date.getDate() + Number(button.dataset.days));
        dueDate.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    }));
    togglePayment(); updateClientDebt();

    search.addEventListener("input", renderResults);
    document.addEventListener("click", (event) => { if (!results.contains(event.target) && event.target !== search) results.innerHTML = ""; });
    saleForm.addEventListener("submit", (event) => { if (!updateChange()) { event.preventDefault(); received.focus(); return; } if (!cart.size || [...cart.values()].some(({product,cantidad}) => !Number.isInteger(Number(cantidad)) || Number(cantidad) > Math.floor(Number(product.stock)) || Number(cantidad) < 1)) { event.preventDefault(); } });
}


// Buscador rápido en listados: filtra las filas de la tabla indicada en data-filter-target.
document.querySelectorAll(".table-filter").forEach((input) => {
    const table = document.querySelector(input.dataset.filterTarget);
    if (!table) return;
    const normalize = (value) => value.toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    let emptyRow = null;
    input.addEventListener("input", () => {
        const query = normalize(input.value.trim());
        let visible = 0;
        table.querySelectorAll("tbody tr").forEach((row) => {
            if (row === emptyRow || row.querySelector(".empty-state")) return;
            const show = !query || normalize(row.textContent).includes(query);
            row.hidden = !show;
            if (show) visible++;
        });
        if (!emptyRow) {
            emptyRow = document.createElement("tr");
            emptyRow.innerHTML = `<td class="empty-state" colspan="${table.querySelectorAll("thead th").length}">No hay resultados para la búsqueda.</td>`;
            table.querySelector("tbody").append(emptyRow);
        }
        emptyRow.hidden = visible > 0 || !query;
    });
});

// Botones de impresión.
document.querySelectorAll("[data-print]").forEach((button) => button.addEventListener("click", () => window.print()));

// Botones que llenan un campo con un valor (ej. pagar saldo completo).
document.querySelectorAll("[data-fill-target]").forEach((button) => button.addEventListener("click", () => {
    const field = document.querySelector(button.dataset.fillTarget);
    if (field) { field.value = button.dataset.fillValue; field.focus(); }
}));

// Número de operación obligatorio para pagos que no son en efectivo.
const paymentMethod = document.querySelector("#metodo_pago");
const operationField = document.querySelector("#operation-field");
if (paymentMethod && operationField) {
    const operationInput = operationField.querySelector("input");
    const toggleOperation = () => {
        const needsOperation = paymentMethod.value !== "EFECTIVO";
        operationField.hidden = !needsOperation;
        operationInput.required = needsOperation;
        if (!needsOperation) operationInput.value = "";
    };
    paymentMethod.addEventListener("change", toggleOperation);
    toggleOperation();
}

// Formularios que piden un motivo antes de enviarse (ej. anular un pago).
document.querySelectorAll("form[data-require-reason]").forEach((form) => form.addEventListener("submit", (event) => {
    const reason = window.prompt(form.dataset.requireReason, "");
    if (reason === null || reason.trim() === "") { event.preventDefault(); return; }
    form.querySelector("input[name='motivo']").value = reason.trim().slice(0, 150);
}));

// Margen del producto mientras se escriben los precios.
const salePrice = document.querySelector("#pventa");
const costPrice = document.querySelector("#pcompra");
const marginInfo = document.querySelector("#product-margin");
if (salePrice && costPrice && marginInfo) {
    const updateMargin = () => {
        const sale = Number(salePrice.value), cost = Number(costPrice.value);
        if (!salePrice.value || !costPrice.value || !(cost > 0)) { marginInfo.textContent = ""; marginInfo.className = ""; return; }
        const margin = ((sale - cost) / cost) * 100;
        marginInfo.textContent = sale < cost ? "El precio de venta es menor que el de compra." : `Margen de ganancia: ${margin.toFixed(1)}% (S/ ${(sale - cost).toFixed(2)} por unidad)`;
        marginInfo.className = sale < cost ? "text-danger" : "";
    };
    salePrice.addEventListener("input", updateMargin);
    costPrice.addEventListener("input", updateMargin);
    updateMargin();
}
