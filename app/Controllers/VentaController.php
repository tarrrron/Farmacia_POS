<?php
class VentaController
{
    private Sale $sales;
    private Profile $profiles;

    public function __construct(Sale $sales, Profile $profiles)
    {
        $this->sales = $sales;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $sales = $this->sales->all();
        $message = $_SESSION["venta_message"] ?? ""; unset($_SESSION["venta_message"]);
        $error = $_SESSION["venta_error"] ?? ""; unset($_SESSION["venta_error"]);
        $csrfToken = $_SESSION["venta_csrf"];
        $view = __DIR__ . "/../Views/ventas/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function create(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $products = $this->sales->catalog(); $clients = $this->sales->clients();
        $documentTypes = $this->sales->documentTypes(); $csrfToken = $_SESSION["venta_csrf"];
        $error = $_SESSION["venta_error"] ?? ""; unset($_SESSION["venta_error"]);
        $view = __DIR__ . "/../Views/ventas/create.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function store(): void
    {
        $this->requireAccess("POST"); $this->requireCsrf();
        $type = filter_var($_POST["idtipocomprobante"] ?? null, FILTER_VALIDATE_INT, ["options"=>["min_range"=>1]]);
        $client = $_POST["idcliente"] ?? "";
        if ($type === false || !in_array($_POST["formapago"] ?? "", ["C", "R"], true) || ($client !== "" && filter_var($client, FILTER_VALIDATE_INT, ["options"=>["min_range"=>1]]) === false)) {
            $_SESSION["venta_error"] = "Revisa los datos de la venta.";
            header("Location: index.php?route=module/ventas/crear", true, 303); exit;
        }
        $rawItems = $_POST["items"] ?? [];
        if (!is_array($rawItems)) $rawItems = [];
        try {
            $id = $this->sales->create(["idtipocomprobante"=>$type,"idcliente"=>$client,"formapago"=>$_POST["formapago"]], $rawItems, (int)$_SESSION["idusuario"]);
            $_SESSION["venta_message"] = "Venta registrada correctamente.";
            header("Location: index.php?route=module/ventas/ver&id=" . $id, true, 303); exit;
        } catch (Throwable $error) {
            $_SESSION["venta_error"] = $error instanceof RuntimeException ? $error->getMessage() : "No se pudo registrar la venta. Intentalo nuevamente.";
            header("Location: index.php?route=module/ventas/crear", true, 303); exit;
        }
    }

    public function show(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT, ["options"=>["min_range"=>1]]);
        $sale = $id !== false ? $this->sales->find($id) : null;
        if (!$sale) { http_response_code(404); echo "Venta no encontrada."; return; }
        $message = $_SESSION["venta_message"] ?? ""; unset($_SESSION["venta_message"]);
        $view = __DIR__ . "/../Views/ventas/show.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function cancel(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($id === false) {
            http_response_code(422);
            exit("Venta no válida.");
        }

        try {
            $this->sales->cancel($id, (int) $_SESSION["idusuario"]);
            $_SESSION["venta_message"] = "Venta anulada; el stock de sus productos fue restituido.";
        } catch (RuntimeException $exception) {
            $_SESSION["venta_error"] = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $_SESSION["venta_error"] = "No se pudo anular la venta. Inténtalo nuevamente.";
        }

        header("Location: index.php?route=module/ventas", true, 303);
        exit;
    }

    private function requireAccess(string $method): array
    {
        if (!isset($_SESSION["idusuario"])) { header("Location: index.php"); exit; }
        $menuOptions = $this->profiles->menuOptions((int)($_SESSION["idperfil"] ?? 0));
        foreach ($menuOptions as $option) {
            if (in_array($option["url"] ?? "", ["module/ventas", "vista/ventas.php"], true)) {
                if ($_SERVER["REQUEST_METHOD"] !== $method) { header("Allow: " . $method); http_response_code(405); exit("Metodo no permitido."); }
                $_SESSION["venta_csrf"] ??= bin2hex(random_bytes(32));
                return $menuOptions;
            }
        }
        http_response_code(403); exit("No tienes acceso al modulo de ventas.");
    }

    private function requireCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION["venta_csrf"], $token)) { http_response_code(403); exit("Solicitud invalida."); }
    }
}
