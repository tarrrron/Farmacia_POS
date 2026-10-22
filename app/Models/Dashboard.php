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
            "usuarios" => $this->countRows("usuario", "estado = 1"),
            "productos" => $this->countRows("producto", "estado = 1"),
            "stock_bajo" => (int) $this->db->query("SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock <= stockseguridad")->fetchColumn(),
            "ventas_mes_cantidad" => (int) $this->db->query("SELECT COUNT(*) FROM venta WHERE estado = 1 AND YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())")->fetchColumn()
        ];
    }

    public function weeklySales(): array
    {
        return $this->db->query("SELECT DATE_FORMAT(days.day, '%Y-%m-%d') AS fecha,
                                        COALESCE(SUM(v.total), 0) AS total
                                 FROM (
                                     SELECT CURDATE() AS day UNION ALL
                                     SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) UNION ALL
                                     SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) UNION ALL
                                     SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) UNION ALL
                                     SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) UNION ALL
                                     SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) UNION ALL
                                     SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                 ) days
                                 LEFT JOIN venta v ON DATE(v.fecha) = days.day AND v.estado = 1
                                 GROUP BY days.day ORDER BY days.day")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function stockStatus(): array
    {
        $row = $this->db->query("SELECT
                                    SUM(CASE WHEN estado = 1 AND stock <= 0 THEN 1 ELSE 0 END) AS agotados,
                                    SUM(CASE WHEN estado = 1 AND stock > 0 AND stock <= stockseguridad THEN 1 ELSE 0 END) AS bajos,
                                    SUM(CASE WHEN estado = 1 AND stock > stockseguridad THEN 1 ELSE 0 END) AS disponibles
                                 FROM producto")->fetch();
        return ["Agotados" => (int)($row["agotados"] ?? 0), "Stock bajo" => (int)($row["bajos"] ?? 0), "Disponibles" => (int)($row["disponibles"] ?? 0)];
    }

    public function lowStockProducts(): array
    {
        return $this->db->query("SELECT p.nombre, p.stock, p.stockseguridad, u.descripcion AS unidad
                                 FROM producto p INNER JOIN unidad u ON u.idunidad = p.idunidad
                                 WHERE p.estado = 1 AND p.stock <= p.stockseguridad
                                 ORDER BY (p.stock <= 0) DESC, p.stock ASC, p.nombre ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recentSales(): array
    {
        return $this->db->query("SELECT v.idventa, v.fecha, v.serie, v.correlativo, v.total,
                                       c.nombre AS cliente, td.nombre AS comprobante
                                FROM venta v LEFT JOIN cliente c ON c.idcliente = v.idcliente
                                INNER JOIN tipocomprobante td ON td.idtipocomprobante = v.idtipocomprobante
                                WHERE v.estado = 1 ORDER BY v.fecha DESC, v.idventa DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
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
