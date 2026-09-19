<?php
class PerfilController
{
    private Profile $profiles;

    public function __construct(Profile $profiles)
    {
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess(["GET"]);
        $profiles = $this->profiles->all();
        $message = $_SESSION["perfil_message"] ?? "";
        unset($_SESSION["perfil_message"]);
        $csrfToken = $_SESSION["perfil_csrf"];
        $view = __DIR__ . "/../Views/perfiles/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function create(): void
    {
        $this->form(false);
    }

    public function edit(): void
    {
        $this->form(true);
    }

    private function form(bool $editing): void
    {
        $menuOptions = $this->requireAccess(["GET", "POST"]);
        $profile = $editing ? $this->requireProfile($_GET["id"] ?? null) : ["idperfil" => 0, "nombre" => ""];
        $accessOptions = $this->profiles->optionsWithAccess((int) ($profile["idperfil"] ?? 0));
        $error = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->requireCsrf();
            $name = $_POST["nombre"] ?? "";
            $profile["nombre"] = is_string($name) ? trim($name) : "";
            $selectedOptions = $_POST["opciones"] ?? [];
            $selectedOptions = is_array($selectedOptions) ? $selectedOptions : [];

            if ($profile["nombre"] === "" || mb_strlen($profile["nombre"], "UTF-8") > 50) {
                http_response_code(422);
                $error = "El nombre es obligatorio y debe tener como máximo 50 caracteres.";
            } else {
                try {
                    if ($editing) {
                        $this->profiles->update((int) $profile["idperfil"], $profile["nombre"]);
                        $profileId = (int) $profile["idperfil"];
                    } else {
                        $profileId = $this->profiles->create($profile["nombre"]);
                    }
                    $this->profiles->syncAccess($profileId, $selectedOptions);
                    $this->redirect($editing ? "Perfil actualizado." : "Perfil creado.");
                } catch (PDOException $exception) {
                    http_response_code(500);
                    $error = "No se pudo guardar el perfil. Inténtalo nuevamente.";
                }
            }

            $selectedMap = array_flip(array_map("intval", $selectedOptions));
            foreach ($accessOptions as &$option) {
                $option["permitido"] = isset($selectedMap[(int) $option["idopcion"]]) ? 1 : 0;
            }
            unset($option);
        }

        $csrfToken = $_SESSION["perfil_csrf"];
        $view = __DIR__ . "/../Views/perfiles/form.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function changeStatus(): void
    {
        $this->requireAccess(["POST"]);
        $this->requireCsrf();
        $profile = $this->requireProfile($_POST["id"] ?? null);
        $status = $_POST["estado"] ?? null;
        if (!in_array($status, ["0", "1"], true)) {
            $this->fail(422, "Estado de perfil inválido.");
        }

        try {
            $this->profiles->changeStatus((int) $profile["idperfil"], (int) $status);
        } catch (PDOException $exception) {
            $this->fail(500, "No se pudo cambiar el estado del perfil. Inténtalo nuevamente.");
        }
        $this->redirect($status === "1" ? "Perfil activado." : "Perfil desactivado.");
    }

    private function requireAccess(array $methods): array
    {
        if (!isset($_SESSION["idusuario"])) {
            header("Location: index.php");
            exit;
        }

        $menuOptions = $this->profiles->menuOptions((int) ($_SESSION["idperfil"] ?? 0));
        $allowed = false;
        foreach ($menuOptions as $option) {
            if (in_array($option["url"] ?? "", ["module/perfiles", "vista/perfiles.php"], true)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $this->fail(403, "No tienes acceso a la gestión de perfiles.");
        }
        if (!in_array($_SERVER["REQUEST_METHOD"], $methods, true)) {
            header("Allow: " . implode(", ", $methods));
            $this->fail(405, "Método no permitido.");
        }

        $_SESSION["perfil_csrf"] ??= bin2hex(random_bytes(32));
        return $menuOptions;
    }

    private function requireProfile($id): array
    {
        $id = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $profile = $id !== false ? $this->profiles->findById($id) : null;
        if (!$profile) {
            $this->fail(404, "Perfil no encontrado.");
        }
        return $profile;
    }

    private function requireCsrf(): void
    {
        $token = $_POST["csrf_token"] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION["perfil_csrf"], $token)) {
            $this->fail(403, "Solicitud inválida. Vuelve al listado e inténtalo nuevamente.");
        }
    }

    private function redirect(string $message): void
    {
        $_SESSION["perfil_message"] = $message;
        header("Location: index.php?route=module/perfiles", true, 303);
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
