<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Home (maybe show count)
$app->get('/', function (Request $req, Response $res) use ($pdo) {
  $count = 0;
  if (!empty($_SESSION['uid'])) {
    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM books WHERE user_id = :u');
    $stmt->execute([':u' => $_SESSION['uid']]);
    $count = (int)$stmt->fetch()['c'];
  }
  return \Slim\Views\Twig::fromRequest($req)->render($res, 'home.twig', ['count'=>$count]);
});

// Login form
$app->get('/login', function (Request $req, Response $res) {
    if (isLoggedIn()) { return $res->withHeader('Location', '/myshelf')->withStatus(302); }
    return \Slim\Views\Twig::fromRequest($req)->render($res, 'login.twig', [
        'csrf' => csrf_token(),
    ]);
});

// Register (GET)
$app->get('/register', function (Request $req, Response $res) {
    if (!empty($_SESSION['uid'])) { 
      return $res->withHeader('Location', '/myshelf')->withStatus(302); 
    }
    return \Slim\Views\Twig::fromRequest($req)->render($res, 'register.twig', [
        'csrf' => csrf_token(),
    ]);
});

// Register (POST)
$app->post('/register', function ($req, $res) use ($pdo) {
    if (!empty($_SESSION['uid'])) {
        return $res->withHeader('Location', '/myshelf')->withStatus(302);
    }

    $data = (array)$req->getParsedBody();

    // CSRF
    if (!isset($data['csrf']) || !csrf_check($data['csrf'])) {
        $res->getBody()->write('CSRF validation failed');
        return $res->withStatus(400);
    }

    // Inputs
    $username  = trim((string)($data['username'] ?? ''));
    $password  = (string)($data['password'] ?? '');
    $password2 = (string)($data['confirm-password'] ?? '');

    // Basic validation
    if ($username === '' || $password === '' || $password2 === '') {
        return \Slim\Views\Twig::fromRequest($req)->render($res, 'register.twig', [
            'error' => 'All fields are required.',
            'csrf'  => csrf_token(),
        ]);
    }
    if ($password !== $password2) {
        return \Slim\Views\Twig::fromRequest($req)->render($res, 'register.twig', [
            'error' => 'Passwords do not match.',
            'csrf'  => csrf_token(),
        ]);
    }
    if (strlen($password) < 8) {
        return \Slim\Views\Twig::fromRequest($req)->render($res, 'register.twig', [
            'error' => 'Password must be at least 8 characters.',
            'csrf'  => csrf_token(),
        ]);
    }

    // Create user
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :h)');
        $stmt->execute([':u' => $username, ':h' => $hash]);

        // Auto-login
        $uid = (int)$pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        $_SESSION['username'] = $username;

        // Go to shelf
        return $res->withHeader('Location', '/myshelf')->withStatus(302);

    } catch (Throwable $e) {
        // Handle duplicate username (UNIQUE constraint) or other DB errors
        $msg = 'Could not create account.';
        if (strpos(strtolower($e->getMessage()), 'unique') !== false) {
            $msg = 'Username is taken. Choose another.';
        }
        return \Slim\Views\Twig::fromRequest($req)->render($res, 'register.twig', [
            'error' => $msg,
            'csrf'  => csrf_token(),
        ]);
    }
});


// Login submit
$app->post('/login', function (Request $req, Response $res) use ($pdo) {
    $data = (array) $req->getParsedBody();
    if (!isset($data['csrf']) || !csrf_check($data['csrf'])) {
        $res->getBody()->write('CSRF validation failed');
        return $res->withStatus(400);
    }
    $username = trim((string)($data['username'] ?? ''));
    $pass  = (string)($data['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        loginUser((int)$user['id']);
        return $res->withHeader('Location', '/myshelf')->withStatus(302);
    }

    return \Slim\Views\Twig::fromRequest($req)->render($res, 'login.twig', [
        'error' => 'Invalid credentials',
        'csrf'  => csrf_token(),
    ]);
});

// Logout
$app->post('/logout', function (Request $req, Response $res) {
    logoutUser();
    return $res->withHeader('Location', '/')->withStatus(302);
});

$app->get('/admin', function (Request $req, Response $res) {
    if (!isLoggedIn()) { return $res->withHeader('Location', '/login')->withStatus(302); }
    return \Slim\Views\Twig::fromRequest($req)->render($res, 'admin.twig', [
        'csrf' => csrf_token(),
    ]);
});

// Shelf (list books for logged-in user)
$app->get('/myshelf', function (Request $req, Response $res) use ($pdo) {
  if (empty($_SESSION['uid'])) return $res->withHeader('Location','/login')->withStatus(302);
  $stmt = $pdo->prepare('SELECT id, title, author FROM books WHERE user_id = :u ORDER BY created_at DESC');
  $stmt->execute([':u' => $_SESSION['uid']]);
  $books = $stmt->fetchAll();
  return \Slim\Views\Twig::fromRequest($req)->render($res, 'shelf.twig', ['books'=>$books]);
});

// Add book form
$app->get('/books/new', function (Request $req, Response $res) {
  if (empty($_SESSION['uid'])) return $res->withHeader('Location','/login')->withStatus(302);
  return \Slim\Views\Twig::fromRequest($req)->render($res, 'book_new.twig', ['csrf'=>csrf_token()]);
});

// Create book
$app->post('/books', function (Request $req, Response $res) use ($pdo) {
  if (empty($_SESSION['uid'])) return $res->withHeader('Location','/login')->withStatus(302);
  $data = (array)$req->getParsedBody();
  if (!isset($data['csrf']) || !csrf_check($data['csrf'])) return $res->withStatus(400);
  $title = trim($data['title'] ?? '');
  $author = trim($data['author'] ?? '');
  $notes = trim($data['notes'] ?? '');
  if ($title === '' || $author === '') {
    return \Slim\Views\Twig::fromRequest($req)->render($res, 'book_new.twig', [
      'error'=>'Title and author required','csrf'=>csrf_token()
    ]);
  }
  $stmt = $pdo->prepare('INSERT INTO books (user_id, title, author, notes) VALUES (:u,:t,:a,:n)');
  $stmt->execute([':u'=>$_SESSION['uid'], ':t'=>$title, ':a'=>$author, ':n'=>$notes]);
  return $res->withHeader('Location','/shelf')->withStatus(302);
});
