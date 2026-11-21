<?php
class Sale
{
    public const MINIMUM_CREDIT_AMOUNT = 100.00;
    public const MAX_CREDIT_INSTALLMENTS = 3;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(?string $from = null, ?string $to = null): array
    {
        $where = [];
        $params = [];
        if ($from !== null) {
            $where[] = "DATE(v.fecha) >= :desde";
            $params["desde"] = $from;
        }
        if ($to !== null) {
            $where[] = "DATE(v.fecha) <= :hasta";
            $params["hasta"] = $to;
        }

        $statement = $this->db->prepare("SELECT v.idventa, v.fecha, v.serie, v.correlativo, v.total,
                                       v.formapago, v.estado, c.nombre AS cliente, c.nrodocumento,
                                       td.nombre AS comprobante, u.nombre AS vendedor,
                                       (SELECT COUNT(*) FROM detalle_venta d WHERE d.idventa = v.idventa) AS productos
                                FROM venta v
                                LEFT JOIN cliente c ON c.idcliente = v.idcliente
                                INNER JOIN tipocomprobante td ON td.idtipocomprobante = v.idtipocomprobante
                                INNER JOIN usuario u ON u.idusuario = v.idusuario"
                                . ($where ? " WHERE " . implode(" AND ", $where) : "")
                                . " ORDER BY v.idventa DESC" . ($where ? " LIMIT 1000" : " LIMIT 100"));
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function catalog(): array
    {
        return $this->db->query("SELECT p.idproducto, p.nombre, p.codigobarra, p.urlimagen, p.pventa, p.stock,
                                       u.descripcion AS unidad
                                FROM producto p INNER JOIN unidad u ON u.idunidad = p.idunidad
                                WHERE p.estado = 1 AND p.stock >= 1 ORDER BY p.nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clients(): array
    {
        return $this->db->query("SELECT c.idcliente, c.nombre, c.nrodocumento, c.idtipodocumento, td.nombre AS tipo_documento,
                                       COALESCE((SELECT SUM(cr.saldo) FROM credito cr
                                                 WHERE cr.idcliente = c.idcliente AND cr.estado = 1), 0) AS deuda,
                                       COALESCE((SELECT SUM(cu.saldo) FROM credito cr
                                                 INNER JOIN credito_cuota cu ON cu.idcredito = cr.idcredito
                                                 WHERE cr.idcliente = c.idcliente AND cr.estado = 1 AND cu.estado = 1
                                                   AND cu.fecha_vencimiento < CURDATE()), 0) AS deuda_vencida
                                FROM cliente c INNER JOIN tipodocumento td ON td.idtipodocumento = c.idtipodocumento
                                WHERE c.estado = 1 ORDER BY c.nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function documentTypes(): array
    {
        return $this->db->query("SELECT idtipocomprobante, nombre FROM tipocomprobante WHERE estado = 1 ORDER BY idtipocomprobante")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clientDocumentTypes(): array
    {
        return $this->db->query("SELECT idtipodocumento, nombre FROM tipodocumento WHERE estado = 1 ORDER BY idtipodocumento")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT v.*, c.nombre AS cliente, c.nrodocumento,
                                               td.nombre AS comprobante, u.nombre AS vendedor
                                        FROM venta v LEFT JOIN cliente c ON c.idcliente = v.idcliente
                                        INNER JOIN tipocomprobante td ON td.idtipocomprobante = v.idtipocomprobante
                                        INNER JOIN usuario u ON u.idusuario = v.idusuario WHERE v.idventa = :id");
        $statement->execute(["id" => $id]);
        $sale = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$sale) return null;
        $statement = $this->db->prepare("SELECT idcredito, saldo, estado, numero_cuotas, fecha_vencimiento FROM credito WHERE idventa = :id");
        $statement->execute(["id" => $id]);
        $sale["credito"] = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($sale["credito"]) {
            $statement = $this->db->prepare("SELECT numero_cuota, fecha_vencimiento, monto, saldo, estado
                                             FROM credito_cuota WHERE idcredito = :id ORDER BY numero_cuota");
            $statement->execute(["id" => $sale["credito"]["idcredito"]]);
            $sale["credito"]["cuotas"] = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        $statement = $this->db->prepare("SELECT d.*, p.nombre AS producto, u.descripcion AS unidad_nombre
                                        FROM detalle_venta d INNER JOIN producto p ON p.idproducto = d.idproducto
                                        INNER JOIN unidad u ON u.idunidad = p.idunidad WHERE d.idventa = :id ORDER BY d.iddetalle");
        $statement->execute(["id" => $id]);
        $sale["detalles"] = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $sale;
    }

    public function cancel(int $id, int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $saleQuery = $this->db->prepare("SELECT idventa, serie, correlativo, estado
                                             FROM venta WHERE idventa = :id FOR UPDATE");
            $saleQuery->execute(["id" => $id]);
            $sale = $saleQuery->fetch(PDO::FETCH_ASSOC);
            if (!$sale || (int) $sale["estado"] !== 1) {
                throw new RuntimeException("La venta no existe o ya fue anulada.");
            }

            $creditQuery = $this->db->prepare("SELECT c.idcredito,
                                                      (SELECT COUNT(*) FROM pago_credito p WHERE p.idcredito = c.idcredito AND p.estado = 1) AS pagos
                                               FROM credito c WHERE c.idventa = :id AND c.estado <> 0 FOR UPDATE");
            $creditQuery->execute(["id" => $id]);
            $credit = $creditQuery->fetch(PDO::FETCH_ASSOC);
            if ($credit && (int) $credit["pagos"] > 0) {
                throw new RuntimeException("No se puede anular la venta porque su crédito ya tiene pagos registrados.");
            }

            $detailQuery = $this->db->prepare("SELECT idproducto, cantidad FROM detalle_venta
                                               WHERE idventa = :id AND estado = 1
                                               ORDER BY idproducto, iddetalle FOR UPDATE");
            $detailQuery->execute(["id" => $id]);
            $asCents = static function ($value): int {
                [$whole, $fraction] = array_pad(explode(".", (string) $value, 2), 2, "0");
                return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, "0");
            };
            $quantities = [];
            foreach ($detailQuery->fetchAll(PDO::FETCH_ASSOC) as $line) {
                $productId = (int) $line["idproducto"];
                $quantities[$productId] = ($quantities[$productId] ?? 0) + $asCents($line["cantidad"]);
            }
            ksort($quantities, SORT_NUMERIC);

            $productQuery = $this->db->prepare("SELECT nombre, stock FROM producto WHERE idproducto = :id FOR UPDATE");
            $productUpdate = $this->db->prepare("UPDATE producto SET stock = :stock WHERE idproducto = :id");
            $movementInsert = $this->db->prepare("INSERT INTO movimiento_inventario
                (idproducto, idusuario, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                VALUES (:producto, :usuario, 'ENTRADA', :cantidad, :anterior, :nuevo, :motivo)");
            $invoice = ($sale["serie"] ?? "") . "-" . str_pad((string) ($sale["correlativo"] ?? ""), 8, "0", STR_PAD_LEFT);
            $formatCents = static fn(int $cents): string => intdiv($cents, 100) . "." . str_pad((string) ($cents % 100), 2, "0", STR_PAD_LEFT);

            foreach ($quantities as $productId => $quantityCents) {
                $productQuery->execute(["id" => $productId]);
                $product = $productQuery->fetch(PDO::FETCH_ASSOC);
                if (!$product) {
                    throw new RuntimeException("No se encontró un producto de la venta para restituir su stock.");
                }
                $previousCents = $asCents($product["stock"]);
                $newCents = $previousCents + $quantityCents;
                if ($newCents > 99999999999) {
                    throw new RuntimeException("No se pudo restituir el stock porque superaría el límite del producto " . $product["nombre"] . ".");
                }
                $productUpdate->execute(["stock" => $formatCents($newCents), "id" => $productId]);
                $movementInsert->execute([
                    "producto" => $productId,
                    "usuario" => $userId,
                    "cantidad" => $formatCents($quantityCents),
                    "anterior" => $formatCents($previousCents),
                    "nuevo" => $formatCents($newCents),
                    "motivo" => "Anulación de venta " . $invoice
                ]);
            }

            $detailsUpdate = $this->db->prepare("UPDATE detalle_venta SET estado = 0 WHERE idventa = :id");
            $detailsUpdate->execute(["id" => $id]);
            $saleUpdate = $this->db->prepare("UPDATE venta SET estado = 0 WHERE idventa = :id");
            $saleUpdate->execute(["id" => $id]);
            if ($credit) {
                $creditUpdate = $this->db->prepare("UPDATE credito SET estado = 0 WHERE idcredito = :id");
                $creditUpdate->execute(["id" => $credit["idcredito"]]);
                $installmentsUpdate = $this->db->prepare("UPDATE credito_cuota SET estado = 0 WHERE idcredito = :id");
                $installmentsUpdate->execute(["id" => $credit["idcredito"]]);
            }
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function create(array $sale, array $items, int $userId): int
    {
        $this->db->beginTransaction();
        try {
            $series = $this->db->prepare("SELECT idserie, serie, correlativo FROM serie
                                           WHERE idtipocomprobante = :tipo AND estado = 1 ORDER BY idserie LIMIT 1 FOR UPDATE");
            $series->execute(["tipo" => $sale["idtipocomprobante"]]);
            $seriesRow = $series->fetch(PDO::FETCH_ASSOC);
            if (!$seriesRow) throw new RuntimeException("No hay una serie activa para el comprobante seleccionado.");

            $typeCheck = $this->db->prepare("SELECT nombre FROM tipocomprobante WHERE idtipocomprobante = :id AND estado = 1");
            $typeCheck->execute(["id" => $sale["idtipocomprobante"]]);
            $receiptName = $typeCheck->fetchColumn();
            if (!$receiptName) throw new RuntimeException("Selecciona un comprobante activo.");
            $isInvoice = stripos((string) $receiptName, "factura") !== false;
            if (empty($sale["idcliente"]) && !empty($sale["cliente_nuevo"]) && is_array($sale["cliente_nuevo"])) {
                $newClient = $sale["cliente_nuevo"];
                $duplicate = $this->db->prepare("SELECT idcliente, estado FROM cliente WHERE nrodocumento = :documento LIMIT 1 FOR UPDATE");
                $duplicate->execute(["documento" => $newClient["nrodocumento"]]);
                $existing = $duplicate->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    if ((int) $existing["estado"] !== 1) throw new RuntimeException("Ya existe un cliente con ese documento, pero está inactivo. Actívalo desde Clientes antes de continuar.");
                    $sale["idcliente"] = (int) $existing["idcliente"];
                } else {
                    $type = $this->db->prepare("SELECT nombre FROM tipodocumento WHERE idtipodocumento = :id AND estado = 1");
                    $type->execute(["id" => $newClient["idtipodocumento"]]);
                    if (!$type->fetchColumn()) throw new RuntimeException("Selecciona un tipo de documento activo para el cliente.");
                    $insertClient = $this->db->prepare("INSERT INTO cliente
                        (nombre, nombre_comercial, razon_social, idtipodocumento, nrodocumento, direccion, departamento, provincia, distrito, estado)
                        VALUES (:nombre, NULL, :razon_social, :tipo, :documento, :direccion, :departamento, :provincia, :distrito, 1)");
                    $insertClient->execute(["nombre" => $newClient["nombre"], "razon_social" => $newClient["razon_social"] ?: null,
                        "tipo" => $newClient["idtipodocumento"], "documento" => $newClient["nrodocumento"],
                        "direccion" => $newClient["direccion"] ?: null, "departamento" => $newClient["departamento"] ?: null,
                        "provincia" => $newClient["provincia"] ?: null, "distrito" => $newClient["distrito"] ?: null]);
                    $sale["idcliente"] = (int) $this->db->lastInsertId();
                }
            }
            if ($sale["idcliente"]) {
                $clientCheck = $this->db->prepare("SELECT c.idtipodocumento, td.nombre AS tipo_documento FROM cliente c
                    INNER JOIN tipodocumento td ON td.idtipodocumento = c.idtipodocumento WHERE c.idcliente = :id AND c.estado = 1");
                $clientCheck->execute(["id" => $sale["idcliente"]]);
                $clientRecord = $clientCheck->fetch(PDO::FETCH_ASSOC);
                if (!$clientRecord) throw new RuntimeException("El cliente seleccionado ya no está disponible.");
                if ($isInvoice && strtoupper((string) $clientRecord["tipo_documento"]) !== "RUC") throw new RuntimeException("Para emitir factura, selecciona un cliente registrado con RUC.");
            } elseif ($isInvoice) {
                throw new RuntimeException("Para emitir factura, selecciona un cliente con RUC.");
            }
            $isCredit = $sale["formapago"] === "R";
            if ($isCredit && !$sale["idcliente"]) {
                throw new RuntimeException("Para una venta a crédito, selecciona un cliente registrado.");
            }
            $installments = filter_var($sale["numero_cuotas"] ?? 1, FILTER_VALIDATE_INT);
            if ($isCredit && ($installments === false || $installments < 1 || $installments > self::MAX_CREDIT_INSTALLMENTS)) {
                throw new RuntimeException("Selecciona entre 1 y " . self::MAX_CREDIT_INSTALLMENTS . " cuotas mensuales.");
            }
            if ($isCredit && empty($sale["vencimiento"])) throw new RuntimeException("Indica la fecha de vencimiento del crédito.");
            if ($isCredit) {
                $overdue = $this->db->prepare("SELECT COALESCE(SUM(cu.saldo), 0) FROM credito c
                                               INNER JOIN credito_cuota cu ON cu.idcredito = c.idcredito
                                               WHERE c.idcliente = :cliente AND c.estado = 1 AND cu.estado = 1
                                                 AND cu.fecha_vencimiento < CURDATE()");
                $overdue->execute(["cliente" => $sale["idcliente"]]);
                $overdueAmount = (float) $overdue->fetchColumn();
                if ($overdueAmount > 0) {
                    throw new RuntimeException("El cliente tiene créditos vencidos por S/ " . number_format($overdueAmount, 2) . ". Regulariza su deuda antes de otorgarle un nuevo crédito.");
                }
            }
            $productQuery = $this->db->prepare("SELECT p.idproducto, p.nombre, p.pventa, p.stock, p.idafectacion,
                                                       u.descripcion AS unidad
                                                FROM producto p INNER JOIN unidad u ON u.idunidad = p.idunidad
                                                WHERE p.idproducto = :id AND p.estado = 1 FOR UPDATE");
            $lines = []; $total = 0.0; $gravado = 0.0; $exonerado = 0.0; $inafecto = 0.0; $igv = 0.0;
            $quantities = [];
            foreach ($items as $item) {
                if (!is_array($item)) throw new RuntimeException("Revisa los productos y cantidades de la venta.");
                $id = filter_var($item["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
                $quantity = filter_var($item["cantidad"] ?? null, FILTER_VALIDATE_FLOAT);
                if ($id === false || $quantity === false || $quantity <= 0 || $quantity > 100000 || abs($quantity - round($quantity)) > 0.000001) throw new RuntimeException("La cantidad de cada producto debe ser un número entero mayor a cero.");
                $quantities[$id] = ($quantities[$id] ?? 0) + $quantity;
            }
            ksort($quantities, SORT_NUMERIC);
            foreach ($quantities as $id => $quantity) {
                if ($quantity > 100000) throw new RuntimeException("La cantidad ingresada excede el limite permitido.");
                $productQuery->execute(["id" => $id]); $product = $productQuery->fetch(PDO::FETCH_ASSOC);
                if (!$product) throw new RuntimeException("Uno de los productos ya no esta disponible.");
                if ((float)$product["stock"] < $quantity) throw new RuntimeException("Stock insuficiente para " . $product["nombre"] . ". Disponible: " . $product["stock"] . ".");
                $lineTotal = round((float)$product["pventa"] * $quantity, 2);
                $tax = (int)$product["idafectacion"] === 1 ? round($lineTotal - ($lineTotal / 1.18), 2) : 0.0;
                $base = round($lineTotal - $tax, 2);
                if ((int)$product["idafectacion"] === 1) $gravado += $base;
                elseif ((int)$product["idafectacion"] === 2) $exonerado += $base;
                else $inafecto += $base;
                $igv += $tax; $total += $lineTotal;
                $lines[] = ["product"=>$product, "cantidad"=>$quantity, "total"=>$lineTotal, "igv"=>$tax];
            }
            if (!$lines) throw new RuntimeException("Agrega al menos un producto a la venta.");
            if ($isCredit && round($total, 2) < self::MINIMUM_CREDIT_AMOUNT) {
                throw new RuntimeException("Las ventas a crédito están disponibles desde S/ " . number_format(self::MINIMUM_CREDIT_AMOUNT, 2) . ".");
            }

            $totalCents = (int) round($total * 100);
            $receivedCents = 0;
            $changeCents = 0;
            if (!$isCredit) {
                $receivedInput = trim((string) ($sale["monto_recibido"] ?? ""));
                if ($receivedInput === "") {
                    throw new RuntimeException("Ingresa el monto recibido para registrar la venta.");
                } elseif (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $receivedInput)) {
                    throw new RuntimeException("Ingresa un monto recibido válido, con hasta dos decimales.");
                } else {
                    $receivedCents = (int) round((float) $receivedInput * 100);
                }
                if ($receivedCents <= 0) {
                    throw new RuntimeException("El monto recibido debe ser mayor que cero.");
                }
                if ($receivedCents < $totalCents) {
                    throw new RuntimeException("El monto recibido no puede ser menor al total de la venta.");
                }
                $changeCents = $receivedCents - $totalCents;
            }

            $dueDate = $isCredit ? ($sale["vencimiento"] ?? null) : null;
            $insert = $this->db->prepare("INSERT INTO venta (fecha,idcliente,idtipocomprobante,serie,correlativo,total,monto_recibido,vuelto,idusuario,total_gravado,total_exonerado,total_inafecto,total_igv,formapago,idmoneda,vencimiento)
                                          VALUES (NOW(),:cliente,:tipo,:serie,:correlativo,:total,:recibido,:vuelto,:usuario,:gravado,:exonerado,:inafecto,:igv,:pago,1,:vencimiento)");
            $insert->execute(["cliente"=>$sale["idcliente"] ?: null, "tipo"=>$sale["idtipocomprobante"], "serie"=>$seriesRow["serie"],
                "correlativo"=>(int)$seriesRow["correlativo"] + 1, "total"=>round($total,2),
                "recibido"=>number_format($receivedCents / 100, 2, ".", ""), "vuelto"=>number_format($changeCents / 100, 2, ".", ""), "usuario"=>$userId,
                "gravado"=>round($gravado,2), "exonerado"=>round($exonerado,2), "inafecto"=>round($inafecto,2), "igv"=>round($igv,2), "pago"=>$sale["formapago"], "vencimiento"=>$dueDate]);
            $saleId = (int)$this->db->lastInsertId();
            $detail = $this->db->prepare("INSERT INTO detalle_venta (idventa,idproducto,cantidad,unidad,pventa,igv,total,idafectacion)
                                           VALUES (:venta,:producto,:cantidad,:unidad,:precio,:igv,:total,:afectacion)");
            $stockUpdate = $this->db->prepare("UPDATE producto SET stock = stock - :cantidad WHERE idproducto = :id AND stock >= :disponible");
            $movement = $this->db->prepare("INSERT INTO movimiento_inventario
                (idproducto, idusuario, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                VALUES (:producto, :usuario, 'SALIDA', :cantidad, :anterior, :nuevo, :motivo)");
            $invoice = $seriesRow["serie"] . "-" . str_pad((string)((int)$seriesRow["correlativo"] + 1), 8, "0", STR_PAD_LEFT);
            foreach ($lines as $line) {
                $p = $line["product"];
                $previousStock = round((float)$p["stock"], 2);
                $movement->execute(["producto"=>$p["idproducto"], "usuario"=>$userId, "cantidad"=>number_format((float)$line["cantidad"], 2, ".", ""),
                    "anterior"=>number_format($previousStock, 2, ".", ""), "nuevo"=>number_format(round($previousStock - (float)$line["cantidad"], 2), 2, ".", ""),
                    "motivo"=>"Venta " . $invoice]);
                $detail->execute(["venta"=>$saleId,"producto"=>$p["idproducto"],"cantidad"=>$line["cantidad"],"unidad"=>$p["unidad"],
                    "precio"=>$p["pventa"],"igv"=>$line["igv"],"total"=>$line["total"],"afectacion"=>$p["idafectacion"]]);
                $stockUpdate->execute(["cantidad"=>$line["cantidad"],"disponible"=>$line["cantidad"],"id"=>$p["idproducto"]]);
                if ($stockUpdate->rowCount() !== 1) throw new RuntimeException("No se pudo actualizar el stock de " . $p["nombre"] . ".");
            }
            if ($isCredit) {
                $credit = $this->db->prepare("INSERT INTO credito (idventa, idcliente, monto_total, saldo, fecha_vencimiento, estado, numero_cuotas)
                                              VALUES (:venta, :cliente, :monto, :saldo, :vencimiento, 1, :cuotas)");
                $credit->execute(["venta"=>$saleId, "cliente"=>$sale["idcliente"], "monto"=>round($total,2),
                    "saldo"=>round($total,2), "vencimiento"=>$dueDate, "cuotas"=>$installments]);
                $creditId = (int) $this->db->lastInsertId();
                $this->createInstallmentSchedule($creditId, (int) $installments, (int) round($total * 100), $dueDate);
            }
            $advance = $this->db->prepare("UPDATE serie SET correlativo = :correlativo WHERE idserie = :id");
            $advance->execute(["correlativo"=>(int)$seriesRow["correlativo"] + 1,"id"=>$seriesRow["idserie"]]);
            $this->db->commit();
            return $saleId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function createInstallmentSchedule(int $creditId, int $count, int $totalCents, string $firstDueDate): void
    {
        $insert = $this->db->prepare("INSERT INTO credito_cuota (idcredito, numero_cuota, fecha_vencimiento, monto, saldo, estado)
                                      VALUES (:credito, :numero, :vencimiento, :monto, :saldo, 1)");
        $baseCents = intdiv($totalCents, $count);
        $extraCents = $totalCents % $count;
        for ($index = 0; $index < $count; $index++) {
            $cents = $baseCents + ($index < $extraCents ? 1 : 0);
            $insert->execute([
                "credito" => $creditId,
                "numero" => $index + 1,
                "vencimiento" => $this->monthlyDate($firstDueDate, $index),
                "monto" => $this->formatCents($cents),
                "saldo" => $this->formatCents($cents)
            ]);
        }
    }

    private function monthlyDate(string $date, int $months): string
    {
        $first = new DateTimeImmutable($date);
        $day = (int) $first->format("j");
        $month = $first->modify("first day of this month")->modify("+" . $months . " months");
        return $month->setDate((int) $month->format("Y"), (int) $month->format("n"), min($day, (int) $month->format("t")))->format("Y-m-d");
    }

    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100) . "." . str_pad((string) ($cents % 100), 2, "0", STR_PAD_LEFT);
    }
}
