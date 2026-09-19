<?php
class DashboardController
{
    private Dashboard $dashboard;
    private Profile $profiles;

    public function __construct(Dashboard $dashboard, Profile $profiles)
    {
        $this->dashboard = $dashboard;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $this->requireSession();

        $summary = $this->dashboard->summary();
        $menuOptions = $this->profiles->menuOptions((int) $_SESSION["idperfil"]);
        $view = __DIR__ . "/../Views/dashboard/index.php";

        require __DIR__ . "/../Views/layout/app.php";
    }

    private function requireSession(): void
    {
        if (!isset($_SESSION["idusuario"])) {
            header("Location: index.php");
            exit;
        }
    }
}
