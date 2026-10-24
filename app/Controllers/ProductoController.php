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
        $action = $_POST["accion"] ?? ($status === "1" ? "activar" : "eliminar");

        if (!in_array($status, ["0", "1"], true) ||
            !in_array($action, ["activar", "desactivar", "eliminar"], true) ||
            ($action === "activar" && $status !== "1") ||
            ($action !== "activar" && $status !== "0")) {
            $this->fail(422, "Estado de producto invalido.");
        }

        $this->products->changeStatus((int) $product["idproducto"], (int) $status);
        $message = $action === "activar" ? "Producto activado." : ($action === "desactivar" ? "Producto desactivado." : "Producto eliminado y marcado como inactivo.");
        $this->redirect($message);
    }

    private function save(bool $editing): void
    {
        $menuOptions = $this->requireAccess("POST");
        $this->requireCsrf();

        $product = $editing ? $this->requireProduct($_POST["id"] ?? null) : $this->emptyProduct();
        $product = array_merge($product, $this->productFromPost());
        $existingImagePath = $product["urlimagen"] ?? null;
        $errors = $this->validate($product, $editing ? (int) $product["idproducto"] : 0);

        $imagePath = null;
        if (!$errors) {
            try {
                $imagePath = $this->storeImage($_FILES["imagen"] ?? null);
                if ($imagePath !== null) {
                    $product["urlimagen"] = $imagePath;
                }
            } catch (InvalidArgumentException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if (!$errors) {
            try {
                if ($editing) {
                    $this->products->update((int) $product["idproducto"], $product);
                } else {
                    $this->products->create($product);
                }
                $this->redirect($editing ? "Producto actualizado." : "Producto registrado.");
            } catch (InvalidArgumentException $exception) {
                if ($imagePath !== null) {
                    @unlink(dirname(__DIR__, 2) . "/" . $imagePath);
                    $product["urlimagen"] = $existingImagePath;
                }
                $errors[] = $exception->getMessage();
            }
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

    private function storeImage(?array $file): ?string
    {
        if ($file === null || ($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file["tmp_name"] ?? "")) {
            throw new InvalidArgumentException("No se pudo recibir la imagen. Inténtalo nuevamente.");
        }

        if ((int) ($file["size"] ?? 0) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException("La imagen no puede superar los 5 MB.");
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);
        $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
        if (!isset($extensions[$mime]) || @getimagesize($file["tmp_name"]) === false) {
            throw new InvalidArgumentException("Usa una imagen JPG, PNG o WEBP válida.");
        }

        $relativeDirectory = "public/uploads/productos";
        $directory = dirname(__DIR__, 2) . "/" . $relativeDirectory;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new InvalidArgumentException("No se pudo preparar la carpeta para guardar imágenes.");
        }

        $filename = bin2hex(random_bytes(16)) . "." . $extensions[$mime];
        if (!move_uploaded_file($file["tmp_name"], $directory . "/" . $filename)) {
            throw new InvalidArgumentException("No se pudo guardar la imagen del producto.");
        }

        return $relativeDirectory . "/" . $filename;
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

        $categoryId = filter_var($product["idcategoria"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $category = $categoryId !== false ? $this->categories->findById($categoryId) : null;
        if (!$category || (int) $category["estado"] !== 1) {
            $errors[] = "Selecciona una categoría activa para el producto.";
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
            "idafectacion" => "1",
            "urlimagen" => null
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
