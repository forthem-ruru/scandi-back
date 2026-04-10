<?php
require_once __DIR__ . '/../vendor/autoload.php';

// CORS Header-ები სულ თავში, რომ 404-ის დროსაც არ დაიბლოკოს
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// OPTIONS მოთხოვნის სწრაფი პასუხი
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // ვუსმენთ სხვადასხვა შესაძლო ენდპოინტს
    $r->addRoute(['GET', 'POST', 'OPTIONS'], '/', [App\Controller\GraphQL::class, 'handle']);
    $r->addRoute(['GET', 'POST', 'OPTIONS'], '/index.php', [App\Controller\GraphQL::class, 'handle']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Railway-სთვის დამატებითი გასუფთავება
$uri = str_replace('/public', '', $uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // ვიძახებთ კონტროლერს
        echo call_user_func_array($handler, $vars);
        break;
    default:
        // თუ როუტერი დაიბნა, მაინც ვცადოთ GraphQL-ის გამოძახება
        echo App\Controller\GraphQL::handle();
        break;
}