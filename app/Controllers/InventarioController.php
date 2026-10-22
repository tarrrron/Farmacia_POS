
<?php

class InventarioController
{
    private Inventario $inventario;
    private Profile $profiles;

    public function __construct(
        Inventario $inventario,
        Profile $profiles
    ) {
        $this->inventario = $inventario;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");

        $products = $this->inventario->productos();
        $resumen = $this->inventario->resumen();
        $movimientos = $this->inventario->historial();

        $message = $_SESSION["inventario_message"] ?? "";
        unset($_SESSION["inventario_message"]);

        $csrfToken = $_SESSION["inventario_csrf"];

        $view = __DIR__ . "/../Views/inventario/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function create(): void
    {
        $menuOptions = $this->requireAccess("GET");

        $products = $this->inventario->productosActivos();
        $errors = $_SESSION["inventario_errors"] ?? [];
        $old = $_SESSION["inventario_old"] ?? [];

        unset(
            $_SESSION["inventario_errors"],
            $_SESSION["inventario_old"]
        );

        $csrfToken = $_SESSION["inventario_csrf"];

        $view = __DIR__ . "/../Views/inventario/form.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function store(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();

        $idproducto = filter_var(
            $_POST["idproducto"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        $tipo = (string) ($_POST["tipo"] ?? "");
        $cantidad = trim((string) ($_POST["cantidad"] ?? ""));
        $motivo = trim((string) ($_POST["motivo"] ?? ""));

        if ($idproducto === false ||
            $idproducto === null ||
            !in_array($tipo, ["ENTRADA", "SALIDA"], true)) {
            $this->formError("Selecciona un producto y movimiento validos.");
        }

        try {
            $this->inventario->registrar(
                $idproducto,
                (int) $_SESSION["idusuario"],
                $tipo,
                $cantidad,
                $motivo
            );

            $_SESSION["inventario_message"] =
                "Movimiento registrado correctamente.";

            header(
                "Location: index.php?route=module/inventario",
                true,
                303
            );
            exit;

        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->formError($e->getMessage());

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->formError("No se pudo registrar el movimiento.");
        }
    }

    private function requireAccess(string $method): array
    {
        if (!isset($_SESSION["idusuario"])) {
            header("Location: index.php");
            exit;
        }

        $menuOptions = $this->profiles->menuOptions(
            (int) ($_SESSION["idperfil"] ?? 0)
        );

        $allowed = false;

        foreach ($menuOptions as $option) {
            if (in_array(
                $option["url"] ?? "",
                ["module/inventario", "vista/inventario.php"],
                true
            )) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $this->fail(403, "No tienes acceso al inventario.");
        }

        if ($_SERVER["REQUEST_METHOD"] !== $method) {
            header("Allow: " . $method);
            $this->fail(405, "Metodo no permitido.");
        }

        $_SESSION["inventario_csrf"] ??= bin2hex(random_bytes(32));

        return $menuOptions;
    }

    private function requireCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? null;

        if (!is_string($token) ||
            !hash_equals($_SESSION["inventario_csrf"], $token)) {
            $this->fail(403, "Solicitud invalida.");
        }
    }

    private function formError(string $message): void
    {
        $_SESSION["inventario_errors"] = [$message];

        $_SESSION["inventario_old"] = [
            "idproducto" => $_POST["idproducto"] ?? "",
            "tipo" => $_POST["tipo"] ?? "",
            "cantidad" => $_POST["cantidad"] ?? "",
            "motivo" => $_POST["motivo"] ?? ""
        ];

        header(
            "Location: index.php?route=module/inventario/crear",
            true,
            303
        );
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
