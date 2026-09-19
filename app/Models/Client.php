<?php
class Client
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query("SELECT c.idcliente, c.nombre, c.nombre_comercial, c.razon_social,
                                       c.nrodocumento, c.direccion, c.estado,
                                       td.nombre AS tipo_documento
                                FROM cliente c
                                INNER JOIN tipodocumento td ON td.idtipodocumento = c.idtipodocumento
                                ORDER BY c.idcliente")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare("SELECT idcliente, nombre, nombre_comercial, razon_social,
                                               idtipodocumento, nrodocumento, direccion,
                                               departamento, provincia, distrito, estado
                                        FROM cliente
                                        WHERE idcliente = :id");
        $statement->execute(["id" => $id]);

        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function documentExists(string $document, int $excludeId = 0): bool
    {
        $statement = $this->db->prepare("SELECT 1
                                       FROM cliente
                                       WHERE nrodocumento = :documento
                                         AND idcliente <> :id
                                       LIMIT 1");
        $statement->execute([
            "documento" => $document,
            "id" => $excludeId
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function documentTypes(): array
    {
        return $this->db->query("SELECT idtipodocumento, nombre
                                FROM tipodocumento
                                WHERE estado = 1
                                ORDER BY idtipodocumento")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $client): int
    {
        $statement = $this->db->prepare("INSERT INTO cliente
            (nombre, nombre_comercial, razon_social, idtipodocumento, nrodocumento,
             direccion, departamento, provincia, distrito, estado)
            VALUES
            (:nombre, :nombre_comercial, :razon_social, :idtipodocumento, :nrodocumento,
             :direccion, :departamento, :provincia, :distrito, 1)");
        $statement->execute($this->params($client));

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $client): void
    {
        $params = $this->params($client);
        $params["id"] = $id;

        $statement = $this->db->prepare("UPDATE cliente
            SET nombre = :nombre,
                nombre_comercial = :nombre_comercial,
                razon_social = :razon_social,
                idtipodocumento = :idtipodocumento,
                nrodocumento = :nrodocumento,
                direccion = :direccion,
                departamento = :departamento,
                provincia = :provincia,
                distrito = :distrito
            WHERE idcliente = :id");
        $statement->execute($params);
    }

    public function changeStatus(int $id, int $status): void
    {
        if (!in_array($status, [0, 1], true)) {
            throw new InvalidArgumentException("Estado de cliente invalido.");
        }

        $statement = $this->db->prepare("UPDATE cliente SET estado = :estado WHERE idcliente = :id");
        $statement->execute([
            "estado" => $status,
            "id" => $id
        ]);
    }

    private function params(array $client): array
    {
        return [
            "nombre" => $client["nombre"],
            "nombre_comercial" => $client["nombre_comercial"] !== "" ? $client["nombre_comercial"] : null,
            "razon_social" => $client["razon_social"] !== "" ? $client["razon_social"] : null,
            "idtipodocumento" => $client["idtipodocumento"],
            "nrodocumento" => $client["nrodocumento"],
            "direccion" => $client["direccion"] !== "" ? $client["direccion"] : null,
            "departamento" => $client["departamento"] !== "" ? $client["departamento"] : null,
            "provincia" => $client["provincia"] !== "" ? $client["provincia"] : null,
            "distrito" => $client["distrito"] !== "" ? $client["distrito"] : null
        ];
    }
}
