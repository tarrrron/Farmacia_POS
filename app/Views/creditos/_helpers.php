<?php
// Funciones de apoyo para las vistas del módulo de créditos.
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
$creditInvoice = static fn(array $row) => ($row["serie"] ?? "") . "-" . str_pad((string) ($row["correlativo"] ?? ""), 8, "0", STR_PAD_LEFT);
$creditBadge = static function (array $credit): array {
    if ((int) $credit["estado"] === 2) {
        return ["is-active", "Pagado"];
    }
    if ((int) $credit["estado"] === 0) {
        return ["is-muted", "Anulado"];
    }
    if ((int) $credit["vencido"] === 1) {
        return ["is-danger", "Vencido"];
    }
    return ["is-warning", "Pendiente"];
};
$creditDays = static function (array $credit): string {
    if ((int) $credit["estado"] !== 1 || $credit["dias"] === null) {
        return "—";
    }
    $days = (int) $credit["dias"];
    if ($days < 0) {
        return "Vencido hace " . abs($days) . ($days === -1 ? " día" : " días");
    }
    if ($days === 0) {
        return "Vence hoy";
    }
    return "Vence en " . $days . ($days === 1 ? " día" : " días");
};
