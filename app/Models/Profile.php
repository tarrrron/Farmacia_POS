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
        return $this->db->query("SELECT p.idperfil, p.nombre, p.estado, p.fecha_creacion,
                                       COUNT(CASE WHEN u.estado = 1 THEN 1 END) AS usuarios_activos
                                FROM perfil p
                                LEFT JOIN usuario u ON u.idperfil = p.idperfil
                                GROUP BY p.idperfil, p.nombre, p.estado, p.fecha_creacion
                                ORDER BY p.idperfil")->fetchAll(PDO::FETCH_ASSOC);
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

        $this->db->beginTransaction();
        try {
            $lockProfile = $this->db->prepare("SELECT idperfil FROM perfil WHERE idperfil = :idperfil FOR UPDATE");
            $lockProfile->execute(["idperfil" => $profileId]);
            if (!$lockProfile->fetchColumn()) {
                throw new DomainException("El perfil ya no existe.");
            }

            if ($status === 0) {
                $assignedUser = $this->db->prepare("SELECT idusuario FROM usuario WHERE idperfil = :idperfil AND estado = 1 LIMIT 1 FOR UPDATE");
                $assignedUser->execute(["idperfil" => $profileId]);
                if ($assignedUser->fetchColumn() !== false) {
                    throw new DomainException("No se puede desactivar ni eliminar este perfil porque tiene usuarios activos asignados. Cambia primero esos usuarios a otro perfil.");
                }
            }

            $statement = $this->db->prepare("UPDATE perfil SET estado = :estado WHERE idperfil = :idperfil");
            $statement->execute(["estado" => $status, "idperfil" => $profileId]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function optionsWithAccess(int $profileId = 0): array
    {
        $sql = "SELECT o.idopcion, o.descripcion, o.url, o.icono,
                       CASE WHEN a.idopcion IS NULL THEN 0 ELSE a.estado END AS permitido
                FROM opcion o
                LEFT JOIN acceso a ON a.idopcion = o.idopcion AND a.idperfil = :idperfil
                WHERE o.estado = 1
                ORDER BY o.idopcion";

        $statement = $this->db->prepare($sql);
        $statement->execute(["idperfil" => $profileId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncAccess(int $profileId, array $optionIds): void
    {
        $validIds = array_map("intval", array_column($this->optionsWithAccess(), "idopcion"));
        $selectedIds = array_values(array_unique(array_filter(array_map("intval", $optionIds))));
        $selectedIds = array_values(array_intersect($selectedIds, $validIds));

        $this->db->beginTransaction();

        try {
            $disable = $this->db->prepare("UPDATE acceso SET estado = 0 WHERE idperfil = :idperfil");
            $disable->execute(["idperfil" => $profileId]);

            $upsert = $this->db->prepare("INSERT INTO acceso (idperfil, idopcion, estado)
                                          VALUES (:idperfil, :idopcion, 1)
                                          ON DUPLICATE KEY UPDATE estado = VALUES(estado)");

            foreach ($selectedIds as $optionId) {
                $upsert->execute([
                    "idperfil" => $profileId,
                    "idopcion" => $optionId
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
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
