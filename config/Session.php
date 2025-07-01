<?php

declare(strict_types=1);

require_once 'Enums.php';

/**
 * Modern Session management class with PHP 8.3 features
 */
final class Session
{
    private const FLASH_KEY = '_flash_messages';
    
    /**
     * Start session if not already started with secure configuration
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Use session configuration from constants.php
            $config = defined('SESSION_CONFIG') ? SESSION_CONFIG : [];
            
            try {
                // Check if headers have already been sent
                if (headers_sent($file, $line)) {
                    error_log("Warning: Headers already sent in {$file} on line {$line}. Session may not work properly.");
                    // Try to start session anyway, but suppress the warning
                    @session_start($config);
                } else {
                    session_start($config);
                }
            } catch (Error $e) {
                // Handle session decode errors by clearing corrupted session
                if (str_contains($e->getMessage(), 'Failed to decode session')) {
                    self::clearCorruptedSession();
                    if (headers_sent()) {
                        @session_start($config);
                    } else {
                        session_start($config);
                    }
                } else {
                    throw $e;
                }
            }
        }
    }
    
    /**
     * Clear corrupted session data
     */
    private static function clearCorruptedSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    /**
     * Set session value with type safety
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value with default fallback
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Clear all session data
     */
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }
    
    /**
     * Destroy session completely
     */
    public static function destroy(): void
    {
        self::start();
        session_destroy();
        $_SESSION = [];
    }
    
    /**
     * Regenerate session ID for security
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::start();
        session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Set flash message with type
     */
    public static function setFlash(AlertType $type, string $message): void
    {
        self::start();
        $_SESSION[self::FLASH_KEY][] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get all flash messages and clear them
     */
    public static function getFlashMessages(): array
    {
        self::start();
        $messages = $_SESSION[self::FLASH_KEY] ?? [];
        unset($_SESSION[self::FLASH_KEY]);
        return $messages;
    }
    
    /**
     * Check if there are flash messages
     */
    public static function hasFlashMessages(): bool
    {
        self::start();
        return !empty($_SESSION[self::FLASH_KEY]);
    }
    
    /**
     * Set success flash message
     */
    public static function setSuccess(string $message): void
    {
        self::setFlash(AlertType::SUCCESS, $message);
    }
    
    /**
     * Set error flash message
     */
    public static function setError(string $message): void
    {
        self::setFlash(AlertType::ERROR, $message);
    }
    
    /**
     * Set warning flash message
     */
    public static function setWarning(string $message): void
    {
        self::setFlash(AlertType::WARNING, $message);
    }
    
    /**
     * Set info flash message
     */
    public static function setInfo(string $message): void
    {
        self::setFlash(AlertType::INFO, $message);
    }
    
    /**
     * Get session ID
     */
    public static function getId(): string
    {
        self::start();
        return session_id();
    }
    
    /**
     * Get session name
     */
    public static function getName(): string
    {
        return session_name();
    }
    
    /**
     * Check if session is active
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Get all session data (for debugging)
     */
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        self::set('csrf_token', $token);
        return $token;
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        $sessionToken = self::get('csrf_token');
        return $sessionToken !== null && hash_equals($sessionToken, $token);
    }
    
    /**
     * Get CSRF token (generate if not exists)
     */
    public static function getCsrfToken(): string
    {
        return self::get('csrf_token') ?? self::generateCsrfToken();
    }
}