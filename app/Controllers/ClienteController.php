<?php
class ClienteController
{
    private Client $clients;
    private Profile $profiles;

    public function __construct(Client $clients, Profile $profiles)
    {
        $this->clients = $clients;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $clients = $this->clients->all();
        $message = $_SESSION["cliente_message"] ?? "";
        unset($_SESSION["cliente_message"]);
        $csrfToken = $_SESSION["cliente_csrf"];
        $view = __DIR__ . "/../Views/clientes/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function create(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $this->renderForm($menuOptions, $this->emptyClient(), []);
    }

    public function store(): void
    {
        $this->save(false);
    }

    public function edit(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $this->renderForm($menuOptions, $this->requireClient($_GET["id"] ?? null), []);
    }

    public function update(): void
    {
        $this->save(true);
    }

    public function changeStatus(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        $client = $this->requireClient($_POST["id"] ?? null);
        $status = $_POST["estado"] ?? null;

        if (!in_array($status, ["0", "1"], true)) {
            $this->fail(422, "Estado de cliente invalido.");
        }

        $this->clients->changeStatus((int) $client["idcliente"], (int) $status);
        $this->redirect($status === "1" ? "Cliente activado." : "Cliente desactivado.");
    }

    private function save(bool $editing): void
    {
        $menuOptions = $this->requireAccess("POST");
        $this->requireCsrf();

        $client = $editing ? $this->requireClient($_POST["id"] ?? null) : $this->emptyClient();
        $client = array_merge($client, $this->clientFromPost());
        $errors = $this->validate($client, $editing ? (int) $client["idcliente"] : 0);

        if (!$errors) {
            if ($editing) {
                $this->clients->update((int) $client["idcliente"], $client);
            } else {
                $this->clients->create($client);
            }
            $this->redirect($editing ? "Cliente actualizado." : "Cliente registrado.");
        }

        http_response_code(422);
        $this->renderForm($menuOptions, $client, $errors);
    }

    private function renderForm(array $menuOptions, array $client, array $errors): void
    {
        $editing = isset($client["idcliente"]);
        $documentTypes = $this->clients->documentTypes();
        $csrfToken = $_SESSION["cliente_csrf"];
        $view = __DIR__ . "/../Views/clientes/form.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    private function clientFromPost(): array
    {
        return [
            "nombre" => trim((string) ($_POST["nombre"] ?? "")),
            "nombre_comercial" => trim((string) ($_POST["nombre_comercial"] ?? "")),
            "razon_social" => trim((string) ($_POST["razon_social"] ?? "")),
            "idtipodocumento" => trim((string) ($_POST["idtipodocumento"] ?? "")),
            "nrodocumento" => trim((string) ($_POST["nrodocumento"] ?? "")),
            "direccion" => trim((string) ($_POST["direccion"] ?? "")),
            "departamento" => trim((string) ($_POST["departamento"] ?? "")),
            "provincia" => trim((string) ($_POST["provincia"] ?? "")),
            "distrito" => trim((string) ($_POST["distrito"] ?? ""))
        ];
    }

    private function validate(array $client, int $excludeId): array
    {
        $errors = [];

        if ($client["nombre"] === "" || mb_strlen($client["nombre"], "UTF-8") > 150) {
            $errors[] = "El nombre es obligatorio y debe tener como maximo 150 caracteres.";
        }

        if (filter_var($client["idtipodocumento"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) === false) {
            $errors[] = "Selecciona un tipo de documento valido.";
        }

        if ($client["nrodocumento"] === "" || mb_strlen($client["nrodocumento"], "UTF-8") > 20) {
            $errors[] = "El numero de documento es obligatorio y debe tener como maximo 20 caracteres.";
        }

        if ($client["nrodocumento"] !== "" && $this->clients->documentExists($client["nrodocumento"], $excludeId)) {
            $errors[] = "Ya existe un cliente con ese numero de documento.";
        }

        foreach (["nombre_comercial", "razon_social"] as $field) {
            if (mb_strlen($client[$field], "UTF-8") > 150) {
                $errors[] = "Nombre comercial y razon social deben tener como maximo 150 caracteres.";
                break;
            }
        }

        if (mb_strlen($client["direccion"], "UTF-8") > 200) {
            $errors[] = "La direccion debe tener como maximo 200 caracteres.";
        }

        foreach (["departamento", "provincia", "distrito"] as $field) {
            if (mb_strlen($client[$field], "UTF-8") > 80) {
                $errors[] = "Departamento, provincia y distrito deben tener como maximo 80 caracteres.";
                break;
            }
        }

        return $errors;
    }

    private function requireClient($id): array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $client = $id !== false ? $this->clients->findById($id) : null;

        if (!$client) {
            $this->fail(404, "Cliente no encontrado.");
        }

        return $client;
    }

    private function emptyClient(): array
    {
        return [
            "nombre" => "",
            "nombre_comercial" => "",
            "razon_social" => "",
            "idtipodocumento" => "",
            "nrodocumento" => "",
            "direccion" => "",
            "departamento" => "",
            "provincia" => "",
            "distrito" => ""
        ];
    }

    private function requireAccess(string $method): array
    {
        if (!isset($_SESSION["idusuario"])) {
            header("Location: index.php");
            exit;
        }

        $menuOptions = $this->profiles->menuOptions((int) ($_SESSION["idperfil"] ?? 0));
        $allowed = false;

        foreach ($menuOptions as $option) {
            if (in_array($option["url"] ?? "", ["module/clientes", "vista/clientes.php"], true)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $this->fail(403, "No tienes acceso a la gestion de clientes.");
        }

        if ($_SERVER["REQUEST_METHOD"] !== $method) {
            header("Allow: " . $method);
            $this->fail(405, "Metodo no permitido.");
        }

        $_SESSION["cliente_csrf"] ??= bin2hex(random_bytes(32));
        return $menuOptions;
    }

    private function requireCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? null;

        if (!is_string($token) || !hash_equals($_SESSION["cliente_csrf"], $token)) {
            $this->fail(403, "Solicitud invalida. Vuelve al formulario e intentalo nuevamente.");
        }
    }

    private function redirect(string $message): void
    {
        $_SESSION["cliente_message"] = $message;
        header("Location: index.php?route=module/clientes", true, 303);
        exit;
    }

    private function fail(int $status, string $message): void
    {
        http_response_code($status);
        header("Content-Type: text/html; charset=UTF-8");
        echo htmlspecialchars($message, ENT_QUOTES, "UTF-8");
        exit;
    }
}
