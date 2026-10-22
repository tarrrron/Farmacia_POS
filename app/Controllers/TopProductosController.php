<?php
require_once 'app/Models/Product.php';
require_once 'app/Models/Profile.php';

class TopProductosController {

    public function index() {
        global $db; 
        
    
        $mesSeleccionado = isset($_GET['mes']) ? $_GET['mes'] : '';
        
        $productModel = new Product($db);
        $topProductos = $productModel->getTopProductos($mesSeleccionado);
        
        $profileModel = new Profile($db);
        $menuOptions = $profileModel->menuOptions((int) $_SESSION["idperfil"]);
        
        $view = __DIR__ . "/../Views/top_productos/index.php";
        require __DIR__ . "/../Views/layout/app.php";
    }
}
?>