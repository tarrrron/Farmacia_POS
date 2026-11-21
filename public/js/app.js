const menuToggle = document.querySelector(".menu-toggle");

if (menuToggle) {
    const mobileMenu = window.matchMedia("(max-width: 900px)");
    const setMenuState = (open) => {
        document.body.classList.toggle("sidebar-open", mobileMenu.matches && open);
        document.body.classList.toggle("sidebar-collapsed", !mobileMenu.matches && !open);
        menuToggle.setAttribute("aria-expanded", String(open));
    };
    setMenuState(!mobileMenu.matches);
    menuToggle.addEventListener("click", () => {
        const open = menuToggle.getAttribute("aria-expanded") !== "true";
        setMenuState(open);
    });
    mobileMenu.addEventListener("change", () => setMenuState(!mobileMenu.matches));
    document.querySelector(".sidebar")?.addEventListener("click", (event) => {
        if (mobileMenu.matches && event.target.closest("a")) setMenuState(false);
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && mobileMenu.matches && document.body.classList.contains("sidebar-open")) {
            setMenuState(false);
            menuToggle.focus();
        }
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
    const clientRegistration = document.querySelector("#sale-client-registration");
    const clientDocumentType = document.querySelector("#sale-client-document-type");
    const clientDocumentNumber = document.querySelector("#sale-client-document-number");
    const clientName = document.querySelector("#sale-client-name");
    const clientAddress = document.querySelector("#sale-client-address");
    const lookupButton = document.querySelector("#sale-client-lookup");
    const lookupStatus = document.querySelector("#sale-client-lookup-status");
    const receipt = document.querySelector("#sale-document");
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
        if (overdue > 0 && payment.value === "R") {
            payment.value = "C";
            togglePayment();
        }
    }

    function syncClientRequirements() {
        const needsClient = payment.value === "R" || /FACTURA/.test(receipt.selectedOptions[0]?.dataset.kind || "");
        const hasExisting = clientSelect.value !== "";
        clientDocumentNumber.required = needsClient && !hasExisting;
        clientName.required = needsClient && !hasExisting;
        if (needsClient && !hasExisting) clientRegistration.open = true;
        if (hasExisting) clientRegistration.open = false;
    }

    function togglePayment() {
        let isCredit = payment.value === "R";
        const overdue = Number(clientSelect.selectedOptions[0]?.dataset.vencida || 0);
        if (isCredit && overdue > 0) {
            payment.value = "C";
            isCredit = false;
        }
        creditFields.hidden = !isCredit;
        dueDate.disabled = !isCredit;
        cashFields.hidden = isCredit;
        syncClientRequirements();
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
    clientSelect.addEventListener("change", syncClientRequirements);
    receipt.addEventListener("change", syncClientRequirements);
    [clientDocumentType, clientDocumentNumber, clientName, clientAddress].forEach((field) => field.addEventListener("input", () => {
        if (clientSelect.value) clientSelect.value = "";
        syncClientRequirements();
    }));
    clientDocumentType.addEventListener("change", () => {
        const kind = clientDocumentType.selectedOptions[0]?.dataset.kind || "";
        clientDocumentNumber.maxLength = kind === "DNI" ? 8 : 11;
        clientDocumentNumber.placeholder = kind === "DNI" ? "8 dígitos" : "11 dígitos";
    });
    lookupButton.addEventListener("click", async () => {
        const kind = clientDocumentType.selectedOptions[0]?.dataset.kind || "";
        const number = clientDocumentNumber.value.trim();
        lookupStatus.className = "form-hint";
        lookupStatus.textContent = "";
        lookupButton.disabled = true;
        lookupButton.textContent = "Consultando…";
        try {
            const data = new FormData();
            data.set("csrf_token", saleForm.querySelector("[name='csrf_token']").value);
            data.set("tipo", kind);
            data.set("numero", number);
            const response = await fetch(saleForm.dataset.lookupUrl, { method: "POST", body: data, headers: { "X-Requested-With": "XMLHttpRequest" } });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || "No se pudo consultar el documento.");
            const person = payload.cliente;
            const matchingClient = [...clientSelect.options].find((option) => option.value && option.dataset.document === person.numero);
            if (matchingClient) {
                clientSelect.value = matchingClient.value;
                clientRegistration.open = false;
                updateClientDebt();
                lookupStatus.textContent = `Cliente existente: ${matchingClient.textContent.trim()}.`;
            } else {
                clientDocumentNumber.value = person.numero;
                clientName.value = person.nombre;
                clientAddress.value = person.direccion || "";
                document.querySelector("#sale-client-business-name").value = person.razon_social || "";
                document.querySelector("#sale-client-department").value = person.departamento || "";
                document.querySelector("#sale-client-province").value = person.provincia || "";
                document.querySelector("#sale-client-district").value = person.distrito || "";
                clientRegistration.open = true;
                lookupStatus.textContent = "Datos encontrados. Se registrará el cliente al guardar la venta.";
            }
            syncClientRequirements();
        } catch (error) {
            lookupStatus.className = "form-hint form-hint-error";
            lookupStatus.textContent = error.message || "No se pudo consultar el documento.";
        } finally {
            lookupButton.disabled = false;
            lookupButton.textContent = "Consultar";
        }
    });
    document.querySelectorAll("[data-days]").forEach((button) => button.addEventListener("click", () => {
        const date = new Date(); date.setDate(date.getDate() + Number(button.dataset.days));
        dueDate.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    }));
    togglePayment(); updateClientDebt(); syncClientRequirements();

    search.addEventListener("input", renderResults);
    document.addEventListener("click", (event) => { if (!results.contains(event.target) && event.target !== search) results.innerHTML = ""; });
    saleForm.addEventListener("submit", (event) => {
        const invoice = /FACTURA/.test(receipt.selectedOptions[0]?.dataset.kind || "");
        const clientKind = clientSelect.value
            ? (clientSelect.selectedOptions[0]?.dataset.kind || "")
            : (clientDocumentType.selectedOptions[0]?.dataset.kind || "");
        if (invoice && clientKind !== "RUC") {
            event.preventDefault();
            clientRegistration.open = true;
            lookupStatus.className = "form-hint form-hint-error";
            lookupStatus.textContent = "Para emitir factura, selecciona o registra un cliente con RUC.";
            clientSelect.focus();
            return;
        }
        if (!updateChange()) { event.preventDefault(); received.focus(); return; }
        if (!cart.size || [...cart.values()].some(({product,cantidad}) => !Number.isInteger(Number(cantidad)) || Number(cantidad) > Math.floor(Number(product.stock)) || Number(cantidad) < 1)) event.preventDefault();
    });
}


