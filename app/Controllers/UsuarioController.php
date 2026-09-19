<?php
class UsuarioController
{
    private User $users;
    private Profile $profiles;

    public function __construct(User $users, Profile $profiles)
    {
        $this->users = $users;
        $this->profiles = $profiles;
    }

    public function index(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $users = $this->users->all();
        $message = $_SESSION["usuario_message"] ?? "";
        unset($_SESSION["usuario_message"]);
        $view = __DIR__ . "/../Views/usuarios/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }

    public function create(): void
    {
        $menuOptions = $this->requireAccess("GET");
        $this->renderForm($menuOptions, ["nombre" => "", "usuario" => "", "idperfil" => ""], []);
    }

    public function store(): void
    {
        $menuOptions = $this->requireAccess("POST");
        $token = $_POST["csrf_token"] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION["usuario_csrf"], $token)) {
            $this->fail(403, "Solicitud inválida. Vuelve al formulario e inténtalo nuevamente.");
        }

        $user = [];
        foreach (["nombre", "usuario", "idperfil"] as $field) {
            $value = $_POST[$field] ?? "";
            $user[$field] = is_string($value) ? trim($value) : "";
        }
        // El login también aplica trim antes de comprobar la contraseña.
        $password = is_string($_POST["clave"] ?? null) ? trim($_POST["clave"]) : "";
        $errors = [];
        if ($user["nombre"] === "" || mb_strlen($user["nombre"], "UTF-8") > 100) {
            $errors[] = "El nombre es obligatorio y debe tener como máximo 100 caracteres.";
        }
        if ($user["usuario"] === "" || mb_strlen($user["usuario"], "UTF-8") > 50) {
            $errors[] = "El usuario es obligatorio y debe tener como máximo 50 caracteres.";
        }
        if ($password === "") {
            $errors[] = "La contraseña es obligatoria.";
        }
        $profileId = filter_var($user["idperfil"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $activeIds = array_map("intval", array_column($this->users->activeProfiles(), "idperfil"));
        if ($profileId === false || !in_array($profileId, $activeIds, true)) {
            $errors[] = "Selecciona un perfil activo válido.";
        }
        if ($user["usuario"] !== "" && $this->users->usernameExists($user["usuario"])) {
            $errors[] = "El usuario ya existe. Ingresa otro nombre de usuario.";
        }

        if (!$errors) {
            try {
                $this->users->create($user["nombre"], $user["usuario"], $password, $profileId);
                $_SESSION["usuario_message"] = "Usuario registrado correctamente.";
                header("Location: index.php?route=module/usuarios", true, 303);
                exit;
            } catch (InvalidArgumentException $exception) {
                $errors[] = $exception->getMessage();
            } catch (PDOException $exception) {
                // El índice único también protege contra registros simultáneos.
                if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                    $errors[] = "El usuario ya existe. Ingresa otro nombre de usuario.";
                } else {
                    http_response_code(500);
                    $errors[] = "No se pudo guardar el usuario. Inténtalo nuevamente.";
                }
            }
        }
        if (http_response_code() !== 500) {
            http_response_code(422);
        }
        $this->renderForm($menuOptions, $user, $errors);
    }

    private function renderForm(array $menuOptions, array $user, array $errors): void
    {
        $profiles = $this->users->activeProfiles();
        $csrfToken = $_SESSION["usuario_csrf"];
        $view = __DIR__ . "/../Views/usuarios/form.php";
        require __DIR__ . "/../Views/layout/app.php";
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
            if (in_array($option["url"] ?? "", ["module/usuarios", "vista/usuarios.php"], true)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $this->fail(403, "No tienes acceso a la gestión de usuarios.");
        }
        if ($_SERVER["REQUEST_METHOD"] !== $method) {
            header("Allow: " . $method);
            $this->fail(405, "Método no permitido.");
        }
        $_SESSION["usuario_csrf"] ??= bin2hex(random_bytes(32));
        return $menuOptions;
    }

    private function fail(int $status, string $message): void
    {
        http_response_code($status);
        header("Content-Type: text/html; charset=UTF-8");
        echo htmlspecialchars($message, ENT_QUOTES, "UTF-8");
        exit;
    }
}
