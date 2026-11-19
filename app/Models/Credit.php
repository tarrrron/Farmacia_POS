<?php
class Credit
{
    public const PAYMENT_METHODS = [
        "EFECTIVO" => "Efectivo",
        "YAPE_PLIN" => "Yape / Plin",
        "TRANSFERENCIA" => "Transferencia",
        "TARJETA" => "Tarjeta"
    ];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(string $status = ""): array
    {
        $sql = "SELECT c.idcredito, c.idventa, c.idcliente, c.monto_total, c.saldo, c.fecha, c.fecha_vencimiento, c.estado,
                       cl.nombre AS cliente, cl.nrodocumento, v.serie, v.correlativo,
                       (c.estado = 1 AND c.fecha_vencimiento IS NOT NULL AND c.fecha_vencimiento < CURDATE()) AS vencido,
                       DATEDIFF(c.fecha_vencimiento, CURDATE()) AS dias
                FROM credito c
                INNER JOIN cliente cl ON cl.idcliente = c.idcliente
                INNER JOIN venta v ON v.idventa = c.idventa";

        $conditions = [
            "pendiente" => " WHERE c.estado = 1",
            "por_vencer" => " WHERE c.estado = 1 AND c.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
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
                                                (c.estado = 1 AND c.fecha_vencimiento IS NOT NULL AND c.fecha_vencimiento < CURDATE()) AS vencido,
                                                DATEDIFF(c.fecha_vencimiento, CURDATE()) AS dias
                                         FROM credito c
                                         INNER JOIN cliente cl ON cl.idcliente = c.idcliente
                                         INNER JOIN venta v ON v.idventa = c.idventa
                                         WHERE c.idcredito = :id");
        $statement->execute(["id" => $id]);
        $credit = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$credit) {
            return null;
        }

        $credit["pagos"] = $this->payments("p.idcredito = :id", ["id" => $id]);

        $statement = $this->db->prepare("SELECT pr.fecha_anterior, pr.fecha_nueva, pr.motivo, pr.fecha, u.nombre AS usuario
                                         FROM credito_prorroga pr
                                         INNER JOIN usuario u ON u.idusuario = pr.idusuario
                                         WHERE pr.idcredito = :id
                                         ORDER BY pr.fecha DESC, pr.idprorroga DESC");
        $statement->execute(["id" => $id]);
        $credit["prorrogas"] = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $credit;
    }

    public function findPayment(int $paymentId): ?array
    {
        $payments = $this->payments("p.idpago = :id", ["id" => $paymentId]);
        if (!$payments) {
            return null;
        }
        $payment = $payments[0];
        $credit = $this->find((int) $payment["idcredito"]);
        $payment["credito"] = $credit;
        return $payment;
    }

    public function clientStatement(int $clientId): ?array
    {
        $statement = $this->db->prepare("SELECT cl.idcliente, cl.nombre, cl.nrodocumento, td.nombre AS tipo_documento
                                         FROM cliente cl
                                         INNER JOIN tipodocumento td ON td.idtipodocumento = cl.idtipodocumento
                                         WHERE cl.idcliente = :id");
        $statement->execute(["id" => $clientId]);
        $client = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$client) {
            return null;
        }

        $statement = $this->db->prepare("SELECT c.idcredito, c.idventa, c.monto_total, c.saldo, c.fecha, c.fecha_vencimiento, c.estado,
                                                v.serie, v.correlativo,
                                                (c.estado = 1 AND c.fecha_vencimiento IS NOT NULL AND c.fecha_vencimiento < CURDATE()) AS vencido,
                                                DATEDIFF(c.fecha_vencimiento, CURDATE()) AS dias
                                         FROM credito c
                                         INNER JOIN venta v ON v.idventa = c.idventa
                                         WHERE c.idcliente = :id
                                         ORDER BY c.estado = 1 DESC, c.fecha DESC");
        $statement->execute(["id" => $clientId]);
        $client["creditos"] = $statement->fetchAll(PDO::FETCH_ASSOC);
        $client["pagos"] = $this->payments("c.idcliente = :id", ["id" => $clientId]);

        $client["deuda"] = 0.0;
        $client["vencido"] = 0.0;
        $client["pagado"] = 0.0;
        foreach ($client["creditos"] as $credit) {
            if ((int) $credit["estado"] === 1) {
                $client["deuda"] += (float) $credit["saldo"];
                if ((int) $credit["vencido"] === 1) {
                    $client["vencido"] += (float) $credit["saldo"];
                }
            }
        }
        foreach ($client["pagos"] as $payment) {
            if ((int) $payment["estado"] === 1) {
                $client["pagado"] += (float) $payment["monto"];
            }
        }

        return $client;
    }

    public function addPayment(int $creditId, string $amount, string $method, string $operation, string $note, int $userId): int
    {
        if (!array_key_exists($method, self::PAYMENT_METHODS)) {
            throw new RuntimeException("Selecciona un método de pago válido.");
        }

        $this->db->beginTransaction();
        try {
            $query = $this->db->prepare("SELECT saldo, estado FROM credito WHERE idcredito = :id FOR UPDATE");
            $query->execute(["id" => $creditId]);
            $credit = $query->fetch(PDO::FETCH_ASSOC);
            if (!$credit || (int) $credit["estado"] !== 1) {
                throw new RuntimeException("El crédito no existe o ya no está pendiente.");
            }

            $balance = $this->toCents((string) $credit["saldo"]);
            $payment = $this->toCents($amount);
            if ($payment <= 0) {
                throw new RuntimeException("El monto del pago debe ser mayor a cero.");
            }
            if ($payment > $balance) {
                throw new RuntimeException("El pago no puede ser mayor al saldo pendiente (S/ " . $this->fromCents($balance) . ").");
            }

            $insert = $this->db->prepare("INSERT INTO pago_credito (idcredito, idusuario, monto, metodo_pago, nro_operacion, observacion, estado)
                                          VALUES (:credito, :usuario, :monto, :metodo, :operacion, :observacion, 1)");
            $insert->execute([
                "credito" => $creditId,
                "usuario" => $userId,
                "monto" => $this->fromCents($payment),
                "metodo" => $method,
                "operacion" => $operation !== "" ? $operation : null,
                "observacion" => $note !== "" ? $note : null
            ]);
            $paymentId = (int) $this->db->lastInsertId();

            $newBalance = $balance - $payment;
            $update = $this->db->prepare("UPDATE credito SET saldo = :saldo, estado = :estado WHERE idcredito = :id");
            $update->execute([
                "saldo" => $this->fromCents($newBalance),
                "estado" => $newBalance === 0 ? 2 : 1,
                "id" => $creditId
            ]);

            $this->db->commit();
            return $paymentId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function cancelPayment(int $paymentId, string $reason, int $userId): int
    {
        $this->db->beginTransaction();
        try {
            $query = $this->db->prepare("SELECT idcredito, monto, estado FROM pago_credito WHERE idpago = :id FOR UPDATE");
            $query->execute(["id" => $paymentId]);
            $payment = $query->fetch(PDO::FETCH_ASSOC);
            if (!$payment || (int) $payment["estado"] !== 1) {
                throw new RuntimeException("El pago no existe o ya fue anulado.");
            }

            $query = $this->db->prepare("SELECT saldo, monto_total, estado FROM credito WHERE idcredito = :id FOR UPDATE");
            $query->execute(["id" => $payment["idcredito"]]);
            $credit = $query->fetch(PDO::FETCH_ASSOC);
            if (!$credit || (int) $credit["estado"] === 0) {
                throw new RuntimeException("No se puede anular un pago de un crédito anulado.");
            }

            $newBalance = $this->toCents((string) $credit["saldo"]) + $this->toCents((string) $payment["monto"]);
            if ($newBalance > $this->toCents((string) $credit["monto_total"])) {
                throw new RuntimeException("El saldo resultante superaría el monto del crédito.");
            }

            $update = $this->db->prepare("UPDATE pago_credito
                                          SET estado = 0, motivo_anulacion = :motivo, fecha_anulacion = NOW(), idusuario_anula = :usuario
                                          WHERE idpago = :id");
            $update->execute(["motivo" => $reason, "usuario" => $userId, "id" => $paymentId]);

            $update = $this->db->prepare("UPDATE credito SET saldo = :saldo, estado = 1 WHERE idcredito = :id");
            $update->execute(["saldo" => $this->fromCents($newBalance), "id" => $payment["idcredito"]]);

            $this->db->commit();
            return (int) $payment["idcredito"];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function extend(int $creditId, string $newDate, string $reason, int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $query = $this->db->prepare("SELECT fecha_vencimiento, estado FROM credito WHERE idcredito = :id FOR UPDATE");
            $query->execute(["id" => $creditId]);
            $credit = $query->fetch(PDO::FETCH_ASSOC);
            if (!$credit || (int) $credit["estado"] !== 1) {
                throw new RuntimeException("Solo se puede prorrogar un crédito pendiente.");
            }
            if ($credit["fecha_vencimiento"] !== null && $newDate <= $credit["fecha_vencimiento"]) {
                throw new RuntimeException("La nueva fecha debe ser posterior al vencimiento actual.");
            }

            $insert = $this->db->prepare("INSERT INTO credito_prorroga (idcredito, idusuario, fecha_anterior, fecha_nueva, motivo)
                                          VALUES (:credito, :usuario, :anterior, :nueva, :motivo)");
            $insert->execute([
                "credito" => $creditId,
                "usuario" => $userId,
                "anterior" => $credit["fecha_vencimiento"],
                "nueva" => $newDate,
                "motivo" => $reason
            ]);

            $update = $this->db->prepare("UPDATE credito SET fecha_vencimiento = :fecha WHERE idcredito = :id");
            $update->execute(["fecha" => $newDate, "id" => $creditId]);

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
                                    COALESCE(SUM(CASE WHEN estado = 1 AND fecha_vencimiento < CURDATE() THEN saldo END), 0) AS monto_vencido,
                                    COUNT(DISTINCT CASE WHEN estado = 1 THEN idcliente END) AS clientes
                                 FROM credito")->fetch(PDO::FETCH_ASSOC);

        return [
            "por_cobrar" => (float) ($row["por_cobrar"] ?? 0),
            "pendientes" => (int) ($row["pendientes"] ?? 0),
            "vencidos" => (int) ($row["vencidos"] ?? 0),
            "monto_vencido" => (float) ($row["monto_vencido"] ?? 0),
            "clientes" => (int) ($row["clientes"] ?? 0)
        ];
    }

    private function payments(string $where, array $params): array
    {
        $statement = $this->db->prepare("SELECT p.idpago, p.idcredito, p.monto, p.metodo_pago, p.nro_operacion, p.fecha, p.observacion,
                                                p.estado, p.motivo_anulacion, p.fecha_anulacion,
                                                u.nombre AS usuario, ua.nombre AS usuario_anula,
                                                v.serie, v.correlativo
                                         FROM pago_credito p
                                         INNER JOIN credito c ON c.idcredito = p.idcredito
                                         INNER JOIN venta v ON v.idventa = c.idventa
                                         INNER JOIN usuario u ON u.idusuario = p.idusuario
                                         LEFT JOIN usuario ua ON ua.idusuario = p.idusuario_anula
                                         WHERE " . $where . "
                                         ORDER BY p.fecha DESC, p.idpago DESC");
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function toCents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode(".", $value, 2), 2, "0");
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, "0");
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100) . "." . str_pad((string) ($cents % 100), 2, "0", STR_PAD_LEFT);
    }
}
