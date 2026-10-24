<?php
class Category
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query("SELECT c.idcategoria, c.nombre, c.estado, c.fecha_creacion,
                                       COUNT(p.idproducto) AS productos_asociados
                                FROM categoria c
                                LEFT JOIN producto p ON p.idcategoria = c.idcategoria
                                GROUP BY c.idcategoria, c.nombre, c.estado, c.fecha_creacion
                                ORDER BY c.idcategoria")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function active(): array
    {
        return $this->db->query("SELECT idcategoria, nombre
                                FROM categoria
                                WHERE estado = 1
                                ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT idcategoria, nombre, estado, fecha_creacion
                                       FROM categoria
                                       WHERE idcategoria = :id");
        $statement->execute(["id" => $id]);

        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $statement = $this->db->prepare("SELECT 1
                                       FROM categoria
                                       WHERE nombre = :nombre
                                         AND idcategoria <> :id
                                       LIMIT 1");
        $statement->execute([
            "nombre" => $name,
            "id" => $excludeId
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $name): int
    {
        $statement = $this->db->prepare("INSERT INTO categoria (nombre, estado)
                                       VALUES (:nombre, 1)");
        $statement->execute(["nombre" => $name]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        $statement = $this->db->prepare("UPDATE categoria
                                       SET nombre = :nombre
                                       WHERE idcategoria = :id");
        $statement->execute([
            "nombre" => $name,
            "id" => $id
        ]);
    }

    public function changeStatus(int $id, int $status): void
    {
        if (!in_array($status, [0, 1], true)) {
            throw new InvalidArgumentException("Estado de categoria invalido.");
        }

        $this->db->beginTransaction();
        try {
            $lockCategory = $this->db->prepare("SELECT idcategoria FROM categoria WHERE idcategoria = :id FOR UPDATE");
            $lockCategory->execute(["id" => $id]);
            if (!$lockCategory->fetchColumn()) {
                throw new DomainException("La categoría ya no existe.");
            }

            if ($status === 0) {
                $linkedProduct = $this->db->prepare("SELECT idproducto FROM producto WHERE idcategoria = :id LIMIT 1 FOR UPDATE");
                $linkedProduct->execute(["id" => $id]);
                if ($linkedProduct->fetchColumn() !== false) {
                    throw new DomainException("No se puede desactivar ni eliminar esta categoría porque todavía está asignada a uno o más productos. Reasigna esos productos primero.");
                }
            }

            $statement = $this->db->prepare("UPDATE categoria SET estado = :estado WHERE idcategoria = :id");
            $statement->execute(["estado" => $status, "id" => $id]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
