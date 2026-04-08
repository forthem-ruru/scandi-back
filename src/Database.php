<?php

namespace App;

use PDO;
use Dotenv\Dotenv;

class Database {
    private static $instance = null;
    private $connection;


    private function __construct() {
      $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        $host = $_ENV['DB_HOST'];
        $db   = $_ENV['DB_NAME'];
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];

        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $this->connection = new PDO($dsn, $user, $pass);
    }


    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
}