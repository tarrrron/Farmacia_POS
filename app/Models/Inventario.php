
<?php

class Inventario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function productos(): array
    {
        $sql = "SELECT
                    p.idproducto,
                    p.nombre,
                    p.codigobarra,
                    p.stock,
                    p.stockseguridad,
                    p.pcompra,
                    p.pventa,
                    p.estado,
                    c.nombre AS categoria,
                    u.descripcion AS unidad
                FROM producto p
                INNER JOIN categoria c
                    ON c.idcategoria = p.idcategoria
                INNER JOIN unidad u
                    ON u.idunidad = p.idunidad
                ORDER BY p.nombre ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function productosActivos(): array
    {
        $sql = "SELECT idproducto, nombre, stock
                FROM producto
                WHERE estado = 1
                ORDER BY nombre";

        return $this->db->query($sql)->fetchAll();
    }

    public function resumen(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_productos,
                    COALESCE(SUM(stock), 0) AS unidades,
                    COALESCE(SUM(
                        CASE
                            WHEN stock = 0 THEN 1
                            ELSE 0
                        END
                    ), 0) AS agotados,
                    COALESCE(SUM(
                        CASE
                            WHEN stock > 0
                             AND stock <= stockseguridad
                            THEN 1 ELSE 0
                        END
                    ), 0) AS stock_bajo
                FROM producto
                WHERE estado = 1";

        return $this->db->query($sql)->fetch();
    }

    public function historial(): array
    {
        $sql = "SELECT
                    m.idmovimiento,
                    m.tipo,
                    m.cantidad,
                    m.stock_anterior,
                    m.stock_nuevo,
                    m.motivo,
                    m.fecha,
                    p.nombre AS producto,
                    u.nombre AS usuario
                FROM movimiento_inventario m
                INNER JOIN producto p
                    ON p.idproducto = m.idproducto
                INNER JOIN usuario u
                    ON u.idusuario = m.idusuario
                ORDER BY m.fecha DESC, m.idmovimiento DESC
                LIMIT 100";

        return $this->db->query($sql)->fetchAll();
    }

    public function registrar(
        int $idproducto,
        int $idusuario,
        string $tipo,
        string $cantidad,
        string $motivo
    ): void {
        if (!in_array($tipo, ["ENTRADA", "SALIDA"], true)) {
            throw new InvalidArgumentException("Tipo de movimiento invalido.");
        }

        if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $cantidad)
            || (float) $cantidad <= 0) {
            throw new InvalidArgumentException("Cantidad invalida.");
        }

        if ($motivo === "" || mb_strlen($motivo, "UTF-8") > 255) {
            throw new InvalidArgumentException("Ingresa un motivo valido.");
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT stock
                 FROM producto
                 WHERE idproducto = :id
                   AND estado = 1
                 FOR UPDATE"
            );

            $stmt->execute(["id" => $idproducto]);
            $producto = $stmt->fetch();

            if (!$producto) {
                throw new RuntimeException("Producto no encontrado o inactivo.");
            }

            // Trabajar en centesimas evita errores de coma flotante.
            $aCentimos = static function (string $valor): int {
                $partes = explode(".", $valor, 2);
                return ((int) $partes[0] * 100)
                    + (int) str_pad($partes[1] ?? "", 2, "0");
            };

            $stockAnterior = $aCentimos((string) $producto["stock"]);
            $cantidadEntera = $aCentimos($cantidad);

            $stockNuevo = $tipo === "ENTRADA"
                ? $stockAnterior + $cantidadEntera
                : $stockAnterior - $cantidadEntera;

            if ($stockNuevo < 0) {
                throw new RuntimeException("Stock insuficiente para realizar la salida.");
            }

            if ($stockNuevo > 99999999999) {
                throw new RuntimeException("El stock supera el limite permitido.");
            }

            $formatear = static function (int $valor): string {
                return intdiv($valor, 100)
                    . "." . str_pad((string) ($valor % 100), 2, "0", STR_PAD_LEFT);
            };

            $nuevo = $formatear($stockNuevo);

            $update = $this->db->prepare(
                "UPDATE producto
                 SET stock = :stock
                 WHERE idproducto = :id"
            );

            $update->execute([
                "stock" => $nuevo,
                "id" => $idproducto
            ]);

            $insert = $this->db->prepare(
                "INSERT INTO movimiento_inventario
                 (idproducto, idusuario, tipo, cantidad,
                  stock_anterior, stock_nuevo, motivo)
                 VALUES
                 (:idproducto, :idusuario, :tipo, :cantidad,
                  :anterior, :nuevo, :motivo)"
            );

            $insert->execute([
                "idproducto" => $idproducto,
                "idusuario" => $idusuario,
                "tipo" => $tipo,
                "cantidad" => $cantidad,
                "anterior" => $formatear($stockAnterior),
                "nuevo" => $nuevo,
                "motivo" => $motivo
            ]);

            $this->db->commit();

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
