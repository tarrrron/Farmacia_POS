<?php
class TopProductosController
{
    private Product $products;
    private Profile $profiles;

    public function __construct(Product $products, Profile $profiles)
    {
        $this->products = $products;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess();

        $mes = filter_var($_GET["mes"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 12]]);
        $mesSeleccionado = $mes === false || $mes === null ? "" : $mes;

        $topProductos = $this->products->getTopProductos($mesSeleccionado);

        $view = __DIR__ . "/../Views/top_productos/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    private function requireAccess(): array
    {
        if (!isset($_SESSION["idusuario"])) {
            header("Location: index.php");
            exit;
        }

        $menuOptions = $this->profiles->menuOptions((int) ($_SESSION["idperfil"] ?? 0));

        foreach ($menuOptions as $option) {
            if (($option["url"] ?? "") === "module/top-productos") {
                return $menuOptions;
            }
        }

        http_response_code(403);
        exit("No tienes acceso al modulo de top productos.");
    }
}
