<?php
$localConfig = __DIR__ . "/miapi.local.php";
$local = is_file($localConfig) ? require $localConfig : [];

return [
    "token" => trim((string) ($local["token"] ?? (getenv("MIAPI_CLOUD_TOKEN") ?: "")))
];
