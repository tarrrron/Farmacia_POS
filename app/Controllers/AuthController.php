<?php
class AuthController
{
    private User $users;

    public function __construct(User $users)
    {
        $this->users = $users;
    }

    public function showLogin(?string $error = null): void
    {
        require __DIR__ . "/../Views/auth/login.php";
    }

    public function login(): void
    {
        $username = trim($_POST["usuario"] ?? "");
        $password = trim($_POST["clave"] ?? "");

        if ($username === "" || $password === "") {
            $this->showLogin("Ingresa usuario y clave.");
            return;
        }

        $user = $this->users->findActiveByCredentials($username, $password);

        if (!$user) {
            $this->showLogin("Usuario o clave incorrecta.");
            return;
        }

        $_SESSION["idusuario"] = $user["idusuario"];
        $_SESSION["nombre"] = $user["nombre"];
        $_SESSION["usuario"] = $user["usuario"];
        $_SESSION["idperfil"] = $user["idperfil"];

        header("Location: index.php?route=dashboard");
        exit;
    }

    public function logout(): void
    {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}
