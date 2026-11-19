<?php
class CreditoController
{
    private const ADMIN_PROFILE_ID = 1;

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
            "por_vencer" => "Vencen en 7 días",
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
        [$message, $error] = $this->flash();

        $view = __DIR__ . "/../Views/creditos/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function show(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $credit = $this->requireCredit($_GET["id"] ?? null);
        $csrfToken = $_SESSION["credito_csrf"];
        $paymentMethods = Credit::PAYMENT_METHODS;
        $isAdmin = $this->isAdmin();
        [$message, $error] = $this->flash();

        $view = __DIR__ . "/../Views/creditos/show.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function client(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $account = $id !== false ? $this->credits->clientStatement($id) : null;
        if (!$account) {
            http_response_code(404);
            exit("Cliente no encontrado.");
        }
        $paymentMethods = Credit::PAYMENT_METHODS;

        $view = __DIR__ . "/../Views/creditos/cliente.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function receipt(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $payment = $id !== false ? $this->credits->findPayment($id) : null;
        if (!$payment) {
            http_response_code(404);
            exit("Pago no encontrado.");
        }
        $paymentMethods = Credit::PAYMENT_METHODS;

        $view = __DIR__ . "/../Views/creditos/constancia.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function pay(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        $credit = $this->requireCredit($_POST["id"] ?? null);
        $back = "index.php?route=module/creditos/ver&id=" . (int) $credit["idcredito"];

        $amount = trim((string) ($_POST["monto"] ?? ""));
        $method = (string) ($_POST["metodo_pago"] ?? "");
        $operation = trim((string) ($_POST["nro_operacion"] ?? ""));
        $note = trim((string) ($_POST["observacion"] ?? ""));
        if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $amount)) {
            $this->back($back, "Ingresa un monto válido, con hasta dos decimales.");
        }
        if (mb_strlen($note) > 150 || mb_strlen($operation) > 50) {
            $this->back($back, "La observación (150) o el número de operación (50) superan el máximo de caracteres.");
        }
        if ($method !== "EFECTIVO" && $operation === "") {
            $this->back($back, "Ingresa el número de operación para pagos que no son en efectivo.");
        }

        try {
            $paymentId = $this->credits->addPayment((int) $credit["idcredito"], $amount, $method, $operation, $note, (int) $_SESSION["idusuario"]);
            $_SESSION["credito_message"] = "Pago registrado correctamente.";
            $_SESSION["credito_last_payment"] = $paymentId;
        } catch (RuntimeException $exception) {
            $_SESSION["credito_error"] = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $_SESSION["credito_error"] = "No se pudo registrar el pago. Inténtalo nuevamente.";
        }

        header("Location: " . $back, true, 303);
        exit;
    }

    public function cancelPayment(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        if (!$this->isAdmin()) {
            http_response_code(403);
            exit("Solo un administrador puede anular pagos.");
        }

        $paymentId = filter_var($_POST["idpago"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $creditId = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $reason = trim((string) ($_POST["motivo"] ?? ""));
        $back = "index.php?route=module/creditos" . ($creditId ? "/ver&id=" . $creditId : "");
        if ($paymentId === false || $reason === "" || mb_strlen($reason) > 150) {
            $this->back($back, "Indica el motivo de la anulación (máximo 150 caracteres).");
        }

        try {
            $this->credits->cancelPayment($paymentId, $reason, (int) $_SESSION["idusuario"]);
            $_SESSION["credito_message"] = "Pago anulado. El saldo del crédito fue restituido.";
        } catch (RuntimeException $exception) {
            $_SESSION["credito_error"] = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $_SESSION["credito_error"] = "No se pudo anular el pago. Inténtalo nuevamente.";
        }

        header("Location: " . $back, true, 303);
        exit;
    }

    public function extend(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        $credit = $this->requireCredit($_POST["id"] ?? null);
        $back = "index.php?route=module/creditos/ver&id=" . (int) $credit["idcredito"];

        $newDate = trim((string) ($_POST["fecha_vencimiento"] ?? ""));
        $reason = trim((string) ($_POST["motivo"] ?? ""));
        $parsed = DateTime::createFromFormat("!Y-m-d", $newDate);
        if (!$parsed || $parsed->format("Y-m-d") !== $newDate || $parsed < new DateTime("today")) {
            $this->back($back, "Selecciona una fecha de vencimiento válida, desde hoy en adelante.");
        }
        if ($reason === "" || mb_strlen($reason) > 150) {
            $this->back($back, "Indica el motivo de la prórroga (máximo 150 caracteres).");
        }

        try {
            $this->credits->extend((int) $credit["idcredito"], $newDate, $reason, (int) $_SESSION["idusuario"]);
            $_SESSION["credito_message"] = "Vencimiento prorrogado al " . $parsed->format("d/m/Y") . ".";
        } catch (RuntimeException $exception) {
            $_SESSION["credito_error"] = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $_SESSION["credito_error"] = "No se pudo prorrogar el crédito. Inténtalo nuevamente.";
        }

        header("Location: " . $back, true, 303);
        exit;
    }

    private function flash(): array
    {
        $message = $_SESSION["credito_message"] ?? "";
        $error = $_SESSION["credito_error"] ?? "";
        unset($_SESSION["credito_message"], $_SESSION["credito_error"]);
        return [$message, $error];
    }

    private function back(string $location, string $error): void
    {
        $_SESSION["credito_error"] = $error;
        header("Location: " . $location, true, 303);
        exit;
    }

    private function isAdmin(): bool
    {
        return (int) ($_SESSION["idperfil"] ?? 0) === self::ADMIN_PROFILE_ID;
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
