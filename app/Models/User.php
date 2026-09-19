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
}
