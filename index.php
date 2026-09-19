<?php
$sessionPath = __DIR__ . "/storage/sessions";

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

session_save_path($sessionPath);
session_start();

require __DIR__ . "/config/database.php";
require __DIR__ . "/app/Models/User.php";
require __DIR__ . "/app/Models/Profile.php";
require __DIR__ . "/app/Models/Dashboard.php";
require __DIR__ . "/app/Models/Category.php";
require __DIR__ . "/app/Models/Product.php";
require __DIR__ . "/app/Models/Client.php";
require __DIR__ . "/app/Controllers/AuthController.php";
require __DIR__ . "/app/Controllers/DashboardController.php";
require __DIR__ . "/app/Controllers/ModuleController.php";
require __DIR__ . "/app/Controllers/PerfilController.php";
require __DIR__ . "/app/Controllers/UsuarioController.php";
require __DIR__ . "/app/Controllers/CategoriaController.php";
require __DIR__ . "/app/Controllers/ProductoController.php";
require __DIR__ . "/app/Controllers/ClienteController.php";

$db = Database::connect();
$authController = new AuthController(new User($db));
$dashboardController = new DashboardController(new Dashboard($db), new Profile($db));
$moduleController = new ModuleController(new Profile($db));
$perfilController = new PerfilController(new Profile($db));
$usuarioController = new UsuarioController(new User($db), new Profile($db));
$categoriaController = new CategoriaController(new Category($db), new Profile($db));
$productoController = new ProductoController(new Product($db), new Category($db), new Profile($db));
$clienteController = new ClienteController(new Client($db), new Profile($db));
$route = $_GET["route"] ?? (isset($_SESSION["idusuario"]) ? "dashboard" : "login");

if ($route === "login" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $authController->login();
    exit;
}

if ($route === "logout") {
    $authController->logout();
    exit;
}

if ($route === "dashboard") {
    $dashboardController->index();
    exit;
}

$perfilRoutes = [
    "module/perfiles" => "index",
    "vista/perfiles.php" => "index",
    "module/perfiles/crear" => "create",
    "module/perfiles/editar" => "edit",
    "module/perfiles/estado" => "changeStatus"
];

if (isset($perfilRoutes[$route])) {
    $perfilController->{$perfilRoutes[$route]}();
    exit;
}

$usuarioRoutes = [
    "module/usuarios" => "index",
    "vista/usuarios.php" => "index",
    "module/usuarios/crear" => "create",
    "module/usuarios/guardar" => "store",
    "module/usuarios/editar" => "edit",
    "module/usuarios/actualizar" => "update",
    "module/usuarios/estado" => "changeStatus"
];

if (isset($usuarioRoutes[$route])) {
    $usuarioController->{$usuarioRoutes[$route]}();
    exit;
}

$categoriaRoutes = [
    "module/categorias" => "index",
    "vista/categorias.php" => "index",
    "module/categorias/crear" => "create",
    "module/categorias/guardar" => "store",
    "module/categorias/editar" => "edit",
    "module/categorias/actualizar" => "update",
    "module/categorias/estado" => "changeStatus"
];

if (isset($categoriaRoutes[$route])) {
    $categoriaController->{$categoriaRoutes[$route]}();
    exit;
}

$productoRoutes = [
    "module/productos" => "index",
    "vista/productos.php" => "index",
    "module/productos/crear" => "create",
    "module/productos/guardar" => "store",
    "module/productos/editar" => "edit",
    "module/productos/actualizar" => "update",
    "module/productos/estado" => "changeStatus"
];

if (isset($productoRoutes[$route])) {
    $productoController->{$productoRoutes[$route]}();
    exit;
}

$clienteRoutes = [
    "module/clientes" => "index",
    "vista/clientes.php" => "index",
    "module/clientes/crear" => "create",
    "module/clientes/guardar" => "store",
    "module/clientes/editar" => "edit",
    "module/clientes/actualizar" => "update",
    "module/clientes/estado" => "changeStatus"
];

if (isset($clienteRoutes[$route])) {
    $clienteController->{$clienteRoutes[$route]}();
    exit;
}

if (str_starts_with($route, "module/")) {
    $moduleController->show($route);
    exit;
}

if (str_starts_with($route, "vista/")) {
    $moduleController->showLegacy($route);
    exit;
}

$authController->showLogin();
