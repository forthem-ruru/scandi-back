<?php
require_once __DIR__ . '/../vendor/autoload.php';

// 1. CORS Header-ები პირდაპირ აქ, რომ ნებისმიერ შემთხვევაში (404-ზეც კი) იმუშაოს
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// 2. OPTIONS მოთხოვნის დახურვა დაუყოვნებლივ
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // ვუსმენთ მთავარ გვერდს და /index.php-ს
    $r->addRoute(['GET', 'POST'], '/', [App\Controller\GraphQL::class, 'handle']);
    $r->addRoute(['GET', 'POST'], '/index.php', [App\Controller\GraphQL::class, 'handle']);
});

// URI-ს გასუფთავება
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// მოვაცილოთ Query String (?...)
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Railway-ზე ხშირად საჭიროა /public/ ნაწილის მოცილება, თუ სერვერი არასწორადაა მიმართული
$uri = str_replace('/public', '', $uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        echo $handler[0]::{$handler[1]}();
        break;
    default:
        // თუ მაინც ვერ იპოვა Route, მაინც ვცადოთ GraphQL-ის გაშვება
        // ეს არის "Fallback" მექანიზმი
        echo App\Controller\GraphQL::handle();
        break;
}