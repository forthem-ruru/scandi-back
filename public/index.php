<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->post('/', [App\Controller\GraphQL::class, 'handle']);
    $r->addRoute('OPTIONS', '/', [App\Controller\GraphQL::class, 'handle']);
});

$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        echo $handler[0]::{$handler[1]}();
        break;
    default:
        http_response_code(404);
        break;
}