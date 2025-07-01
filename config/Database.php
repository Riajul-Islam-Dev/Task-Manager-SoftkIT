<?php

declare(strict_types=1);

require_once 'constants.php';

/**
 * Modern Database connection class using PHP 8.3 features
 */
final class Database
{
    private static ?PDO $connection = null;
    
    public function __construct(
        private string $host = LOCALHOST,
        private string $username = DB_USERNAME,
        private string $password = DB_PASSWORD,
        private string $database = DB_NAME
    ) {}
    
    /**
     * Get PDO connection instance (singleton pattern)
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $instance = new self();
            self::$connection = $instance->createConnection();
        }
        
        return self::$connection;
    }
    
    /**
     * Create new PDO connection with proper error handling
     */
    private function createConnection(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new DatabaseException("Unable to connect to database", previous: $e);
        }
    }
    
    /**
     * Execute a prepared statement with parameters
     */
    public static function execute(string $sql, array $params = []): PDOStatement
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::execute($sql, $params)->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::execute($sql, $params)->fetchAll();
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }
}

/**
 * Custom exception for database errors
 */
class DatabaseException extends Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}