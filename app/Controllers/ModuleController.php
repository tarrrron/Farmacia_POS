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
