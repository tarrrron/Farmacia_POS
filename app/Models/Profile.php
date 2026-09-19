<?php
class Profile
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query("SELECT idperfil, nombre, estado, fecha_creacion FROM perfil ORDER BY idperfil")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $profileId): ?array
    {
        $statement = $this->db->prepare("SELECT idperfil, nombre, estado, fecha_creacion FROM perfil WHERE idperfil = :idperfil");
        $statement->execute(["idperfil" => $profileId]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(string $name): int
    {
        $statement = $this->db->prepare("INSERT INTO perfil (nombre, estado) VALUES (:nombre, 1)");
        $statement->execute(["nombre" => $name]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $profileId, string $name): void
    {
        $statement = $this->db->prepare("UPDATE perfil SET nombre = :nombre WHERE idperfil = :idperfil");
        $statement->execute(["nombre" => $name, "idperfil" => $profileId]);
    }

    public function changeStatus(int $profileId, int $status): void
    {
        if (!in_array($status, [0, 1], true)) {
            throw new InvalidArgumentException("Estado de perfil inválido.");
        }

        $statement = $this->db->prepare("UPDATE perfil SET estado = :estado WHERE idperfil = :idperfil");
        $statement->execute(["estado" => $status, "idperfil" => $profileId]);
    }

    public function menuOptions(int $profileId): array
    {
        $sql = "SELECT o.descripcion, o.url, o.icono
                FROM acceso a
                INNER JOIN opcion o ON o.idopcion = a.idopcion
                WHERE a.idperfil = :idperfil
                  AND a.estado = 1
                  AND o.estado = 1
                ORDER BY o.idopcion";

        $statement = $this->db->prepare($sql);
        $statement->execute(["idperfil" => $profileId]);

        return $statement->fetchAll();
    }
}
