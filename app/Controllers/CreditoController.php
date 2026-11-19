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

    public function show(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $credit = $this->requireCredit($_GET["id"] ?? null);
        $csrfToken = $_SESSION["credito_csrf"];
        $message = $_SESSION["credito_message"] ?? "";
        unset($_SESSION["credito_message"]);
        $error = $_SESSION["credito_error"] ?? "";
        unset($_SESSION["credito_error"]);

        $view = __DIR__ . "/../Views/creditos/show.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function pay(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        $credit = $this->requireCredit($_POST["id"] ?? null);
        $back = "index.php?route=module/creditos/ver&id=" . (int) $credit["idcredito"];

        $amount = trim((string) ($_POST["monto"] ?? ""));
        $note = trim((string) ($_POST["observacion"] ?? ""));
        if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $amount)) {
            $_SESSION["credito_error"] = "Ingresa un monto válido, con hasta dos decimales.";
            header("Location: " . $back, true, 303);
            exit;
        }
        if (mb_strlen($note) > 150) {
            $_SESSION["credito_error"] = "La observación no puede superar los 150 caracteres.";
            header("Location: " . $back, true, 303);
            exit;
        }

        try {
            $this->credits->addPayment((int) $credit["idcredito"], $amount, $note, (int) $_SESSION["idusuario"]);
            $_SESSION["credito_message"] = "Pago registrado correctamente.";
        } catch (RuntimeException $exception) {
            $_SESSION["credito_error"] = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $_SESSION["credito_error"] = "No se pudo registrar el pago. Inténtalo nuevamente.";
        }

        header("Location: " . $back, true, 303);
        exit;
    }

    private function requireCredit($id): array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $credit = $id !== false ? $this->credits->find($id) : null;
        if (!$credit) {
            http_response_code(404);
            exit("Crédito no encontrado.");
        }
        return $credit;
    }

    private function requireCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION["credito_csrf"], $token)) {
            http_response_code(403);
            exit("Solicitud invalida.");
        }
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
