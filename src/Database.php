<?php

namespace App;

use PDO;
use PDOException;

class Database {
    private static $instance = null;


    public static function getConnection() {
        if (self::$instance === null) {
     
            $host = getenv('MYSQLHOST') ?: '127.0.0.1';
            $port = getenv('MYSQLPORT') ?: '3306';
            $db   = getenv('MYSQLDATABASE') ?: 'railway';
            $user = getenv('MYSQLUSER') ?: 'root';
            $pass = getenv('MYSQLPASSWORD') ?: '';
            $charset = 'utf8mb4';

     
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
                PDO::ATTR_EMULATE_PREPARES   => false,                  
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
               
                throw new PDOException("ბაზასთან კავშირი ვერ დამყარდა: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }
}