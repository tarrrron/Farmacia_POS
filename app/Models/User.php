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
        $statement = $this->db->prepare("SELECT idusuario, nombre, usuario, idperfil, clave
                                         FROM usuario
                                         WHERE usuario = :usuario
                                           AND estado = 1");
        $statement->execute(["usuario" => $username]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return null;
        }

        $storedHash = (string) $user["clave"];
        $valid = password_verify($password, $storedHash);
        $isLegacySha1 = !$valid && preg_match('/^[a-f0-9]{40}$/i', $storedHash) === 1
            && hash_equals(strtolower($storedHash), sha1($password));

        if (!$valid && !$isLegacySha1) {
            return null;
        }

        // Las claves antiguas en SHA1 se convierten a password_hash al iniciar sesión.
        if ($isLegacySha1 || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            $rehash = $this->db->prepare("UPDATE usuario SET clave = :clave WHERE idusuario = :id");
            $rehash->execute(["clave" => password_hash($password, PASSWORD_DEFAULT), "id" => $user["idusuario"]]);
        }

        unset($user["clave"]);
        return $user;
    }

    public function isActive(int $id): bool
    {
        $statement = $this->db->prepare("SELECT 1 FROM usuario WHERE idusuario = :id AND estado = 1");
        $statement->execute(["id" => $id]);
        return $statement->fetchColumn() !== false;
    }

    public function all(): array
    {
        return $this->db->query("SELECT u.idusuario, u.nombre, u.usuario, p.nombre AS perfil, u.estado
                                FROM usuario u
                                LEFT JOIN perfil p ON p.idperfil = u.idperfil
                                WHERE u.estado <> 2
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
        $statement = $this->db->prepare("SELECT u.idusuario, u.nombre, u.usuario, u.idperfil, u.estado, p.nombre AS perfil
                                        FROM usuario u LEFT JOIN perfil p ON p.idperfil = u.idperfil
                                        WHERE u.idusuario = :id");
        $statement->execute(["id" => $id]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, string $name, string $username, int $profileId, string $password = ""): void
    {
        $params = ["id" => $id, "nombre" => $name, "usuario" => $username, "idperfil" => $profileId];
        $passwordSql = "";
        if ($password !== "") {
            $passwordSql = ", clave = :clave";
            $params["clave"] = password_hash($password, PASSWORD_DEFAULT);
        }
        $this->db->beginTransaction();
        try {
            $this->lockActiveProfile($profileId);
            $statement = $this->db->prepare("UPDATE usuario SET nombre = :nombre, usuario = :usuario,
                                           idperfil = :idperfil" . $passwordSql . " WHERE idusuario = :id");
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
        if (!in_array($status, [0, 1, 2], true)) {
            throw new InvalidArgumentException("Estado de usuario inválido.");
        }

        if ($status !== 1) {
            $statement = $this->db->prepare("UPDATE usuario SET estado = :estado WHERE idusuario = :id");
            $statement->execute(["estado" => $status, "id" => $id]);
            return;
        }

        $this->db->beginTransaction();
        try {
            $userQuery = $this->db->prepare("SELECT idperfil FROM usuario WHERE idusuario = :id FOR UPDATE");
            $userQuery->execute(["id" => $id]);
            $profileId = $userQuery->fetchColumn();
            if ($profileId === false) {
                throw new InvalidArgumentException("El usuario ya no existe.");
            }
            $this->lockActiveProfile((int) $profileId);
            $statement = $this->db->prepare("UPDATE usuario SET estado = 1 WHERE idusuario = :id");
            $statement->execute(["id" => $id]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function create(string $name, string $username, string $password, int $profileId): int
    {
        $this->db->beginTransaction();
        try {
            $this->lockActiveProfile($profileId);
            $statement = $this->db->prepare("INSERT INTO usuario (nombre, usuario, clave, idperfil, estado)
                                           VALUES (:nombre, :usuario, :clave, :idperfil, 1)");
            $statement->execute([
                "nombre" => $name,
                "usuario" => $username,
                "clave" => password_hash($password, PASSWORD_DEFAULT),
                "idperfil" => $profileId
            ]);
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

    private function lockActiveProfile(int $profileId): void
    {
        $statement = $this->db->prepare("SELECT idperfil FROM perfil WHERE idperfil = :idperfil AND estado = 1 FOR UPDATE");
        $statement->execute(["idperfil" => $profileId]);
        if ($statement->fetchColumn() === false) {
            throw new InvalidArgumentException("Selecciona un perfil activo válido.");
        }
    }
}
