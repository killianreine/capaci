<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Config\Database;
use App\Config\DatabaseSessionHandler;
use App\Models\Classes\Joueur;
ini_set('display_errors', 1);
error_reporting(E_ALL);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $basePath === '/' ? '' : $basePath);

session_set_save_handler(new DatabaseSessionHandler(), true);
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'httponly' => true,
	'samesite' => 'Lax',
	'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
]);
session_start();

$router = new Router();

$router->handleRequest();
