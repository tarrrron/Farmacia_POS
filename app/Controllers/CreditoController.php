<?php
class CreditoController
{
    private Credit $credits;
    private Profile $profiles;

    public function __construct(Credit $credits, Profile $profiles)
    {
        $this->credits = $credits;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");

        $statuses = [
            "" => "Todos",
            "pendiente" => "Pendientes",
            "vencido" => "Vencidos",
            "pagado" => "Pagados",
            "anulado" => "Anulados"
        ];
        $status = (string) ($_GET["estado"] ?? "");
        if (!array_key_exists($status, $statuses)) {
            $status = "";
        }

        $credits = $this->credits->all($status);
        $summary = $this->credits->summary();
        $message = $_SESSION["credito_message"] ?? "";
        unset($_SESSION["credito_message"]);
        $error = $_SESSION["credito_error"] ?? "";
        unset($_SESSION["credito_error"]);

        $view = __DIR__ . "/../Views/creditos/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    private function requireAccess(string $method): array
    {
        if (!isset($_SESSION["idusuario"])) {
            header("Location: index.php");
            exit;
        }

        $menuOptions = $this->profiles->menuOptions((int) ($_SESSION["idperfil"] ?? 0));

        foreach ($menuOptions as $option) {
            if (($option["url"] ?? "") === "module/creditos") {
                if ($_SERVER["REQUEST_METHOD"] !== $method) {
                    header("Allow: " . $method);
                    http_response_code(405);
                    exit("Metodo no permitido.");
                }
                $_SESSION["credito_csrf"] ??= bin2hex(random_bytes(32));
                return $menuOptions;
            }
        }

        http_response_code(403);
        exit("No tienes acceso al modulo de creditos.");
    }
}
