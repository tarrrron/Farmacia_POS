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
        return $this->db->query("SELECT idcategoria, nombre, estado, fecha_creacion
                                FROM categoria
                                ORDER BY idcategoria")->fetchAll(PDO::FETCH_ASSOC);
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

        $statement = $this->db->prepare("UPDATE categoria
                                       SET estado = :estado
                                       WHERE idcategoria = :id");
        $statement->execute([
            "estado" => $status,
            "id" => $id
        ]);
    }
}
