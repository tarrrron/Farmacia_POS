<?php
class CategoriaController
{
    private Category $categories;
    private Profile $profiles;

    public function __construct(Category $categories, Profile $profiles)
    {
        $this->categories = $categories;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $categories = $this->categories->all();
        $message = $_SESSION["categoria_message"] ?? "";
        unset($_SESSION["categoria_message"]);
        $csrfToken = $_SESSION["categoria_csrf"];
        $view = __DIR__ . "/../Views/categorias/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function create(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $this->renderForm($menuOptions, ["nombre" => ""], []);
    }

    public function store(): void
    {
        $this->save(false);
    }

    public function edit(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $this->renderForm($menuOptions, $this->requireCategory($_GET["id"] ?? null), []);
    }

    public function update(): void
    {
        $this->save(true);
    }

    public function changeStatus(): void
    {
        $this->requireAccess("POST");
        $this->requireCsrf();
        $category = $this->requireCategory($_POST["id"] ?? null);
        $status = $_POST["estado"] ?? null;

        if (!in_array($status, ["0", "1"], true)) {
            $this->fail(422, "Estado de categoria invalido.");
        }

        $this->categories->changeStatus((int) $category["idcategoria"], (int) $status);
        $this->redirect($status === "1" ? "Categoria activada." : "Categoria desactivada.");
    }

    private function save(bool $editing): void
    {
        $menuOptions = $this->requireAccess("POST");
        $this->requireCsrf();

        $category = $editing ? $this->requireCategory($_POST["id"] ?? null) : [];
        $category["nombre"] = trim((string) ($_POST["nombre"] ?? ""));
        $errors = [];

        if ($category["nombre"] === "" || mb_strlen($category["nombre"], "UTF-8") > 100) {
            $errors[] = "El nombre es obligatorio y debe tener como maximo 100 caracteres.";
        }

        if ($category["nombre"] !== "" && $this->categories->nameExists($category["nombre"], (int) ($category["idcategoria"] ?? 0))) {
            $errors[] = "Ya existe una categoria con ese nombre.";
        }

        if (!$errors) {
            if ($editing) {
                $this->categories->update((int) $category["idcategoria"], $category["nombre"]);
            } else {
                $this->categories->create($category["nombre"]);
            }
            $this->redirect($editing ? "Categoria actualizada." : "Categoria registrada.");
        }

        http_response_code(422);
        $this->renderForm($menuOptions, $category, $errors);
    }

    private function renderForm(array $menuOptions, array $category, array $errors): void
    {
        $editing = isset($category["idcategoria"]);
        $csrfToken = $_SESSION["categoria_csrf"];
        $view = __DIR__ . "/../Views/categorias/form.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    private function requireCategory($id): array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $category = $id !== false ? $this->categories->findById($id) : null;

        if (!$category) {
            $this->fail(404, "Categoria no encontrada.");
        }

        return $category;
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
            if (in_array($option["url"] ?? "", ["module/categorias", "vista/categorias.php"], true)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $this->fail(403, "No tienes acceso a la gestion de categorias.");
        }

        if ($_SERVER["REQUEST_METHOD"] !== $method) {
            header("Allow: " . $method);
            $this->fail(405, "Metodo no permitido.");
        }

        $_SESSION["categoria_csrf"] ??= bin2hex(random_bytes(32));
        return $menuOptions;
    }

    private function requireCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? null;

        if (!is_string($token) || !hash_equals($_SESSION["categoria_csrf"], $token)) {
            $this->fail(403, "Solicitud invalida. Vuelve al formulario e intentalo nuevamente.");
        }
    }

    private function redirect(string $message): void
    {
        $_SESSION["categoria_message"] = $message;
        header("Location: index.php?route=module/categorias", true, 303);
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
