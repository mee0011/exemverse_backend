<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/php/logs/php_error_log');

// Enable error reporting for debugging
error_reporting(E_ALL);

// Include the API routes
require_once __DIR__ . '/../routes/api.php';
?>