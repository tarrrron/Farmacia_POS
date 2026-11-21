<?php
class VentaController
{
    private Sale $sales;
    private Profile $profiles;
    private MiApiCloud $miApi;

    public function __construct(Sale $sales, Profile $profiles, MiApiCloud $miApi)
    {
        $this->sales = $sales;
        $this->profiles = $profiles;
        $this->miApi = $miApi;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $from = $this->dateFromQuery("desde");
        $to = $this->dateFromQuery("hasta");
        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }
        $sales = $this->sales->all($from, $to);
        $salesTotal = 0.0;
        foreach ($sales as $row) {
            if ((int) $row["estado"] === 1) {
                $salesTotal += (float) $row["total"];
            }
        }
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
        $documentTypes = $this->sales->documentTypes();
        $clientDocumentTypes = $this->sales->clientDocumentTypes();
        $minimumCreditAmount = Sale::MINIMUM_CREDIT_AMOUNT;
        $maximumCreditInstallments = Sale::MAX_CREDIT_INSTALLMENTS;
        $csrfToken = $_SESSION["venta_csrf"];
        $error = $_SESSION["venta_error"] ?? ""; unset($_SESSION["venta_error"]);
        $view = __DIR__ . "/../Views/ventas/create.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function lookupDocument(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        header("Content-Type: application/json; charset=utf-8");
        try {
            $client = $this->miApi->lookup((string) ($_POST["tipo"] ?? ""), (string) ($_POST["numero"] ?? ""));
            echo json_encode(["success" => true, "cliente" => $client], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (InvalidArgumentException $error) {
            http_response_code(422);
            echo json_encode(["success" => false, "message" => $error->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (RuntimeException $error) {
            http_response_code(503);
            echo json_encode(["success" => false, "message" => $error->getMessage()], JSON_UNESCAPED_UNICODE);
        }
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
        $dueDate = null;
        $installments = 1;
        if ($_POST["formapago"] === "R") {
            $installmentsInput = filter_var($_POST["numero_cuotas"] ?? null, FILTER_VALIDATE_INT);
            if ($installmentsInput === false || $installmentsInput < 1 || $installmentsInput > Sale::MAX_CREDIT_INSTALLMENTS) {
                $_SESSION["venta_error"] = "Selecciona entre 1 y " . Sale::MAX_CREDIT_INSTALLMENTS . " cuotas mensuales.";
                header("Location: index.php?route=module/ventas/crear", true, 303); exit;
            }
            $installments = $installmentsInput;
            $dueInput = trim((string)($_POST["vencimiento"] ?? ""));
            $parsed = $dueInput === "" ? false : DateTime::createFromFormat("!Y-m-d", $dueInput);
            if ($dueInput === "") {
                $dueDate = (new DateTime("+30 days"))->format("Y-m-d");
            } elseif (!$parsed || $parsed->format("Y-m-d") !== $dueInput || $parsed < new DateTime("today")) {
                $_SESSION["venta_error"] = "La fecha de vencimiento del crédito no es válida.";
                header("Location: index.php?route=module/ventas/crear", true, 303); exit;
            } else {
                $dueDate = $dueInput;
            }
        }
        $rawItems = $_POST["items"] ?? [];
        if (!is_array($rawItems)) $rawItems = [];
        $receivedInput = $_POST["monto_recibido"] ?? "";
        if (!is_string($receivedInput)) {
            $_SESSION["venta_error"] = "Ingresa un monto recibido válido.";
            header("Location: index.php?route=module/ventas/crear", true, 303); exit;
        }
        try {
            $newClient = null;
            $clientInput = $_POST["cliente"] ?? [];
            if (!is_array($clientInput)) $clientInput = [];
            $clientId = trim((string) $client);
            $clientHasDetails = false;
            foreach (["nrodocumento", "nombre", "razon_social", "direccion"] as $field) {
                if (trim((string) ($clientInput[$field] ?? "")) !== "") $clientHasDetails = true;
            }
            if ($clientId === "" && $clientHasDetails) {
                $newClient = $this->normalizeNewClient($clientInput);
            }
            $id = $this->sales->create(["idtipocomprobante"=>$type,"idcliente"=>$client,"cliente_nuevo"=>$newClient,
                "formapago"=>$_POST["formapago"],"monto_recibido"=>trim($receivedInput),
                "vencimiento"=>$dueDate,"numero_cuotas"=>$installments], $rawItems, (int)$_SESSION["idusuario"]);
            $_SESSION["venta_message"] = "Venta registrada correctamente.";
            header("Location: index.php?route=module/ventas/ver&id=" . $id, true, 303); exit;
        } catch (Throwable $error) {
            $_SESSION["venta_error"] = $error instanceof RuntimeException ? $error->getMessage() : "No se pudo registrar la venta. Intentalo nuevamente.";
            header("Location: index.php?route=module/ventas/crear", true, 303); exit;
        }
    }

    private function normalizeNewClient(array $input): array
    {
        $documentTypeId = filter_var($input["idtipodocumento"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $document = trim((string) ($input["nrodocumento"] ?? ""));
        $name = trim((string) ($input["nombre"] ?? ""));
        $address = trim((string) ($input["direccion"] ?? ""));
        $businessName = trim((string) ($input["razon_social"] ?? ""));
        if ($documentTypeId === false || strlen($document) > 20 || $name === "" || mb_strlen($name) > 150 || mb_strlen($address) > 200 || mb_strlen($businessName) > 150) {
            throw new RuntimeException("Completa correctamente los datos del cliente antes de registrar la venta.");
        }
        $types = $this->sales->clientDocumentTypes();
        $allowedType = null;
        foreach ($types as $type) {
            if ((int) $type["idtipodocumento"] === $documentTypeId) $allowedType = strtoupper((string) $type["nombre"]);
        }
        $length = $allowedType === "DNI" ? 8 : ($allowedType === "RUC" ? 11 : 0);
        if ($length === 0 || !preg_match('/^\d{' . $length . '}$/D', $document)) {
            throw new RuntimeException("El documento del cliente debe ser un DNI de 8 dígitos o un RUC de 11 dígitos.");
        }
        if ($allowedType === "RUC" && $businessName === "") $businessName = $name;
        return ["idtipodocumento" => $documentTypeId, "nrodocumento" => $document, "nombre" => $name,
            "razon_social" => $businessName, "direccion" => $address,
            "departamento" => trim((string) ($input["departamento"] ?? "")),
            "provincia" => trim((string) ($input["provincia"] ?? "")),
            "distrito" => trim((string) ($input["distrito"] ?? ""))];
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

    public function ticket(): void
    {
        $this->requireAccess("GET");
        $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT, ["options"=>["min_range"=>1]]);
        $sale = $id !== false ? $this->sales->find($id) : null;
        if (!$sale) { http_response_code(404); echo "Venta no encontrada."; return; }
        $receiptConfig = require __DIR__ . "/../../config/receipt.php";
        $view = __DIR__ . "/../Views/ventas/ticket.php";
        require $view;
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

    private function dateFromQuery(string $key): ?string
    {
        $value = trim((string) ($_GET[$key] ?? ""));
        $date = $value !== "" ? DateTime::createFromFormat("!Y-m-d", $value) : false;
        return $date && $date->format("Y-m-d") === $value ? $value : null;
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
