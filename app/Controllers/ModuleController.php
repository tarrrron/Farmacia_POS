<?php
class ModuleController
{
    private Profile $profiles;

    public function __construct(Profile $profiles)
    {
        $this->profiles = $profiles;
    }

    public function show(string $route): void
    {
        $this->requireSession();

        $menuOptions = $this->profiles->menuOptions((int) $_SESSION["idperfil"]);
        $module = $this->findModule($menuOptions, $route);

        if (!$module) {
            http_response_code(404);
            $module = [
                "descripcion" => "Modulo no disponible",
                "url" => $route
            ];
        }

        $view = __DIR__ . "/../Views/modules/placeholder.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function showLegacy(string $legacyRoute): void
    {
        $routes = [
            "vista/usuarios.php" => "module/usuarios",
            "vista/perfiles.php" => "module/perfiles",
            "vista/productos.php" => "module/productos",
            "vista/categorias.php" => "module/categorias",
            "vista/clientes.php" => "module/clientes",
            "vista/ventas.php" => "module/ventas",
            "vista/inventario.php" => "module/inventario",
            "vista/reportes_top_productos.php" => "module/top-productos"
        ];

        $this->show($routes[$legacyRoute] ?? $legacyRoute);
    }

    private function findModule(array $menuOptions, string $route): ?array
    {
        foreach ($menuOptions as $option) {
            if (($option["url"] ?? "") === $route) {
                return $option;
            }
        }

        return null;
    }

    private function requireSession(): void
    {
        if (!isset($_SESSION["idusuario"])) {
            header("Location: index.php");
            exit;
        }
    }
}
