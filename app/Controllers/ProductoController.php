<?php
class ProductoController
{
    private Product $products;
    private Category $categories;
    private Profile $profiles;

    public function __construct(Product $products, Category $categories, Profile $profiles)
    {
        $this->products = $products;
        $this->categories = $categories;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $products = $this->products->all();
        $message = $_SESSION["producto_message"] ?? "";
        unset($_SESSION["producto_message"]);
        $csrfToken = $_SESSION["producto_csrf"];
        $view = __DIR__ . "/../Views/productos/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function create(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $this->renderForm($menuOptions, $this->emptyProduct(), []);
    }

    public function store(): void
    {
        $this->save(false);
    }

    public function edit(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $this->renderForm($menuOptions, $this->requireProduct($_GET["id"] ?? null), []);
    }

    public function update(): void
    {
        $this->save(true);
    }

    public function changeStatus(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        $product = $this->requireProduct($_POST["id"] ?? null);
        $status = $_POST["estado"] ?? null;

        if (!in_array($status, ["0", "1"], true)) {
            $this->fail(422, "Estado de producto invalido.");
        }

        $this->products->changeStatus((int) $product["idproducto"], (int) $status);
        $this->redirect($status === "1" ? "Producto activado." : "Producto desactivado.");
    }

    private function save(bool $editing): void
    {
        $menuOptions = $this->requireAccess("POST");
        $this->requireCsrf();

        $product = $editing ? $this->requireProduct($_POST["id"] ?? null) : $this->emptyProduct();
        $product = array_merge($product, $this->productFromPost());
        $errors = $this->validate($product, $editing ? (int) $product["idproducto"] : 0);

        if (!$errors) {
            if ($editing) {
                $this->products->update((int) $product["idproducto"], $product);
            } else {
                $this->products->create($product);
            }
            $this->redirect($editing ? "Producto actualizado." : "Producto registrado.");
        }

        http_response_code(422);
        $this->renderForm($menuOptions, $product, $errors);
    }

    private function renderForm(array $menuOptions, array $product, array $errors): void
    {
        $editing = isset($product["idproducto"]);
        $categories = $this->categories->active();
        $units = $this->products->units();
        $affectations = $this->products->taxAffectations();
        $csrfToken = $_SESSION["producto_csrf"];
        $view = __DIR__ . "/../Views/productos/form.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    private function productFromPost(): array
    {
        return [
            "nombre" => trim((string) ($_POST["nombre"] ?? "")),
            "codigobarra" => trim((string) ($_POST["codigobarra"] ?? "")),
            "pventa" => trim((string) ($_POST["pventa"] ?? "")),
            "pcompra" => trim((string) ($_POST["pcompra"] ?? "")),
            "stock" => trim((string) ($_POST["stock"] ?? "")),
            "stockseguridad" => trim((string) ($_POST["stockseguridad"] ?? "")),
            "idunidad" => trim((string) ($_POST["idunidad"] ?? "")),
            "idcategoria" => trim((string) ($_POST["idcategoria"] ?? "")),
            "idafectacion" => trim((string) ($_POST["idafectacion"] ?? ""))
        ];
    }

    private function validate(array $product, int $excludeId): array
    {
        $errors = [];

        if ($product["nombre"] === "" || mb_strlen($product["nombre"], "UTF-8") > 150) {
            $errors[] = "El nombre es obligatorio y debe tener como maximo 150 caracteres.";
        }

        if ($product["nombre"] !== "" && $this->products->nameExists($product["nombre"], $excludeId)) {
            $errors[] = "Ya existe un producto con ese nombre.";
        }

        if ($this->products->barcodeExists($product["codigobarra"], $excludeId)) {
            $errors[] = "Ya existe un producto con ese codigo de barra.";
        }

        foreach (["pventa", "pcompra", "stock", "stockseguridad"] as $field) {
            if (!is_numeric($product[$field]) || (float) $product[$field] < 0) {
                $errors[] = "Los precios y cantidades deben ser numeros mayores o iguales a cero.";
                break;
            }
        }

        foreach (["idunidad", "idcategoria", "idafectacion"] as $field) {
            if (filter_var($product[$field], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) === false) {
                $errors[] = "Selecciona unidad, categoria y afectacion validas.";
                break;
            }
        }

        return $errors;
    }

    private function requireProduct($id): array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $product = $id !== false ? $this->products->findById($id) : null;

        if (!$product) {
            $this->fail(404, "Producto no encontrado.");
        }

        return $product;
    }

    private function emptyProduct(): array
    {
        return [
            "nombre" => "",
            "codigobarra" => "",
            "pventa" => "0.00",
            "pcompra" => "0.00",
            "stock" => "0.00",
            "stockseguridad" => "0.00",
            "idunidad" => "",
            "idcategoria" => "",
            "idafectacion" => "1"
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
            if (in_array($option["url"] ?? "", ["module/productos", "vista/productos.php"], true)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $this->fail(403, "No tienes acceso a la gestion de productos.");
        }

        if ($_SERVER["REQUEST_METHOD"] !== $method) {
            header("Allow: " . $method);
            $this->fail(405, "Metodo no permitido.");
        }

        $_SESSION["producto_csrf"] ??= bin2hex(random_bytes(32));
        return $menuOptions;
    }

    private function requireCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? null;

        if (!is_string($token) || !hash_equals($_SESSION["producto_csrf"], $token)) {
            $this->fail(403, "Solicitud invalida. Vuelve al formulario e intentalo nuevamente.");
        }
    }

    private function redirect(string $message): void
    {
        $_SESSION["producto_message"] = $message;
        header("Location: index.php?route=module/productos", true, 303);
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
