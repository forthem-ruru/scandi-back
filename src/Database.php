<?php

namespace App;

use PDO;
use PDOException;

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            // Railway ავტომატურად აწვდის ამ ცვლადებს შენს აპლიკაციას
            // თუ Railway-ზე ხარ, გამოიყენებს მათ, თუ არა - გამოიყენებს შენს ჩაწერილ მნიშვნელობებს
            $host = getenv('MYSQLHOST') ?: 'mainline.proxy.rlwy.net';
            $port = getenv('MYSQLPORT') ?: '26345';
            $db   = getenv('MYSQLDATABASE') ?: 'railway';
            $user = getenv('MYSQLUSER') ?: 'root';
            $pass = getenv('MYSQLPASSWORD') ?: 'hygdLHxOkCDMprCIZjbLigifUBPGkThE';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            
            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Scandiweb-ის ტესტისთვის ჯობია დეტალური შეცდომა არ გამოაჩინო საჯაროდ, 
                // მაგრამ დეველოპმენტისთვის ასე დარჩეს
                throw new PDOException("ბაზასთან კავშირი ვერ დამყარდა.");
            }
        }
        return self::$instance;
    }
}