<?php
class Credit
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(string $status = ""): array
    {
        $sql = "SELECT c.idcredito, c.idventa, c.monto_total, c.saldo, c.fecha, c.fecha_vencimiento, c.estado,
                       cl.nombre AS cliente, cl.nrodocumento, v.serie, v.correlativo,
                       (c.estado = 1 AND c.fecha_vencimiento IS NOT NULL AND c.fecha_vencimiento < CURDATE()) AS vencido
                FROM credito c
                INNER JOIN cliente cl ON cl.idcliente = c.idcliente
                INNER JOIN venta v ON v.idventa = c.idventa";

        $conditions = [
            "pendiente" => " WHERE c.estado = 1",
            "vencido" => " WHERE c.estado = 1 AND c.fecha_vencimiento < CURDATE()",
            "pagado" => " WHERE c.estado = 2",
            "anulado" => " WHERE c.estado = 0"
        ];
        $sql .= $conditions[$status] ?? "";
        $sql .= " ORDER BY c.estado = 1 DESC, c.fecha_vencimiento ASC, c.idcredito DESC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function summary(): array
    {
        $row = $this->db->query("SELECT
                                    COALESCE(SUM(CASE WHEN estado = 1 THEN saldo END), 0) AS por_cobrar,
                                    COUNT(CASE WHEN estado = 1 THEN 1 END) AS pendientes,
                                    COUNT(CASE WHEN estado = 1 AND fecha_vencimiento < CURDATE() THEN 1 END) AS vencidos,
                                    COUNT(DISTINCT CASE WHEN estado = 1 THEN idcliente END) AS clientes
                                 FROM credito")->fetch(PDO::FETCH_ASSOC);

        return [
            "por_cobrar" => (float) ($row["por_cobrar"] ?? 0),
            "pendientes" => (int) ($row["pendientes"] ?? 0),
            "vencidos" => (int) ($row["vencidos"] ?? 0),
            "clientes" => (int) ($row["clientes"] ?? 0)
        ];
    }
}
