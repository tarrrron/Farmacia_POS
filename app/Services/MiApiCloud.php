<?php
class MiApiCloud
{
    private string $token;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . "/config/miapi.php";
        $this->token = trim((string) ($config["token"] ?? ""));
    }

    public function lookup(string $documentType, string $number): array
    {
        $documentType = strtoupper(trim($documentType));
        $number = trim($number);
        $expectedLength = $documentType === "DNI" ? 8 : ($documentType === "RUC" ? 11 : 0);
        if ($expectedLength === 0 || !preg_match('/^\d{' . $expectedLength . '}$/D', $number)) {
            throw new InvalidArgumentException($documentType === "DNI"
                ? "Ingresa un DNI válido de 8 dígitos."
                : "Ingresa un RUC válido de 11 dígitos.");
        }
        if ($this->token === "") {
            throw new RuntimeException("La consulta de documentos no está configurada. Añade una clave vigente de MiAPI Cloud en config/miapi.local.php.");
        }
        if (!function_exists("curl_init")) {
            throw new RuntimeException("El servidor no tiene habilitada la extensión cURL para consultar documentos.");
        }

        $url = "https://miapi.cloud/v1/" . strtolower($documentType) . "/" . rawurlencode($number);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $this->token, "Accept: application/json"],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_errno($curl);
        curl_close($curl);

        if ($body === false || $curlError !== 0) {
            throw new RuntimeException("No se pudo conectar con MiAPI Cloud. Inténtalo nuevamente.");
        }
        if ($status === 401) {
            throw new RuntimeException("La clave de MiAPI Cloud no es válida o venció. Actualízala en config/miapi.local.php.");
        }
        if ($status === 404) {
            throw new RuntimeException("No se encontraron datos para ese documento.");
        }
        if ($status === 422 || $status === 400) {
            throw new RuntimeException("MiAPI Cloud rechazó el número de documento. Verifica los dígitos.");
        }
        if ($status === 429) {
            throw new RuntimeException("Se alcanzó el límite de consultas de MiAPI Cloud. Espera un momento e inténtalo otra vez.");
        }
        if ($status >= 500) {
            throw new RuntimeException("MiAPI Cloud no está disponible temporalmente. Inténtalo nuevamente en unos minutos.");
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("No se pudo completar la consulta del documento.");
        }

        $response = json_decode((string) $body, true);
        if (!is_array($response) || ($response["success"] ?? false) !== true) {
            throw new RuntimeException("MiAPI Cloud no devolvió información para ese documento.");
        }
        $data = $response["datos"] ?? $response["data"] ?? null;
        if (!is_array($data)) {
            throw new RuntimeException("MiAPI Cloud devolvió una respuesta sin datos utilizables.");
        }

        $domicile = $data["domiciliado"] ?? [];
        $location = $data["ubicacion"] ?? [];
        $address = trim((string) ($location["direccion"] ?? ""));
        if ($address === "") $address = trim((string) ($domicile["direccion"] ?? ""));
        if ($address === "") $address = trim((string) ($data["direccion"] ?? ""));

        if ($documentType === "DNI") {
            $name = trim(implode(" ", array_filter([
                $data["nombres"] ?? "",
                $data["ape_paterno"] ?? "",
                $data["ape_materno"] ?? ""
            ], static fn($part) => trim((string) $part) !== "")));
            if ($name === "") {
                $name = trim((string) ($data["nombre_completo"] ?? ""));
            }
            $businessName = "";
        } else {
            $name = trim((string) ($data["razon_social"] ?? $data["nombre_completo"] ?? ""));
            $businessName = $name;
        }
        if ($name === "") {
            throw new RuntimeException("La consulta no devolvió el nombre asociado al documento.");
        }

        return [
            "tipo" => $documentType,
            "numero" => $number,
            "nombre" => $name,
            "razon_social" => $businessName,
            "direccion" => $address,
            "departamento" => trim((string) ($location["departamento"] ?? $domicile["departamento"] ?? "")),
            "provincia" => trim((string) ($location["provincia"] ?? $domicile["provincia"] ?? "")),
            "distrito" => trim((string) ($location["distrito"] ?? $domicile["distrito"] ?? ""))
        ];
    }
}
