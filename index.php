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
require __DIR__ . "/app/Controllers/AuthController.php";
require __DIR__ . "/app/Controllers/DashboardController.php";
require __DIR__ . "/app/Controllers/ModuleController.php";

$db = Database::connect();
$authController = new AuthController(new User($db));
$dashboardController = new DashboardController(new Dashboard($db), new Profile($db));
$moduleController = new ModuleController(new Profile($db));
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

if (str_starts_with($route, "module/")) {
    $moduleController->show($route);
    exit;
}

if (str_starts_with($route, "vista/")) {
    $moduleController->showLegacy($route);
    exit;
}

$authController->showLogin();
