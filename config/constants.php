<?php

declare(strict_types=1);

// Session configuration - will be used by Session class
define('SESSION_CONFIG', [
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'sid_length' => 48,
    'sid_bits_per_character' => 6
]);

// Environment detection with PHP 8.3 features
final readonly class Environment
{
    public const LOCAL_HOSTS = ['localhost', 'tm.softkit.io.test', '127.0.0.1'];

    public static function isLocal(): bool
    {
        return in_array($_SERVER['HTTP_HOST'] ?? '', self::LOCAL_HOSTS, true);
    }

    public static function isProduction(): bool
    {
        return !self::isLocal();
    }

    public static function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }
}

// Database configuration constants
define('LOCALHOST', 'localhost');

// Conditional database credentials based on environment
if (Environment::isLocal()) {
    // Local development environment
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'task_manager');
} else {
    // Live server environment
    define('DB_USERNAME', 'softkitx_task_manager_user');
    define('DB_PASSWORD', 'Gv8#zPq9Xr!mL2');
    define('DB_NAME', 'softkitx_task_manager');
}

// Site URL configuration with proper protocol detection
if (Environment::isLocal()) {
    // Local development
    define('SITEURL', 'http://localhost/tm.softkit.io/');
} else {
    // Live server with HTTPS
    define('SITEURL', 'https://tm.softkit.io/');
}

// Additional configuration constants
define('APP_NAME', 'Task Manager');
define('APP_VERSION', '2.0.0');
define('TIMEZONE', 'UTC');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting configuration
if (Environment::isLocal()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

/**
 * Set security headers for production environment
 * Call this function after session_start() to avoid header conflicts
 */
function setSecurityHeaders(): void
{
    if (Environment::isProduction() && !headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdnjs.cloudflare.com cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdnjs.cloudflare.com fonts.googleapis.com; font-src \'self\' fonts.gstatic.com; img-src \'self\' data:;');
    }
}

// Automatically set security headers for production (after session handling)
register_shutdown_function('setSecurityHeaders');
