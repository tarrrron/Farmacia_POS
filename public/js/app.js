const menuToggle = document.querySelector(".menu-toggle");

if (menuToggle) {
    menuToggle.addEventListener("click", () => {
        document.body.classList.toggle("sidebar-collapsed");
        const expanded = !document.body.classList.contains("sidebar-collapsed");
        menuToggle.setAttribute("aria-expanded", String(expanded));
    });
}