// Buscador rápido en listados: filtra las filas de la tabla indicada en data-filter-target.
document.querySelectorAll(".table-filter").forEach((input) => {
    const table = document.querySelector(input.dataset.filterTarget);
    if (!table) return;
    const normalize = (value) => value.toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const controls = [...document.querySelectorAll("[data-filter-target]")]
        .filter((control) => control.dataset.filterTarget === input.dataset.filterTarget);
    const categoryFilter = controls.find((control) => control.hasAttribute("data-filter-category"));
    const statusFilter = controls.find((control) => control.hasAttribute("data-filter-status"));
    let emptyRow = null;
    const applyFilters = () => {
        const query = normalize(input.value.trim());
        const category = normalize(categoryFilter?.value || "");
        const status = statusFilter?.value || "";
        let visible = 0;
        table.querySelectorAll("tbody tr").forEach((row) => {
            if (row === emptyRow || row.querySelector(".empty-state")) return;
            const searchableText = normalize(`${row.textContent} ${row.dataset.category || ""}`);
            const matchesQuery = !query || searchableText.includes(query);
            const matchesCategory = !category || normalize(row.dataset.category || "") === category;
            const matchesStatus = !status || row.dataset.status === status;
            const show = matchesQuery && matchesCategory && matchesStatus;
            row.hidden = !show;
            if (show) visible++;
        });
        if (!emptyRow) {
            emptyRow = document.createElement("tr");
            emptyRow.innerHTML = `<td class="empty-state" colspan="${table.querySelectorAll("thead th").length}">No hay resultados para la búsqueda.</td>`;
            table.querySelector("tbody").append(emptyRow);
        }
        emptyRow.hidden = visible > 0 || (!query && !category && !status);
    };
    controls.forEach((control) => control.addEventListener(control.tagName === "SELECT" ? "change" : "input", applyFilters));
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

// Confirmaciones con modal accesible para acciones de administración.
const actionDialog = document.querySelector("#action-confirmation");
if (actionDialog) {
    const confirmationMessage = actionDialog.querySelector("#confirmation-message");
    const acceptConfirmation = actionDialog.querySelector("[data-confirm-accept]");
    let pendingSubmission = null;
    document.addEventListener("submit", (event) => {
        const form = event.target;
        const submitter = event.submitter;
        const message = submitter?.dataset.confirm;
        if (!message || form.dataset.confirmed === "true") return;
        event.preventDefault();
        pendingSubmission = { form, submitter };
        confirmationMessage.textContent = message;
        actionDialog.showModal();
    });
    acceptConfirmation.addEventListener("click", () => {
        if (!pendingSubmission) return;
        const { form, submitter } = pendingSubmission;
        pendingSubmission = null;
        form.dataset.confirmed = "true";
        actionDialog.close();
        form.requestSubmit(submitter);
        delete form.dataset.confirmed;
    });
    actionDialog.querySelector("[data-confirm-cancel]").addEventListener("click", () => {
        pendingSubmission = null;
        actionDialog.close();
    });
    actionDialog.addEventListener("click", (event) => {
        if (event.target === actionDialog) actionDialog.close();
    });
}

// Mensaje de éxito breve en formato toast.
document.querySelectorAll(".alert-success[role='status']").forEach((alert) => {
    alert.classList.add("success-toast");
    window.setTimeout(() => alert.classList.add("is-visible"), 30);
    window.setTimeout(() => {
        alert.classList.remove("is-visible");
        window.setTimeout(() => alert.remove(), 250);
    }, 4200);
});

// Edición de registros en un modal reutilizable desde los listados.
const editModal = document.querySelector("#record-edit-modal");
if (editModal) {
    const editModalTitle = editModal.querySelector("#record-edit-title");
    const editModalContent = editModal.querySelector(".edit-modal-content");
    const closeEditModal = () => editModal.close();

    const showFormResponse = (html) => {
        const parsed = new DOMParser().parseFromString(html, "text/html");
        const formPanel = [...parsed.querySelectorAll("main .panel")].find((panel) => panel.querySelector("form.form-stack"));
        if (!formPanel) return false;
        const heading = parsed.querySelector("main .page-title h1");
        const description = parsed.querySelector("main .page-title p");
        if (heading) editModalTitle.textContent = heading.textContent.trim();
        editModalContent.replaceChildren();
        if (description?.textContent.trim()) {
            const intro = document.createElement("p");
            intro.className = "edit-modal-intro";
            intro.textContent = description.textContent.trim();
            editModalContent.append(intro);
        }
        editModalContent.append(document.importNode(formPanel, true));
        return true;
    };

    document.addEventListener("click", async (event) => {
        const link = event.target.closest("a[data-modal-edit]");
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        editModalTitle.textContent = "Cargando formulario…";
        editModalContent.innerHTML = '<p class="edit-modal-loading" role="status">Cargando datos del registro…</p>';
        if (!editModal.open) editModal.showModal();
        try {
            const response = await fetch(link.href, { headers: { "X-Requested-With": "XMLHttpRequest" } });
            const html = await response.text();
            if (!response.ok || !showFormResponse(html)) {
                throw new Error(response.ok ? "No se pudo cargar el formulario de edición." : `No se pudo abrir la edición (HTTP ${response.status}).`);
            }
        } catch (error) {
            editModalTitle.textContent = "No se pudo abrir la edición";
            editModalContent.innerHTML = "";
            const message = document.createElement("p");
            message.className = "alert alert-error";
            message.textContent = error.message || "Ocurrió un error al cargar el formulario.";
            editModalContent.append(message);
        }
    });

    editModal.querySelector(".edit-modal-close").addEventListener("click", closeEditModal);
    editModal.addEventListener("click", (event) => {
        if (event.target === editModal) closeEditModal();
    });

    editModalContent.addEventListener("submit", async (event) => {
        const form = event.target.closest("form.form-stack");
        if (!form) return;
        event.preventDefault();
        if (!form.reportValidity()) return;
        const submitButton = event.submitter;
        if (submitButton) { submitButton.disabled = true; submitButton.dataset.originalText = submitButton.textContent; submitButton.textContent = "Guardando…"; }
        try {
            const response = await fetch(form.action, {
                method: (form.method || "POST").toUpperCase(),
                body: new FormData(form),
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            const html = await response.text();
            if (showFormResponse(html)) {
                if (submitButton) submitButton.disabled = false;
                return;
            }
            if (response.ok) {
                closeEditModal();
                window.location.reload();
                return;
            }
            throw new Error(`No se pudo guardar el registro (HTTP ${response.status}).`);
        } catch (error) {
            if (submitButton) { submitButton.disabled = false; submitButton.textContent = submitButton.dataset.originalText || "Guardar cambios"; }
            const message = document.createElement("p");
            message.className = "alert alert-error";
            message.textContent = error.message || "Ocurrió un error al guardar los cambios.";
            editModalContent.prepend(message);
        }
    });

    editModalContent.addEventListener("change", (event) => {
        if (event.target.matches("#imagen")) {
            const preview = editModalContent.querySelector("#product-image-preview");
            const file = event.target.files?.[0];
            if (!preview) return;
            preview.src = file ? URL.createObjectURL(file) : preview.dataset.originalSrc || "";
            preview.hidden = !file && !preview.dataset.originalSrc;
        }
    });
}
