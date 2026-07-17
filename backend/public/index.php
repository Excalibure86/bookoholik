<?php
/**
 * Bookoholik: Home Library Management System - Entry Point
 * 
 * All requests are routed through this file.
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
use App\Config\Database;
use App\Router;

// Load .env if available
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Initialize router
$router = new Router();

// Load routes
require_once __DIR__ . '/../routes/api.php';

// Dispatch request
$router->dispatch();
