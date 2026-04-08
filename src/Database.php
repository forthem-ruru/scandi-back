<?php

namespace App;

use PDO;
use Dotenv\Dotenv;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // 1. ვტვირთავთ .env ფაილს მხოლოდ იმ შემთხვევაში, თუ ის არსებობს (ლოკალური ტესტირებისთვის)
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->safeLoad();
        }

        // 2. ვიყენებთ getenv() ან $_ENV-ს. 
        // Railway-ს ცვლადებს პრიორიტეტს ვაძლევთ (fallback-ებით)
        $host = getenv('MYSQLHOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
        $db   = getenv('MYSQLDATABASE') ?: ($_ENV['DB_NAME'] ?? 'railway');
        $user = getenv('MYSQLUSER') ?: ($_ENV['DB_USER'] ?? 'root');
        $pass = getenv('MYSQLPASSWORD') ?: ($_ENV['DB_PASS'] ?? '');
        $port = getenv('MYSQLPORT') ?: ($_ENV['DB_PORT'] ?? '3306');

        // 3. DSN-ში პორტის დამატება აუცილებელია
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        
        try {
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // შეცდომის ტექსტი გამოჩნდება მხოლოდ დეველოპმენტისას
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
}