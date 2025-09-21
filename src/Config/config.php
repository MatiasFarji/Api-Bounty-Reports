<?php
/**
 * config.php
 * Centralized configuration file for Bounty Reports API
 * Loads environment variables defined in ~/.bashrc
 */

// Database credentials from environment
define('DB_USER', getenv('DB_USER') ?: 'default_user');
define('DB_PASS', getenv('DB_PASS') ?: 'default_pass');
define('DB_NAME', getenv('DB_NAME') ?: 'default_db');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');

// General application settings
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');

// Error reporting based on environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}
