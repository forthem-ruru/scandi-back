<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // დავამატოთ GET, POST და OPTIONS
    $r->addRoute(['GET', 'POST', 'OPTIONS'], '/', [App\Controller\GraphQL::class, 'handle']);
    // ზოგჯერ Railway-ზე მოთხოვნა მოდის ცარიელზე, ზოგჯერ /index.php-ზე
    $r->addRoute(['GET', 'POST', 'OPTIONS'], '/index.php', [App\Controller\GraphQL::class, 'handle']);
});

// REQUEST_URI-ს გასუფთავება (Query string-ის მოცილება)
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // გამოვიძახოთ კონტროლერი
        echo $handler[0]::{$handler[1]}($vars);
        break;
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(["error" => "Route not found: $uri"]);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        break;
}