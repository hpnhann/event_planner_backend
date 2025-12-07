<?php
header('Content-Type: application/json');
require_once 'middleware/CORS.php';
require_once 'config/database.php';
require_once 'utils/Response.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/backend', '', $path);

// Simple routing
$routes = [
    'POST /api/auth/login' => 'controllers/AuthController.php@login',
    'POST /api/auth/register' => 'controllers/AuthController.php@register',
    'POST /api/auth/logout' => 'controllers/AuthController.php@logout',
    'POST /api/auth/forgot-password' => 'controllers/AuthController.php@forgotPassword',
    
    'GET /api/events' => 'controllers/EventController.php@getAll',
    'GET /api/events/public' => 'controllers/EventController.php@getPublic',
    'GET /api/events/:id' => 'controllers/EventController.php@getDetail',
    'POST /api/events' => 'controllers/EventController.php@create',
    'POST /api/events/:id/register' => 'controllers/EventController.php@register',
    'PUT /api/events/:id' => 'controllers/EventController.php@update',
    'DELETE /api/events/:id' => 'controllers/EventController.php@delete',
    
    'GET /api/users' => 'controllers/UserController.php@getAll',
    'GET /api/users/:id' => 'controllers/UserController.php@getById',
    'POST /api/users' => 'controllers/UserController.php@create',
    'PUT /api/users/:id' => 'controllers/UserController.php@update',
    'DELETE /api/users/:id' => 'controllers/UserController.php@delete',
    
    'GET /api/notices' => 'controllers/NoticeController.php@getAll',
    'GET /api/notices/:id' => 'controllers/NoticeController.php@getById',
    'POST /api/notices' => 'controllers/NoticeController.php@create',
    'PUT /api/notices/:id' => 'controllers/NoticeController.php@update',
    'DELETE /api/notices/:id' => 'controllers/NoticeController.php@delete',
    
    'POST /api/upload' => 'controllers/UploadController.php@upload',
];

// Match route
$handler = null;
$params = [];

foreach ($routes as $route => $controller) {
    list($routeMethod, $routePath) = explode(' ', $route);
    
    if ($routeMethod !== $method) continue;
    
    // Convert route pattern to regex
    $pattern = preg_replace('/:\w+/', '([^/]+)', $routePath);
    $pattern = '#^' . $pattern . '$#';
    
    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches); // Remove full match
        $params = $matches;
        $handler = $controller;
        break;
    }
}

if (!$handler) {
    Response::error('Route not found', 404);
    exit;
}

// Execute controller
list($controllerFile, $action) = explode('@', $handler);
require_once $controllerFile;

$controllerName = basename($controllerFile, '.php');
$controller = new $controllerName();

if (!method_exists($controller, $action)) {
    Response::error('Method not found', 404);
    exit;
}

call_user_func_array([$controller, $action], $params);