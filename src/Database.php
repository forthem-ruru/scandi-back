<?php

namespace App;

use PDO;
use Dotenv\Dotenv;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

   private function __construct() {
    // 1. .env-ს ჩატვირთვა (მხოლოდ ლოკალური ტესტირებისთვის)
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }

    // 2. პრიორიტეტი მივანიჭოთ ზუსტად იმ სახელებს, რაც Railway-ზე გიწერია
    // getenv() ეძებს სისტემურ ცვლადებს, $_ENV კი .env ფაილს
    $host = getenv('MYSQLHOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
    $db   = getenv('MYSQLDATABASE') ?: ($_ENV['DB_NAME'] ?? 'railway');
    $user = getenv('MYSQLUSER') ?: ($_ENV['DB_USER'] ?? 'root');
    $pass = getenv('MYSQLPASSWORD') ?: ($_ENV['DB_PASS'] ?? '');
    $port = getenv('MYSQLPORT') ?: ($_ENV['DB_PORT'] ?? '3306');

    // დიაგნოსტიკისთვის: თუ მაინც ცარიელია host, ნიშნავს რომ getenv ვერ ხედავს
    if (empty($host)) {
        die("Critical Error: MYSQLHOST variable is missing in the environment.");
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    try {
        $this->connection = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ATTR_ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (\PDOException $e) {
        die("Database Connection Error to [$host]: " . $e->getMessage());
    }
}

    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
}