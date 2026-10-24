<?php
class Sale
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query("SELECT v.idventa, v.fecha, v.serie, v.correlativo, v.total,
                                       v.formapago, v.estado, c.nombre AS cliente,
                                       td.nombre AS comprobante, u.nombre AS vendedor,
                                       (SELECT COUNT(*) FROM detalle_venta d WHERE d.idventa = v.idventa) AS productos
                                FROM venta v
                                LEFT JOIN cliente c ON c.idcliente = v.idcliente
                                INNER JOIN tipocomprobante td ON td.idtipocomprobante = v.idtipocomprobante
                                INNER JOIN usuario u ON u.idusuario = v.idusuario
                                ORDER BY v.idventa DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
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
        return $this->db->query("SELECT idcliente, nombre, nrodocumento FROM cliente WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function documentTypes(): array
    {
        return $this->db->query("SELECT idtipocomprobante, nombre FROM tipocomprobante WHERE estado = 1 ORDER BY idtipocomprobante")->fetchAll(PDO::FETCH_ASSOC);
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

            $typeCheck = $this->db->prepare("SELECT 1 FROM tipocomprobante WHERE idtipocomprobante = :id AND estado = 1");
            $typeCheck->execute(["id" => $sale["idtipocomprobante"]]);
            if (!$typeCheck->fetchColumn()) throw new RuntimeException("Selecciona un comprobante activo.");
            if ($sale["idcliente"]) {
                $clientCheck = $this->db->prepare("SELECT idtipodocumento FROM cliente WHERE idcliente = :id AND estado = 1");
                $clientCheck->execute(["id" => $sale["idcliente"]]);
                $clientDocumentType = $clientCheck->fetchColumn();
                if ($clientDocumentType === false) throw new RuntimeException("El cliente seleccionado ya no esta disponible.");
                if ((int)$sale["idtipocomprobante"] === 2 && (int)$clientDocumentType !== 2) throw new RuntimeException("Para emitir factura, selecciona un cliente registrado con RUC.");
            } elseif ((int)$sale["idtipocomprobante"] === 2) {
                throw new RuntimeException("Para emitir factura, selecciona un cliente con RUC.");
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

            $insert = $this->db->prepare("INSERT INTO venta (fecha,idcliente,idtipocomprobante,serie,correlativo,total,idusuario,total_gravado,total_exonerado,total_inafecto,total_igv,formapago,idmoneda)
                                          VALUES (NOW(),:cliente,:tipo,:serie,:correlativo,:total,:usuario,:gravado,:exonerado,:inafecto,:igv,:pago,1)");
            $insert->execute(["cliente"=>$sale["idcliente"] ?: null, "tipo"=>$sale["idtipocomprobante"], "serie"=>$seriesRow["serie"],
                "correlativo"=>(int)$seriesRow["correlativo"] + 1, "total"=>round($total,2), "usuario"=>$userId,
                "gravado"=>round($gravado,2), "exonerado"=>round($exonerado,2), "inafecto"=>round($inafecto,2), "igv"=>round($igv,2), "pago"=>$sale["formapago"]]);
            $saleId = (int)$this->db->lastInsertId();
            $detail = $this->db->prepare("INSERT INTO detalle_venta (idventa,idproducto,cantidad,unidad,pventa,igv,total,idafectacion)
                                           VALUES (:venta,:producto,:cantidad,:unidad,:precio,:igv,:total,:afectacion)");
            $stockUpdate = $this->db->prepare("UPDATE producto SET stock = stock - :cantidad WHERE idproducto = :id AND stock >= :disponible");
            foreach ($lines as $line) {
                $p = $line["product"];
                $detail->execute(["venta"=>$saleId,"producto"=>$p["idproducto"],"cantidad"=>$line["cantidad"],"unidad"=>$p["unidad"],
                    "precio"=>$p["pventa"],"igv"=>$line["igv"],"total"=>$line["total"],"afectacion"=>$p["idafectacion"]]);
                $stockUpdate->execute(["cantidad"=>$line["cantidad"],"disponible"=>$line["cantidad"],"id"=>$p["idproducto"]]);
                if ($stockUpdate->rowCount() !== 1) throw new RuntimeException("No se pudo actualizar el stock de " . $p["nombre"] . ".");
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
}
