<?php
class Database
{
    public static function connect(): PDO
    {
        $host = "localhost";
        $database = "farmacia_pos";
        $user = "root";
        $password = "";
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
    }
}
