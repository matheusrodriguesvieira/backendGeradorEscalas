<?php
class DB
{
    public static function connect()
    {
        $host = 'localhost';
        $user = 'root';
        $base = 'escala';
        $password = '';

        return new PDO("mysql:host={$host};dbname={$base};charset=UTF8;", $user, $password);
    }
}