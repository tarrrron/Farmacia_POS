<?php
class Dashboard
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function summary(): array
    {
        return [
            "ventas_dia" => $this->sumSales("DATE(fecha) = CURDATE()"),
            "ventas_ayer" => $this->sumSales("DATE(fecha) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"),
            "ventas_mes" => $this->sumSales("YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())"),
            "usuarios" => $this->countRows("usuario", "estado = 1")
        ];
    }

    private function sumSales(string $where): float
    {
        $statement = $this->db->query("SELECT COALESCE(SUM(total), 0) AS total FROM venta WHERE estado = 1 AND $where");
        $row = $statement->fetch();

        return (float) ($row["total"] ?? 0);
    }

    private function countRows(string $table, string $where): int
    {
        $allowedTables = ["usuario", "producto", "cliente", "venta"];

        if (!in_array($table, $allowedTables, true)) {
            return 0;
        }

        $statement = $this->db->query("SELECT COUNT(*) AS total FROM $table WHERE $where");
        $row = $statement->fetch();

        return (int) ($row["total"] ?? 0);
    }
}
