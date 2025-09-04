<?php
// public/index.php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Sessions first (before anything uses $_SESSION)
session_set_cookie_params([
  'httponly' => true,
  'samesite' => 'Lax',
  'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

// Your app bits
require __DIR__ . '/../app/bootstrap.php'; // creates $pdo + migrations
require __DIR__ . '/../app/auth.php';      // isLoggedIn(), logoutUser(), etc.
require __DIR__ . '/../app/csrf.php';      // csrf_token(), csrf_check()

$app  = AppFactory::create();
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);

// Slim routing middleware
$app->addRoutingMiddleware();

// Twig middleware
$app->add(TwigMiddleware::create($app, $twig));

// Globals available to all templates
$app->add(function ($request, $handler) use ($twig) {
    $env = $twig->getEnvironment();
    $env->addGlobal('loggedIn', !empty($_SESSION['uid']));
    $env->addGlobal('currentPath', $request->getUri()->getPath());
    return $handler->handle($request);
});

// Error middleware last
$app->addErrorMiddleware(true, true, true);

// Routes
require __DIR__ . '/../app/routes.php';

$app->run();
