<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
$autoload = __DIR__ . '/../vendor/autoload.php';

if (! file_exists($autoload)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');

    echo "Composer dependencies are missing.\n";
    echo "Run 'docker compose exec -T app composer run setup' or restart the container to let the entrypoint install vendor dependencies.\n";

    exit(1);
}

require $autoload;

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
