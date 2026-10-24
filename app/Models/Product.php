<?php
class Product
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query("SELECT p.idproducto, p.nombre, p.codigobarra, p.urlimagen, p.pventa, p.pcompra,
                                       p.stock, p.stockseguridad, p.estado,
                                       c.nombre AS categoria, u.descripcion AS unidad,
                                       a.descripcion AS afectacion
                                FROM producto p
                                INNER JOIN categoria c ON c.idcategoria = p.idcategoria
                                INNER JOIN unidad u ON u.idunidad = p.idunidad
                                INNER JOIN afectacion a ON a.idafectacion = p.idafectacion
                                ORDER BY p.idproducto")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT idproducto, nombre, codigobarra, urlimagen, pventa, pcompra,
                                               stock, stockseguridad, idunidad, idcategoria,
                                               idafectacion, estado
                                        FROM producto
                                        WHERE idproducto = :id");
        $statement->execute(["id" => $id]);

        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $statement = $this->db->prepare("SELECT 1
                                       FROM producto
                                       WHERE nombre = :nombre
                                         AND idproducto <> :id
                                       LIMIT 1");
        $statement->execute([
            "nombre" => $name,
            "id" => $excludeId
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function barcodeExists(?string $barcode, int $excludeId = 0): bool
    {
        if ($barcode === null || $barcode === "") {
            return false;
        }

        $statement = $this->db->prepare("SELECT 1
                                       FROM producto
                                       WHERE codigobarra = :codigobarra
                                         AND idproducto <> :id
                                       LIMIT 1");
        $statement->execute([
            "codigobarra" => $barcode,
            "id" => $excludeId
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function units(): array
    {
        return $this->db->query("SELECT idunidad, descripcion
                                FROM unidad
                                WHERE estado = 1
                                ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function taxAffectations(): array
    {
        return $this->db->query("SELECT idafectacion, codigo, descripcion
                                FROM afectacion
                                WHERE estado = 1
                                ORDER BY idafectacion")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $product): int
    {
        $this->db->beginTransaction();
        try {
            $this->lockActiveCategory((int) $product["idcategoria"]);
            $statement = $this->db->prepare("INSERT INTO producto
                (nombre, codigobarra, pventa, pcompra, stock, stockseguridad,
                 idunidad, idcategoria, idafectacion, urlimagen, estado)
                VALUES
                (:nombre, :codigobarra, :pventa, :pcompra, :stock, :stockseguridad,
                 :idunidad, :idcategoria, :idafectacion, :urlimagen, 1)");
            $statement->execute($this->params($product));
            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function update(int $id, array $product): void
    {
        $params = $this->params($product);
        $params["id"] = $id;

        $this->db->beginTransaction();
        try {
            $this->lockActiveCategory((int) $product["idcategoria"]);
            $statement = $this->db->prepare("UPDATE producto
                SET nombre = :nombre,
                    codigobarra = :codigobarra,
                    pventa = :pventa,
                    pcompra = :pcompra,
                    stock = :stock,
                    stockseguridad = :stockseguridad,
                    idunidad = :idunidad,
                    idcategoria = :idcategoria,
                    idafectacion = :idafectacion,
                    urlimagen = :urlimagen
                WHERE idproducto = :id");
            $statement->execute($params);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function changeStatus(int $id, int $status): void
    {
        if (!in_array($status, [0, 1], true)) {
            throw new InvalidArgumentException("Estado de producto invalido.");
        }

        $statement = $this->db->prepare("UPDATE producto
                                       SET estado = :estado
                                       WHERE idproducto = :id");
        $statement->execute([
            "estado" => $status,
            "id" => $id
        ]);
    }

    private function params(array $product): array
    {
        return [
            "nombre" => $product["nombre"],
            "codigobarra" => $product["codigobarra"] !== "" ? $product["codigobarra"] : null,
            "pventa" => $product["pventa"],
            "pcompra" => $product["pcompra"],
            "stock" => $product["stock"],
            "stockseguridad" => $product["stockseguridad"],
            "idunidad" => $product["idunidad"],
            "idcategoria" => $product["idcategoria"],
            "idafectacion" => $product["idafectacion"],
            "urlimagen" => $product["urlimagen"] ?? null
        ];
    }

    private function lockActiveCategory(int $categoryId): void
    {
        $statement = $this->db->prepare("SELECT idcategoria FROM categoria WHERE idcategoria = :id AND estado = 1 FOR UPDATE");
        $statement->execute(["id" => $categoryId]);
        if ($statement->fetchColumn() === false) {
            throw new InvalidArgumentException("Selecciona una categoría activa para el producto.");
        }
    }

   public function getTopProductos($mes = null) {
        $sql = "SELECT p.nombre, p.codigobarra, p.stock, SUM(d.cantidad) as total_vendido 
                FROM producto p 
                INNER JOIN detalle_venta d ON p.idproducto = d.idproducto 
                INNER JOIN venta v ON d.idventa = v.idventa ";
        
        $sql .= " WHERE v.estado = 1 AND d.estado = 1 ";

        if (!empty($mes)) {
            $sql .= " AND MONTH(v.fecha) = :mes ";
        }
        
        $sql .= " GROUP BY p.idproducto 
                  ORDER BY total_vendido DESC 
                  LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($mes)) {
            $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
