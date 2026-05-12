<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('ROOT', dirname(__DIR__));

// Allow token-based session: Authorization: Bearer <session_id>
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $m)) {
    session_id($m[1]);
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT . '/settings/connect_database.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Auth.php';

$routes = [];

function addRoute(string $method, string $pattern, callable $handler): void
{
    global $routes;
    $routes[] = [$method, $pattern, $handler];
}

require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/dishes.php';
require_once __DIR__ . '/routes/orders.php';
require_once __DIR__ . '/routes/users.php';
require_once __DIR__ . '/routes/employees.php';
require_once __DIR__ . '/routes/stats.php';

// Parse the path segment after /api
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^.*/api#', '', $uri);
$uri = '/' . trim($uri, '/');

$method = $_SERVER['REQUEST_METHOD'];

foreach ($routes as [$routeMethod, $pattern, $handler]) {
    if ($routeMethod !== $method) {
        continue;
    }
    $regex = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern) . '$#';
    if (preg_match($regex, $uri, $matches)) {
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        $handler($params);
        exit;
    }
}

Response::json(['error' => 'Endpoint не найден'], 404);
