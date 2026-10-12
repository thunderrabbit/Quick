<?php

const CONFIG_DATABASE_OUTPUT_ENCODING = "UTF8";

class Base
{
    private static $db;

    // One day \Base should be replaced with a dependency injection wrapper, but not today.
    // e.g. https://github.com/mlaphp/mlaphp/blob/master/src/Mlaphp/Di.php
    private static function initDB(Config $config)
    {
        /** START - Database **/
        if (empty(self::$db)) {
            self::$db = new \Database\Database(
                $config->dbHost,
                $config->dbUser,
                $config->dbPass,
                $config->dbName,
                CONFIG_DATABASE_OUTPUT_ENCODING
            );
        }
        /** END - Database **/
    }

    public static function getDB(Config $config): \Database\Database
    {
        self::initDB($config);
        return self::$db;
    }

    private static ?\PDO $pdo = null;

    /**
     * A PDO connection to the same database, for the classes copied from
     * new-DH-user-site (Auth\LoginThrottle, Database\CookieRepository). Their
     * SQL is portable, so the unit suite runs it on an in-memory SQLite.
     */
    public static function getPDO(Config $config): \PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new \PDO(
                "mysql:host={$config->dbHost};dbname={$config->dbName};charset=utf8mb4",
                $config->dbUser,
                $config->dbPass,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]
            );
        }
        return self::$pdo;
    }
}
