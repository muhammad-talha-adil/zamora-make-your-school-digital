<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| hPanel split-folder deployment
|--------------------------------------------------------------------------
|
| Locally (and on any normal single-folder deploy) this "public" folder is
| a child of the project root, so "../vendor" etc. resolve correctly as-is.
|
| On hPanel, this folder is instead uploaded as the web server's document
| root SIDE BY SIDE with a "backend" folder holding the rest of the app -
| app/, bootstrap/, vendor/, storage/, .env, etc. - so "../vendor" would
| wrongly resolve to one level above both folders.
|
| Rather than keep two different copies of this file in sync, it detects
| which layout it's running under and resolves the right base path either
| way - no manual edit needed when deploying.
*/
$basePath = is_dir(__DIR__.'/../vendor') ? __DIR__.'/..' : __DIR__.'/../backend';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
