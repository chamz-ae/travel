<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

class Database {
    private static ?mysqli $connection = null;

    private const DB_HOST = '127.0.0.1';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_NAME = 'tiranda_jogja';
    private const DB_PORT = 3306;

    public static function getConnection(): mysqli {
        if (self::$connection === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                self::$connection = new mysqli(
                    self::DB_HOST,
                    self::DB_USER,
                    self::DB_PASS,
                    self::DB_NAME,
                    self::DB_PORT
                );
                self::$connection->set_charset('utf8mb4');
            } catch (mysqli_sql_exception $e) {
                if (APP_ENV === 'development') {
                    die('Database Connection Error: ' . $e->getMessage());
                } else {
                    error_log('DB Error: ' . $e->getMessage());
                    die('A critical database error occurred. Please try again later.');
                }
            }
        }
        return self::$connection;
    }
}