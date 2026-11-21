<?php
$config = [
    "business_name" => "FULL HEART",
    "ruc" => "67676767",
    "address" => "AV. Utp 123",
];

$localConfigPath = __DIR__ . "/receipt.local.php";
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $config = array_replace($config, $localConfig);
    }
}

return $config;
