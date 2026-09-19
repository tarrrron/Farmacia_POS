<?php
class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findActiveByCredentials(string $username, string $password): ?array
    {
        $sql = "SELECT idusuario, nombre, usuario, idperfil
                FROM usuario
                WHERE usuario = :usuario
                  AND clave = SHA1(:clave)
                  AND estado = 1";

        $statement = $this->db->prepare($sql);
        $statement->execute([
            "usuario" => $username,
            "clave" => $password
        ]);

        $user = $statement->fetch();
        return $user ?: null;
    }

    public function all(): array
    {
        return $this->db->query("SELECT u.idusuario, u.nombre, u.usuario, p.nombre AS perfil, u.estado
                                FROM usuario u
                                LEFT JOIN perfil p ON p.idperfil = u.idperfil
                                ORDER BY u.idusuario")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeProfiles(): array
    {
        return $this->db->query("SELECT idperfil, nombre FROM perfil WHERE estado = 1 ORDER BY nombre")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function usernameExists(string $username, int $excludeId = 0): bool
    {
        $statement = $this->db->prepare("SELECT 1 FROM usuario WHERE usuario = :usuario AND idusuario <> :id LIMIT 1");
        $statement->execute(["usuario" => $username, "id" => $excludeId]);
        return $statement->fetchColumn() !== false;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT idusuario, nombre, usuario, idperfil, estado FROM usuario WHERE idusuario = :id");
        $statement->execute(["id" => $id]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, string $name, string $username, int $profileId, string $password = ""): void
    {
        $statement = $this->db->prepare("SELECT 1 FROM perfil WHERE idperfil = :idperfil AND estado = 1");
        $statement->execute(["idperfil" => $profileId]);
        if (!$statement->fetchColumn()) {
            throw new InvalidArgumentException("Selecciona un perfil activo válido.");
        }
        $params = ["id" => $id, "nombre" => $name, "usuario" => $username, "idperfil" => $profileId];
        $passwordSql = "";
        if ($password !== "") {
            $passwordSql = ", clave = SHA1(:clave)";
            $params["clave"] = $password;
        }
        $statement = $this->db->prepare("UPDATE usuario SET nombre = :nombre, usuario = :usuario,
                                       idperfil = :idperfil" . $passwordSql . " WHERE idusuario = :id");
        $statement->execute($params);
    }

    public function changeStatus(int $id, int $status): void
    {
        if (!in_array($status, [0, 1], true)) {
            throw new InvalidArgumentException("Estado de usuario inválido.");
        }
        $statement = $this->db->prepare("UPDATE usuario SET estado = :estado WHERE idusuario = :id");
        $statement->execute(["estado" => $status, "id" => $id]);
    }

    public function create(string $name, string $username, string $password, int $profileId): int
    {
        $statement = $this->db->prepare("INSERT INTO usuario (nombre, usuario, clave, idperfil, estado)
                                       SELECT :nombre, :usuario, SHA1(:clave), idperfil, 1
                                       FROM perfil WHERE idperfil = :idperfil AND estado = 1");
        $statement->execute([
            "nombre" => $name,
            "usuario" => $username,
            "clave" => $password,
            "idperfil" => $profileId
        ]);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException("Selecciona un perfil activo válido.");
        }
        return (int) $this->db->lastInsertId();
    }
}
