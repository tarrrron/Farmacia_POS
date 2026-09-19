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
            "usuarios" => $this->countRows("usuario", "estado = 1"),
            "productos" => $this->countRows("producto", "estado = 1"),
            "clientes" => $this->countRows("cliente", "estado = 1"),
            "ventas" => $this->countRows("venta", "estado = 1")
        ];
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
