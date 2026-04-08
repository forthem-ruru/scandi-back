<?php

namespace App;

use PDO;
use Dotenv\Dotenv;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

   private function __construct() {
    // 1. ვტვირთავთ .env-ს (მხოლოდ ლოკალურად მუშაობს)
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }

    // 2. ვიღებთ მნიშვნელობებს. Railway-ზე Variables-შიც დაარქვი DB_HOST, DB_NAME და ა.შ.
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
    $db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER');
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    try {
        $this->connection = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // აქ დავამატოთ დიაგნოსტიკა, რომ გვითხრას რომელ ჰოსტს ეჯახება
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