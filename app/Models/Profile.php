<?php
class Profile
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
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
