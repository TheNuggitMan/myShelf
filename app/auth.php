<?php
// app/auth.php
declare(strict_types=1);

function isLoggedIn(): bool {
    return !empty($_SESSION['uid']);
}

function requireAuth($request, $handler) {
    if (!isLoggedIn()) {
        $resp = new \Slim\Psr7\Response();
        return $resp->withHeader('Location', '/login')->withStatus(302);
    }
    return $handler->handle($request);
}

function loginUser(int $userId): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $userId;
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}