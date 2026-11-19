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

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT c.*, cl.nombre AS cliente, cl.nrodocumento, v.serie, v.correlativo,
                                                (c.estado = 1 AND c.fecha_vencimiento IS NOT NULL AND c.fecha_vencimiento < CURDATE()) AS vencido
                                         FROM credito c
                                         INNER JOIN cliente cl ON cl.idcliente = c.idcliente
                                         INNER JOIN venta v ON v.idventa = c.idventa
                                         WHERE c.idcredito = :id");
        $statement->execute(["id" => $id]);
        $credit = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$credit) {
            return null;
        }

        $statement = $this->db->prepare("SELECT p.idpago, p.monto, p.fecha, p.observacion, u.nombre AS usuario
                                         FROM pago_credito p
                                         INNER JOIN usuario u ON u.idusuario = p.idusuario
                                         WHERE p.idcredito = :id
                                         ORDER BY p.fecha DESC, p.idpago DESC");
        $statement->execute(["id" => $id]);
        $credit["pagos"] = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $credit;
    }

    public function addPayment(int $creditId, string $amount, string $note, int $userId): void
    {
        $toCents = static function (string $value): int {
            [$whole, $fraction] = array_pad(explode(".", $value, 2), 2, "0");
            return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, "0");
        };
        $fromCents = static fn(int $cents): string => intdiv($cents, 100) . "." . str_pad((string) ($cents % 100), 2, "0", STR_PAD_LEFT);

        $this->db->beginTransaction();
        try {
            $query = $this->db->prepare("SELECT saldo, estado FROM credito WHERE idcredito = :id FOR UPDATE");
            $query->execute(["id" => $creditId]);
            $credit = $query->fetch(PDO::FETCH_ASSOC);
            if (!$credit || (int) $credit["estado"] !== 1) {
                throw new RuntimeException("El crédito no existe o ya no está pendiente.");
            }

            $balance = $toCents((string) $credit["saldo"]);
            $payment = $toCents($amount);
            if ($payment <= 0) {
                throw new RuntimeException("El monto del pago debe ser mayor a cero.");
            }
            if ($payment > $balance) {
                throw new RuntimeException("El pago no puede ser mayor al saldo pendiente (S/ " . $fromCents($balance) . ").");
            }

            $insert = $this->db->prepare("INSERT INTO pago_credito (idcredito, idusuario, monto, observacion)
                                          VALUES (:credito, :usuario, :monto, :observacion)");
            $insert->execute([
                "credito" => $creditId,
                "usuario" => $userId,
                "monto" => $fromCents($payment),
                "observacion" => $note !== "" ? $note : null
            ]);

            $newBalance = $balance - $payment;
            $update = $this->db->prepare("UPDATE credito SET saldo = :saldo, estado = :estado WHERE idcredito = :id");
            $update->execute([
                "saldo" => $fromCents($newBalance),
                "estado" => $newBalance === 0 ? 2 : 1,
                "id" => $creditId
            ]);

            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
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
