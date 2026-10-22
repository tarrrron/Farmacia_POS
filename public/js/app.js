const menuToggle = document.querySelector(".menu-toggle");

if (menuToggle) {
    menuToggle.addEventListener("click", () => {
        document.body.classList.toggle("sidebar-collapsed");
        const expanded = !document.body.classList.contains("sidebar-collapsed");
        menuToggle.setAttribute("aria-expanded", String(expanded));
    });
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
            button.innerHTML = `<span><strong>${escapeHtml(product.nombre)}</strong><small>${escapeHtml(product.codigobarra || product.unidad)} · Stock ${Number(product.stock)}</small></span><b>${money(product.pventa)}</b>`;
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
            row.innerHTML = `<td><strong>${escapeHtml(product.nombre)}</strong><small>${escapeHtml(product.unidad)}</small><input type="hidden" name="items[${id}][id]" value="${id}"></td><td>${money(product.pventa)}</td><td>${Number(product.stock)}</td><td><input class="sale-quantity" type="number" name="items[${id}][cantidad]" value="${cantidad}" min="0.01" max="${Number(product.stock)}" step="0.01" aria-label="Cantidad de ${escapeHtml(product.nombre)}" required></td><td class="line-total">${money(Number(product.pventa) * Number(cantidad))}</td><td><button class="sale-remove" type="button" aria-label="Quitar ${escapeHtml(product.nombre)}">Quitar</button></td>`;
            const input = row.querySelector(".sale-quantity");
            input.addEventListener("input", () => { const quantity = Number(input.value); if (!Number.isFinite(quantity) || quantity <= 0 || quantity > Number(product.stock)) return; cart.get(id).cantidad = quantity; row.querySelector(".line-total").textContent = money(Number(product.pventa) * quantity); updateTotal(); });
            row.querySelector(".sale-remove").addEventListener("click", () => { cart.delete(id); renderLines(); });
            linesBody.append(row);
        });
        updateTotal();
    }

    function updateTotal() {
        let total = 0; let quantity = 0;
        cart.forEach(({product, cantidad}) => { total += Number(product.pventa) * Number(cantidad); quantity += Number(cantidad); });
        document.querySelector("#sale-total").textContent = money(total);
        document.querySelector("#sale-count").textContent = `${quantity} ${quantity === 1 ? "producto" : "productos"}`;
        document.querySelector("#sale-submit").disabled = cart.size === 0;
    }

    search.addEventListener("input", renderResults);
    document.addEventListener("click", (event) => { if (!results.contains(event.target) && event.target !== search) results.innerHTML = ""; });
    saleForm.addEventListener("submit", (event) => { if (!cart.size || [...cart.values()].some(({product,cantidad}) => Number(cantidad) > Number(product.stock) || Number(cantidad) <= 0)) { event.preventDefault(); } });
}
