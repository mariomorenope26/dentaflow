<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Si la aplicación aún no ha sido instalada y existe install.php, redirigir al asistente antes de cargar Composer/Laravel
$envFile = __DIR__.'/../.env';
$installedLock = __DIR__.'/../storage/installed.lock';
$installerScript = __DIR__.'/install.php';
$hasAppKey = is_file($envFile)
    && preg_match('/^APP_KEY=\S+/m', (string) @file_get_contents($envFile)) === 1;

if (! is_file($installedLock) && ! $hasAppKey && is_file($installerScript)) {
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Location: '.($scriptDir === '' ? '' : $scriptDir).'/install.php', true, 302);
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
